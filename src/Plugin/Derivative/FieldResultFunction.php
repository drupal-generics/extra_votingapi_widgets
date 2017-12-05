<?php

namespace Drupal\extra_votingapi_widgets\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Deriver base class for field vote calculations.
 *
 * @see \Drupal\votingapi_widgets\Plugin\Derivative\FieldResultFunction
 *   The contrib implementation this logic is based on.
 */
class FieldResultFunction extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $instances = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('voting_api_survey_field');
    $this->derivatives = [];
    foreach ($instances as $entity_type => $fields) {
      foreach (array_keys($fields) as $field_name) {
        $plugin_id = $entity_type . '.' . $field_name;
        $this->derivatives[$plugin_id] = $base_plugin_definition;
      }
    }
    return $this->derivatives;
  }

}
