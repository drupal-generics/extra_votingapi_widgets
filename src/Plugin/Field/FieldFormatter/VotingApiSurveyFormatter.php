<?php

namespace Drupal\extra_votingapi_widgets\Plugin\Field\FieldFormatter;

use Drupal\votingapi_widgets\Plugin\Field\FieldFormatter\VotingApiFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Defines the 'survey' voting api field formatter.
 *
 * @FieldFormatter(
 *   id = "voting_api_survey_formatter",
 *   label = @Translation("Voting api survey"),
 *   field_types = {
 *     "voting_api_survey_field"
 *   },
 * )
 *
 * @see \Drupal\votingapi_widgets\Plugin\Field\FieldFormatter\VotingApiFormatter
 *   The contrib implementation this logic is based on.
 */
class VotingApiSurveyFormatter extends VotingApiFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'show_results' => TRUE,
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Implement settings form.
      'style'        => [
        '#title'         => $this->t('Styles'),
        '#type'          => 'select',
        '#options'       => [
          'default'          => $this->t('Default'),
          'google_charts'    => $this->t('Google charts'),
        ],
        '#default_value' => $this->getSetting('style'),
      ],
      'readonly'     => [
        '#title'         => $this->t('Readonly'),
        '#type'          => 'checkbox',
        '#default_value' => $this->getSetting('readonly'),
      ],
      'show_results' => [
        '#title'         => $this->t('Show results'),
        '#type'          => 'checkbox',
        '#default_value' => $this->getSetting('show_results'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $entity = $items->getEntity();
    $field_settings = $this->getFieldSettings();
    $field_name = $this->fieldDefinition->getName();

    $vote_type = $field_settings['vote_type'];

    $elements[] = [
      'vote_form' => [
        '#lazy_builder'       => [
          'extra_votingapi_widgets.lazy_builder:buildSurveyWidget',
          [
            $entity->getEntityTypeId(),
            $entity->id(),
            $vote_type,
            $field_name,
            $this->getSetting('style'),
            $this->getSetting('show_results'),
            $this->getSetting('readonly'),
          ],
        ],
        '#create_placeholder' => TRUE,
      ],
      'results'   => [],
    ];

    return $elements;
  }

}
