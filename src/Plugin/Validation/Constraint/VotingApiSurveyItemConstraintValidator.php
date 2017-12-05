<?php

namespace Drupal\extra_votingapi_widgets\Plugin\Validation\Constraint;

use Drupal\Core\Language\LanguageInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the VotingApiSurveyItem constraint.
 *
 * @see \Drupal\extra_votingapi_widgets\Plugin\Validation\Constraint\VotingApiSurveyItemConstraint
 * @see extra_votingapi_widgets_entity_bundle_field_info_alter()
 *   Only nodes supported for now.
 */
class VotingApiSurveyItemConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    if (!$items || $items->isEmpty()) {
      return;
    }

    /** @var \Drupal\node\NodeInterface $node */
    $node = $items->getEntity();
    if (!($node instanceof NodeInterface)) {
      return;
    }

    // Get the node's original translation.
    $node = $node->getTranslation(LanguageInterface::LANGCODE_DEFAULT);

    // Validate that the translation's number of answers are equal to the
    // original translation's number of answers.
    if ($items->getLangcode() != $node->language()->getId()) {
      $field_name = $items->getName();

      if ($items->count() != $node->$field_name->count()) {
        $this->context->addViolation($constraint->countOriginal);
      }
    }
  }

}
