<?php

namespace Drupal\media_fits\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sets the current file as a context on file routes.
 */
class MediaRouteContextProvider implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new FileRouteContextProvider.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $context_definition = EntityContextDefinition::fromEntityTypeId('media')->setLabel(NULL)->setRequired(FALSE);

    $value = NULL;

    $route_object = $this->routeMatch->getRouteObject();
    if ($route_object) {
      $route_contexts = $route_object->getOption('parameters');
      if ($route_contexts && isset($route_contexts['media'])) {
        $media = $this->routeMatch->getParameter('media');
        if ($media) {
          $value = $media;
        }
      }
    }

    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);

    $context = new Context($context_definition, $value);
    $context->addCacheableDependency($cacheability);
    return ['media' => $context];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = EntityContext::fromEntityTypeId('media', $this->t('Media from URL'));
    return ['media' => $context];
  }

}
