<?php

namespace Drupal\extra_votingapi_widgets\Plugin\ComplexVoteResultFunction;

use Drupal\extra_votingapi_widgets\Plugin\ComplexFieldVoteResultFunctionBase;
use Drupal\Core\Entity\Query\QueryAggregateInterface;

/**
 * Count of a set of votes grouped by the votes values for members.
 *
 * @VoteResultFunction(
 *   id = "field_user_value_count",
 *   label = @Translation("Value count for members"),
 *   description = @Translation("The number of votes cast grouped by votes values for members."),
 *   deriver = "Drupal\extra_votingapi_widgets\Plugin\Derivative\FieldResultFunction",
 * )
 */
class FieldUserValueCount extends ComplexFieldVoteResultFunctionBase {

  /**
   * {@inheritdoc}
   */
  public function calculateComplexResult(QueryAggregateInterface $vote_query) {
    $this->filterVotesByField($vote_query);

    $alias = 'value_count';
    $query_results = $vote_query->groupBy('value')
      ->aggregate('id', 'COUNT', NULL, $alias)
      ->condition('user_id', 0, '<>')
      ->execute();

    $result = [];

    foreach ($query_results as $query_result) {
      $result[$query_result['value']] = $query_result['value_count'];
    }

    return $result;
  }

}
