<?php

namespace Drupal\media_fits\Plugin\ContextReaction;

use Drupal\context\ContextReactionPluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Delete reaction.
 *
 * @ContextReaction(
 *   id = "media_fits_reaction",
 *   label = @Translation("File Extract Metadata Reaction (FITS)")
 * )
 */
class MediaFitsReaction extends ContextReactionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Action storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $actionStorage;

  /**
   * Action IDs to display.
   *
   * @var array
   */
  protected $actionIds;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $action_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->actionStorage = $action_storage;
    $this->actionIds = ['media_fits_action'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('action')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Perform a pre-configured action.');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(EntityInterface $entity = NULL) {
    $config = $this->getConfiguration();
    $action_id = $config['actions'];
    $action = $this->actionStorage->load($action_id);
    if ($action) {
      $action->execute([$entity]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $actions = $this->actionStorage->loadMultiple($this->actionIds);
    foreach ($actions as $action) {
      $options[ucfirst($action->getType())][$action->id()] = $action->label();
    }
    $config = $this->getConfiguration();

    $form['actions'] = [
      '#title' => $this->t('Metadata Generation Actions'),
      '#description' => $this->t('Pre-configured actions to execute.'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $config['actions'] ?? '',
      '#size' => 10,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration(['actions' => $form_state->getValue('actions')]);
  }

}
