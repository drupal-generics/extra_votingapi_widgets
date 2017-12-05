<?php

namespace Drupal\extra_votingapi_widgets\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'survey' voting api field type.
 *
 * @FieldType(
 *   id = "voting_api_survey_field",
 *   label = @Translation("Voting api survey field"),
 *   description = @Translation("A voting api field with string options to vote."),
 *   default_widget = "voting_api_survey_widget",
 *   default_formatter = "voting_api_survey_formatter"
 * )
 */
class VotingApiSurveyItem extends StringItem {

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\votingapi_widgets\Plugin\Field\FieldType\VotingApiField::defaultStorageSettings()
   * @see votingapi_widgets_theme_suggestions_alter()
   *   The 'vote_plugin' setting is not used for other than to have a vote
   *   summary template suggestion.
   */
  public static function defaultStorageSettings() {
    return [
      'vote_plugin' => 'survey',
      'vote_type' => 'survey_vote',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    // This is called very early by the user entity roles field. Prevent
    // early t() calls by using the TranslatableMarkup.
    $properties['value_id'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Value ID'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    $schema['columns']['value_id'] = [
      'description' => 'Unique ID per value.',
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\votingapi_widgets\Plugin\Field\FieldType\VotingApiField::defaultFieldSettings()
   */
  public static function defaultFieldSettings() {
    return [
      'anonymous_window' => -1,
      'user_window' => -1,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\votingapi_widgets\Plugin\Field\FieldType\VotingApiField::fieldSettingsForm()
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $dateFormatter = \Drupal::service('date.formatter');
    $form = parent::fieldSettingsForm($form, $form_state);

    $unit_options = [
      300,
      900,
      1800,
      3600,
      10800,
      21600,
      32400,
      43200,
      86400,
      172800,
      345600,
      604800,
    ];

    $unit_options_form = [];
    foreach ($unit_options as $option) {
      $unit_options_form[$option] = $dateFormatter->formatInterval($option);
    }

    $unit_options_form[0] = $this->t('never');
    $unit_options_form[-1] = $this->t('votingapi default');

    $form['anonymous_window'] = [
      '#type' => 'select',
      '#title' => $this->t('Anonymous vote rollover'),
      '#description' => $this->t("The amount of time that must pass before two anonymous votes from the same computer are considered unique. Setting this to never will eliminate most double-voting, but will make it impossible for multiple anonymous on the same computer (like internet cafe customers) from casting votes."),
      '#options' => $unit_options_form,
      '#default_value' => $this->getSetting('anonymous_window'),
    ];

    $form['user_window'] = [
      '#type' => 'select',
      '#title' => $this->t('Registered user vote rollover'),
      '#description' => $this->t("The amount of time that must pass before two registered user votes from the same user ID are considered unique. Setting this to never will eliminate most double-voting for registered users."),
      '#options' => $unit_options_form,
      '#default_value' => $this->getSetting('user_window'),
    ];
    return $form;
  }

}
