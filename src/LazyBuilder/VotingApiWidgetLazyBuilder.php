<?php

namespace Drupal\extra_votingapi_widgets\LazyBuilder;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class VotingApiWidgetLazyBuilder.
 *
 * @todo Refactoring, dependency injection, etc. There are some contrib patches
 *   already in place, but in need review and not complete, i.e., not
 *   applicable. Work should be done on those first then applied here too.
 *
 * @package Drupal\extra_votingapi_widgets\LazyBuilder
 *
 * @see \Drupal\votingapi_widgets\VotingApiLoader
 *   The contrib implementation this logic is based on.
 * @see \Drupal\votingapi_widgets\Plugin\VotingApiWidgetBase
 *   The contrib implementation this logic is based on.
 */
class VotingApiWidgetLazyBuilder {

  use StringTranslationTrait;

  /**
   * Builds a survey voting api widget.
   *
   * @see \Drupal\votingapi_widgets\VotingApiLoader::buildForm()
   */
  public function buildSurveyWidget($entity_type,
                                    $entity_id,
                                    $vote_type,
                                    $field_name,
                                    $style,
                                    $show_results,
                                    $read_only,
                                    $input = NULL) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = \Drupal::service('entity_type.manager')->getStorage($entity_type)->load($entity_id);
    $vote = $this->getVote($entity_type, $entity->bundle(), $entity_id, $vote_type, $field_name);

    $form_id = Html::getUniqueId('vote-form');

    $widget = [
      'rating' => [
        '#theme' => 'container',
        '#attributes' => [
          'class' => [
            'votingapi-widgets',
            'survey',
            ($read_only) ? 'read_only' : '',
          ],
          'id' => $form_id,
        ],
        '#children' => [],
        '#weight' => 2,
      ],
    ];

    $vote_options = $this->getVoteOptions($entity, $field_name);

    if ($vote->isNew()) {
      /** @var \Drupal\Core\Entity\EntityFormBuilder $form_builder */
      $form_builder = \Drupal::service('entity.form_builder');

      $form_state_additions = [
        'options' => $vote_options,
        'style' => $style,
        'show_results' => $show_results,
        'read_only' => $read_only,
        'submit_ajax_callback' => [$this, 'ajaxSubmit'],
        'submit_ajax_wrapper' => $form_id,
      ];

      if (isset($input)) {
        $form_state_additions['input'] = $input;
      }

      $form = $form_builder->getForm($vote, 'extra_votingapi_widgets_survey', $form_state_additions);

      $widget['rating']['#children']['form'] = $form;
    }
    else {
      $widget['title'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => ['results-text'],
        ],
        '#value' => $this->t('Results:'),
        '#weight' => 1,
      ];

      $widget['rating']['#children']['result'] = [
        '#theme' => 'container',
        '#attributes' => [
          'class' => ['vote-result'],
        ],
        '#children' => $this->getVoteSummary($vote, $vote_options, $style),
        '#weight' => 100,
      ];
    }

    return $widget;
  }

  /**
   * Creates or loads a vote instance.
   *
   * @return \Drupal\votingapi\VoteInterface
   *   The created/loaded vote instance.
   *
   * @see \Drupal\votingapi_widgets\Plugin\VotingApiWidgetBase::getEntityForVoting()
   */
  public function getVote($entity_type, $entity_bundle, $entity_id, $vote_type, $field_name) {
    $storage = \Drupal::service('entity.manager')->getStorage('vote');
    $currentUser = \Drupal::currentUser();
    $voteData = [
      'entity_type' => $entity_type,
      'entity_id'   => $entity_id,
      'type'      => $vote_type,
      'field_name'  => $field_name,
      'user_id' => $currentUser->id(),
    ];
    $vote = $storage->create($voteData);
    $timestamp_offset = $this->getUserTimeWindow('user_window', $entity_type, $entity_bundle, $field_name);

    if ($currentUser->isAnonymous()) {
      $voteData['vote_source'] = \Drupal::service('request_stack')->getCurrentRequest()->getClientIp();
      $timestamp_offset = $this->getUserTimeWindow('anonymous_window', $entity_type, $entity_bundle, $field_name);
    }

    $query = \Drupal::entityQuery('vote');
    foreach ($voteData as $key => $value) {
      $query->condition($key, $value);
    }

    // Check for rollover 'never' setting.
    if (!empty($timestamp_offset)) {
      $query->condition('timestamp', time() - $timestamp_offset, '>=');
    }

    $votes = $query->execute();
    if ($votes && count($votes) > 0) {
      $vote = $storage->load(array_shift($votes));
    }

    return $vote;
  }

  /**
   * Get time window settings.
   *
   * @see \Drupal\votingapi_widgets\Plugin\VotingApiWidgetBase::getWindow()
   *   Additional fix implemented when $window_field_setting is checked to set
   *   $use_site_default.
   */
  protected function getUserTimeWindow($window_type, $entity_type_id, $entity_bundle, $field_name) {
    $config = FieldConfig::loadByName($entity_type_id, $entity_bundle, $field_name);

    $window_field_setting = $config->getSetting($window_type);
    $use_site_default = FALSE;

    if ($window_field_setting === NULL || $window_field_setting == -1) {
      $use_site_default = TRUE;
    }

    $window = $window_field_setting;
    if ($use_site_default) {
      /*
       * @var \Drupal\Core\Config\ImmutableConfig $voting_configuration
       */
      $voting_configuration = \Drupal::config('votingapi.settings');
      $window = $voting_configuration->get($window_type);
    }

    return $window;
  }

  /**
   * Helper method; retrieves the available vote options from the field.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The voted entity instance.
   * @param string $field_name
   *   The field name.
   *
   * @return array
   *   The available vote options.
   *
   * @see \Drupal\votingapi_widgets\Annotation\VotingApiWidget
   *   In the contrib implementation the vote options are stored statically in
   *   the widget plugin's definition, but we need to retrieve them from the
   *   field.
   *
   * @todo A plugin may provide the available vote options, similar like contrib
   *   does it, but refactored to our needs.
   */
  public function getVoteOptions(EntityInterface $entity, $field_name) {
    $vote_options = [];

    foreach ($entity->{$field_name} as $option) {
      if ($option->value_id) {
        $vote_options[$option->value_id] = $option->value;
      }
    }

    return $vote_options;
  }

  /**
   * Generate summary.
   *
   * @see \Drupal\votingapi_widgets\Plugin\VotingApiWidgetBase::getVoteSummary()
   */
  protected function getVoteSummary($vote, $vote_options, $style) {
    $results = $this->getResults($vote, $vote_options);

    switch ($style) {
      case 'google_charts':
        /** @var \Drupal\Core\Block\BlockManager $block_manager */
        $block_manager = \Drupal::service('plugin.manager.block');
        $block_config = [
          'results' => $results,
        ];
        $block_plugin = $block_manager->createInstance('extra_votingapi_widgets_summary_pie_chart_block', $block_config);
        $summary = $block_plugin->build();
        break;

      default:
        $summary = [
          '#theme' => 'votingapi_widgets_summary',
          '#vote' => $vote,
          '#results' => $results,
          '#field_name' => $vote->field_name->value,
        ];
        break;
    }

    return $summary;
  }

  /**
   * Get results.
   *
   * @see \Drupal\votingapi_widgets\Form\BaseRatingForm::getResults()
   *   This is a stripped down implementation of the original, because that's
   *   not quite right implemented.
   */
  public function getResults($entity, $vote_options, $result_function = FALSE, $reset = FALSE) {
    $results = \Drupal::service('extra_votingapi_widgets.plugin.manager.votingapi.resultfunction')
      ->getResults($entity->getVotedEntityType(), $entity->getVotedEntityId());

    if (!array_key_exists($entity->bundle(), $results)) {
      return [];
    }

    $field_name = $entity->field_name->value;
    $fieldResults = [];

    foreach ($results[$entity->bundle()] as $key => $result) {
      if (strrpos($key, $field_name) !== FALSE) {
        $key = explode(':', $key);
        $fieldResults[$key[0]] = $this->preprocessResult($key[0], $result, $vote_options);
      }
    }

    return $fieldResults;
  }

  /**
   * Helper method; preprocess complex result.
   *
   * @todo Refactoring, think of a better solution. Missing from contrib as it's
   *   required for complex results only.
   */
  protected function preprocessResult($resultfunction, $result, $vote_options) {
    $field_results = [];

    switch ($resultfunction) {
      case 'field_value_count':
      case 'field_user_value_count':
        foreach ($vote_options as $option_key => $option_label) {
          $field_results[$option_key] = [
            'label' => $option_label,
            'count' => $result[$option_key] ?? 0,
          ];
        }
        break;

      default:
        $field_results = $result;
        break;
    }

    return $field_results;
  }

  /**
   * Ajax submit handler.
   *
   * @see \Drupal\votingapi_widgets\Form\BaseRatingForm::ajaxSubmit()
   *   This is a stripped down implementation of the original, because that's
   *   not quite right implemented.
   */
  public function ajaxSubmit(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\votingapi\VoteInterface $vote */
    $vote = $form_state->getFormObject()
      ->getEntity();

    // Resets the form's user input so it won't detect the current submission if
    // the form is going to be displayed again.
    return $this->buildSurveyWidget($vote->getVotedEntityType(),
      $vote->getVotedEntityId(),
      $vote->bundle(),
      $vote->field_name->value,
      $form_state->get('style'),
      $form_state->get('show_results'),
      $form_state->get('read_only'),
      []);
  }

}
