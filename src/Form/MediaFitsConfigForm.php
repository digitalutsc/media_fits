<?php

namespace Drupal\media_fits\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MediaFitsConfigForm definition.
 */
class MediaFitsConfigForm extends ConfigFormBase {

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a MediaFitsConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityFieldManagerInterface $entity_field_manager,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    parent::__construct($config_factory);
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'media_fits.fitsconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_fits_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('media_fits.fitsconfig');

    $form['container'] = [
      '#type' => 'container',
    ];
    $form['container']['fits-services-config'] = [
      '#type' => 'details',
      '#title' => 'General Settings',
      '#open' => TRUE,

    ];

    $form['container']['fits-services-config']['method'] = [
      '#type' => 'select',
      '#title' => 'Select Fits method:',
      '#options' => [
        0 => $this->t('-- Select --'),
        'remote' => $this->t('FITS Web Service'),
        'local' => $this->t('FITS from the command-line'),
      ],
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::textfieldsCallback',
        'wrapper' => 'textfields-container',
        'effect' => 'fade',
      ],
      '#default_value' => ($config->get("fits-method") !== NULL) ? $config->get("fits-method") : "",
    ];

    $form['container']['fits-services-config']['textfields_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'textfields-container'],
    ];

    if ((array_key_exists("method", $form_state->getValues()) && $form_state->getValues()['method'] === "remote")
          || (empty($form_state->getValues()['method']) && $config->get("fits-method") === "remote")) {

      $form['container']['fits-services-config']['textfields_container']['server-url'] = [
        '#type' => 'textfield',
        '#name' => 'server-url',
        '#title' => $this
          ->t('Fits XML Services URL:'),
        '#default_value' => ($config->get("fits-server-url") !== NULL) ? $config->get("fits-server-url") : "",
        '#description' => $this->t('For example: <code>http://localhost:8080/fits/examine</code>'),
      ];
    }
    elseif (array_key_exists("method", $form_state->getValues()) && $form_state->getValues()['method'] === "local"
    || (empty($form_state->getValues()['method']) && $config->get("fits-method") === "local")) {
      $form['container']['fits-services-config']['textfields_container']['fits-path'] = [
        '#type' => 'textfield',
        '#title' => $this
          ->t('System path to FITS processor:'),
        '#default_value' => ($config->get("fits-path") !== NULL) ? $config->get("fits-path") : "",
        '#description' => $this->t('Example: <code>/usr/bin/fits.sh</code>'),
      ];
    }

    $form['container']['fits-services-config']['op-config'] = [
      '#type' => 'details',
      '#title' => $this
        ->t('Advanced Queue Configuration'),
      '#open' => TRUE,
    ];

    $queues = ['0' => "-- Select --"];
    $queues = array_merge($queues, $this->entityTypeManager->getStorage('advancedqueue_queue')->getQuery()->execute());
    $form['container']['fits-services-config']['op-config']['advancedqueue-id'] = [
      '#type' => 'select',
      '#name' => 'advancedqueue-id',
      '#title' => $this->t('Select a queue'),
      '#required' => TRUE,
      '#default_value' => ($config->get("fits-advancedqueue_id") !== NULL) ? $config->get("fits-advancedqueue_id") : 0,
      '#options' => $queues,
    ];
    $form['container']['fits-services-config']['op-config']['link-to-add-queue'] = [
      '#markup' => $this->t('To create a new queue, <a href="/admin/config/system/queues/add" target="_blank">Click here</a>'),
    ];

    $form['container']['fits-services-config']['op-config']['number-of-retries'] = [
      '#type' => 'number',
      '#title' => $this
        ->t('Number of retries:'),
      '#description' => $this->t("If a job is failed to run, set number of retries"),
      '#default_value' => ($config->get("aqj-max-retries") !== NULL) ? $config->get("aqj-max-retries") : 5,
    ];

    $form['container']['fits-services-config']['op-config']['retries-delay'] = [
      '#type' => 'number',
      '#title' => $this
        ->t('Retry Delay (in seconds):'),
      '#description' => $this->t("Set the delay time (in seconds) for a job to re-run each time."),
      '#default_value' => ($config->get("aqj-retry_delay") !== NULL) ? $config->get("aqj-retry_delay") : 100,
    ];

    $form['container']['fits-services-config']['extact-fits-while-ingesting'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Extracting Fits while a file is being uploaded'),
      "#access" => FALSE,
      '#default_value' => ($config->get("fits-extract-ingesting") !== NULL) ? $config->get("fits-extract-ingesting") : 1,
    ];

    // Select default fits fields.
    $field_map = $this->entityFieldManager->getFieldMap();
    $node_field_map = $field_map['file'];
    $fields = array_keys($node_field_map);
    $fits_fields = [];
    foreach ($fields as $f) {
      if (strpos($f, "_fits") !== FALSE || strpos($f, "_fits_") !== FALSE) {
        $fits_fields[$f] = $f;
      }
    }
    return $form;
  }

  /**
   * Ajax callback for textfield.
   */
  public function textfieldsCallback($form, FormStateInterface $form_state) {
    return $form['container']['fits-services-config']['textfields_container'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $configFactory = $this->configFactory->getEditable('media_fits.fitsconfig');
    $configFactory->set("fits-method", $form_state->getValues()['method']);

    if ($form_state->getValues()['method'] === "local") {
      $configFactory->set("fits-path", $form_state->getValues()['fits-path']);
      $configFactory->set("fits-server-url", "");
      $configFactory->set("fits-server-endpoint", "");
    }
    else {
      $configFactory->set("fits-server-url", $form_state->getValues()['server-url']);
      $configFactory->set("fits-path", "");
    }

    $configFactory->set("fits-advancedqueue_id", $form_state->getValues()['advancedqueue-id']);
    $configFactory->set("aqj-max-retries", $form_state->getValues()['number-of-retries']);
    $configFactory->set("aqj-retry_delay", $form_state->getValues()['retries-delay']);
    $configFactory->set("fits-extract-ingesting", $form_state->getValues()['extact-fits-while-ingesting']);

    // Save the config.
    $configFactory->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Query existing File types.
   */
  public function getFileTypes() {
    $contentTypes = $this->entityTypeManager->getStorage('file_type')->loadMultiple();
    $types = [];
    foreach ($contentTypes as $contentType) {
      $types[$contentType->id()] = $contentType->label();
    }
    return $types;
  }

}
