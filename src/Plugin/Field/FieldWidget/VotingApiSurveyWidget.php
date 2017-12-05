<?php

namespace Drupal\extra_votingapi_widgets\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the 'survey' voting api field widget.
 *
 * @FieldWidget(
 *   id = "voting_api_survey_widget",
 *   label = @Translation("Voting api survey"),
 *   field_types = {
 *     "voting_api_survey_field"
 *   }
 * )
 *
 * @see \Drupal\votingapi_widgets\Plugin\Field\FieldWidget\VotingApiWidget
 *   Implement votes status.
 */
class VotingApiSurveyWidget extends StringTextfieldWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['value_id'] = [
      '#type' => 'hidden',
      '#value' => $delta + 1,
    ];

    return $element;
  }

}
