<?php

namespace Drupal\extra_votingapi_widgets\Plugin;

use Drupal\Core\Entity\Query\QueryAggregateInterface;

/**
 * Provides an interface for a ComplexVoteResultFunction plugin.
 *
 * @see \Drupal\votingapi\Annotation\VoteResultFunction
 * @see \Drupal\extra_votingapi_widgets\Plugin\ComplexVoteResultFunctionManager
 * @see \Drupal\extra_votingapi_widgets\Plugin\ComplexFieldVoteResultFunctionBase
 * @see plugin_api
 */
interface ComplexVoteResultFunctionInterface {

  /**
   * Performs the calculations on a set of votes to derive the result.
   *
   * @param \Drupal\Core\Entity\Query\QueryAggregateInterface $vote_query
   *   Aggregate query for vote entities.
   *
   * @return mixed
   *   A complex result based on the supplied votes.
   *
   * @see \Drupal\votingapi\VoteResultFunctionInterface::calculateResult()
   *   It's better to do the calculations with a query in the database than in
   *   PHP on the loaded vote entities as contrib does.
   */
  public function calculateComplexResult(QueryAggregateInterface $vote_query);

}
