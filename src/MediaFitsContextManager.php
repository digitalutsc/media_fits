<?php

namespace Drupal\media_fits;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\context\ContextInterface;
use Drupal\context\ContextManager;
use Drupal\Core\Condition\ConditionPluginCollection;
use Drupal\Core\Plugin\ContextAwarePluginInterface;

/**
 * Provide additional bits to assist Contexts managing.
 */
class FitsContextManager extends ContextManager {

  /**
   * Evaluate all context conditions.
   *
   * @param \Drupal\Core\Plugin\Context\Context[] $provided
   *   Additional provided (core) contexts to apply to Conditions.
   */
  public function evaluateContexts(array $provided = []) {
    $this->activeContexts = [];
    if (!empty($provided)) {
      $this->contexts = [];
      $this->contextConditionsEvaluated = FALSE;
    }
    foreach ($this->getContexts() as $context) {
      if ($this->evaluateContextConditions($context, $provided) && !$context->disabled()) {
        $this->activeContexts[$context->id()] = $context;
      }
    }
    $this->contextConditionsEvaluated = TRUE;
  }

  /**
   * Evaluate a contexts conditions.
   *
   * @param \Drupal\context\ContextInterface $context
   *   The context to evaluate conditions for.
   * @param \Drupal\Core\Plugin\Context\Context[] $provided
   *   Additional provided (core) contexts to apply to Conditions.
   *
   * @return bool
   *   TRUE if conditions pass
   */
  public function evaluateContextConditions(ContextInterface $context, array $provided = []) {
    $conditions = $context->getConditions();
    if (!$this->applyContexts($conditions, $provided)) {
      return FALSE;
    }

    $logic = $context->requiresAllConditions()
      ? 'and'
      : 'or';

    if (!count($conditions)) {
      $logic = 'and';
    }

    return $this->resolveConditions($conditions, $logic);
  }

  /**
   * Apply context to all the context aware conditions in the collection.
   *
   * @param \Drupal\Core\Condition\ConditionPluginCollection $conditions
   *   A collection of conditions to apply context to.
   * @param \Drupal\Core\Plugin\Context\Context[] $provided
   *   Additional provided (core) contexts to apply to Conditions.
   *
   * @return bool
   *   TRUE if conditions pass
   */
  protected function applyContexts(ConditionPluginCollection &$conditions, array $provided = []) {
    if (count($conditions) == 0) {
      return TRUE;
    }
    $passed = FALSE;
    foreach ($conditions as $condition) {
      if ($condition instanceof ContextAwarePluginInterface) {
        try {
          if (empty($provided)) {
            $contexts = $this->contextRepository->getRuntimeContexts(array_values($condition->getContextMapping()));
          }
          else {
            $contexts = $provided;
          }
          $this->contextHandler->applyContextMapping($condition, $contexts);
          $passed = TRUE;
        }
        catch (ContextException $e) {
          continue;
        }
      }
    }

    return $passed;
  }

}
