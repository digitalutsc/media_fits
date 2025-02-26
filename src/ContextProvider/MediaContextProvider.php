<?php

namespace Drupal\media_fits\ContextProvider;

use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media\MediaInterface;

/**
 * Sets the provided media as a context.
 */
class MediaContextProvider implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * File to provide in a context.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $media;

  /**
   * Constructs a new MediaContextProvider.
   *
   * @var \Drupal\media\MediaInterface $media
   *   The file to provide in a context.
   */
  public function __construct(MediaInterface $media) {
    $this->media = $media;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $context = EntityContext::fromEntity($this->media);
    return ['@media_fits.media_route_context_provider:media' => $context];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = EntityContext::fromEntityTypeId('media', $this->t('media from entity hook'));
    return ['@media_fits.media_route_context_provider:media' => $context];
  }

}
