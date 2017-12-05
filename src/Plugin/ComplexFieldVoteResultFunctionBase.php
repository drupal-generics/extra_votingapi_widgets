<?php

namespace Drupal\extra_votingapi_widgets\Plugin;

use Drupal\votingapi_widgets\FieldVoteResultBase;
use Drupal\Core\Entity\Query\QueryAggregateInterface;

/**
 * Class ComplexFieldVoteResultFunctionBase.
 *
 * @package Drupal\extra_votingapi_widgets
 */
abstract class ComplexFieldVoteResultFunctionBase extends FieldVoteResultBase implements ComplexVoteResultFunctionInterface {

  /**
   * {@inheritdoc}
   */
  final public function calculateResult($votes) {
    return 0;
  }

  /**
   * Helper method; filters the vote aggregate query by field name.
   *
   * @param \Drupal\Core\Entity\Query\QueryAggregateInterface $vote_query
   *   Aggregate query for vote entities.
   *
   * @see \Drupal\votingapi_widgets\FieldVoteResultInterface::getVotesForField()
   *   It's better to filter out the irrelevant vote entities with a query in
   *   the database than in PHP on the loaded vote entities as contrib does.
   * @see calculateComplexResult()
   *   A common use case is to filter by field name before the complex result
   *   recalculation.
   */
  protected function filterVotesByField(QueryAggregateInterface $vote_query) {
    $plugin_id = explode('.', $this->getDerivativeId());
    $field_name = $plugin_id[1];

    $vote_query->condition('field_name', $field_name);
  }

}
