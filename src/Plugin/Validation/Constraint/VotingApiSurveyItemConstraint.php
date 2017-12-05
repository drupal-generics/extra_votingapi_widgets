<?php

namespace Drupal\extra_votingapi_widgets\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if the answers of a 'survey' voting api field are valid.
 *
 * @Constraint(
 *   id = "VotingApiSurveyItem",
 *   label = @Translation("Answers of a 'survey' voting api field validation.", context = "Validation"),
 * )
 *
 * @see \Drupal\extra_votingapi_widgets\Plugin\Field\FieldType\VotingApiSurveyItem
 */
class VotingApiSurveyItemConstraint extends Constraint {

  /**
   * Answers count validation message.
   *
   * The message that will be shown if the number of answers of the translation
   * do not match with the number of answers of the original translation.
   *
   * @var string
   */
  public $countOriginal = 'The number of answers must be the same as for the original language.';

}
