<?php

namespace Drupal\extra_votingapi_widgets\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\Query\QueryAggregateInterface;

/**
 * Class ComplexVoteResultFunctionManager.
 *
 * @package Drupal\extra_votingapi_widgets\Plugin
 *
 * @see \Drupal\votingapi\VoteResultFunctionManager
 *   The contrib implementation this logic is based on.
 * @see \Drupal\extra_votingapi_widgets\Plugin\ComplexVoteResultFunctionInterface
 *   The discovered plugin types.
 */
class ComplexVoteResultFunctionManager extends DefaultPluginManager {

  /**
   * ComplexVoteResultFunctionManager constructor.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ComplexVoteResultFunction', $namespaces, $module_handler, 'Drupal\extra_votingapi_widgets\Plugin\ComplexVoteResultFunctionInterface', 'Drupal\votingapi\Annotation\VoteResultFunction');
    $this->alterInfo('complex_vote_result_info');
    $this->setCacheBackend($cache_backend, 'complex_vote_result_plugins');
  }

  /**
   * {@inheritdoc}
   *
   * @todo Fix the contrib's deprecated method calls.
   */
  public function getResults($entity_type_id, $entity_id) {
    $results = [];

    $result = db_select('votingapi_result', 'v')
      ->fields('v', ['type', 'function', 'complex_value'])
      ->condition('entity_type', $entity_type_id)
      ->condition('entity_id', $entity_id)
      ->execute();
    while ($row = $result->fetchAssoc()) {
      $results[$row['type']][$row['function']] = unserialize($row['complex_value']);
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Fix the contrib's deprecated method calls.
   * @todo Refactor the $vote_type verification shortcut.
   */
  public function recalculateResults($entity_type_id, $entity_id, $vote_type) {
    // Currently no other vote type should have complex results.
    if ($vote_type != 'survey_vote') {
      return;
    }

    $vote_query = \Drupal::entityQueryAggregate('vote')
      ->condition('entity_type', $entity_type_id)
      ->condition('entity_id', $entity_id)
      ->condition('type', $vote_type);

    $this->performAndStore($entity_type_id, $entity_id, $vote_type, $vote_query);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Fix the contrib's deprecated method calls.
   */
  protected function performAndStore($entity_type_id, $entity_id, $vote_type, QueryAggregateInterface $vote_query) {
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      $plugin_vote_query = clone $vote_query;
      $plugin = $this->createInstance($plugin_id);

      $fields = [
        'entity_id' => $entity_id,
        'entity_type' => $entity_type_id,
        'type' => $vote_type,
        'function' => $plugin_id,
        'value' => 0,
        'complex_value' => serialize($plugin->calculateComplexResult($plugin_vote_query)),
        'timestamp' => REQUEST_TIME,
      ];

      db_insert('votingapi_result')
        ->fields($fields)
        ->execute();
    }
  }

}
