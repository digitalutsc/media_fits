<?php

namespace Drupal\media_fits;

use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\media\MediaInterface;
use Drupal\media_fits\ContextProvider\MediaContextProvider;
use Drupal\media_fits\MediaFitsContextManager;
use Drupal\media\Entity\Media;
/**
 * Utility functions for firing off context reactions.
 */
class MediaFitsContextUtils {
  /**
   * Context manager.
   *
   * @var \Drupal\media_fits\MediaFitsContextManager
   */
  protected $contextManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $contextRepository
   *   Context repository.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $contextHandler
   *   Context handler.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entityFormBuilder
   *   Entity Form Builder.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   Theme manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $currentRouteMatch
   *   Route match.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ContextRepositoryInterface $contextRepository,
    ContextHandlerInterface $contextHandler,
    EntityFormBuilderInterface $entityFormBuilder,
    ThemeManagerInterface $themeManager,
    RouteMatchInterface $currentRouteMatch
  ) {
    $this->contextManager = new MediaFitsContextManager(
      $entityTypeManager,
      $contextRepository,
      $contextHandler,
      $entityFormBuilder,
      $themeManager,
      $currentRouteMatch
    );
  }

  /**
   * Executes context reactions for a File.
   *
   * @param string $reaction_type
   *   Reaction type.
   * @param \Drupal\media\MediaInterface $media
   *   File to evaluate contexts and pass to reaction.
   */
  public function executeFileReactions($reaction_type, MediaInterface $media) {
    $provider = new MediaContextProvider($media);
    $provided = $provider->getRuntimeContexts([]);
    $this->contextManager->evaluateContexts($provided);
    foreach ($this->contextManager->getActiveReactions($reaction_type) as $reaction) {
      $reaction->execute($media);
    }
  }

}
