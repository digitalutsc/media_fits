<?php

namespace Drupal\media_fits\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\advancedqueue\Entity\Queue;
use Drupal\advancedqueue\Job;

/**
 * Provides a 'FitsAction' action.
 *
 * @Action(
 *  id = "media_fits_action",
 *  label = @Translation("FITS - Generate and Extract Technical metadata for Media"),
 *  type = "file",
 *  category = @Translation("Custom")
 * )
 */
class MediaFitsAction extends ActionBase {

  /**
   * Implements access()
   */
  public function access($file, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\file\FileInterface $file */
    $access = $file->access('update', $account, TRUE)
      ->andIf($file->access('edit', $account, TRUE));
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * Implements execute().
   */
  public function execute($file = NULL) {
    /** @var \Drupal\file\FileInterface $file */
    $config = \Drupal::config('fits.fitsconfig');
    // Create a job and add to Advanced Queue.
    $payload = [
      'fid' => $file->id(),
      'file_name' => $file->getFilename(),
      'type' => $file->getEntityTypeId(),
      'action' => "extract_Fits",
      'max_tries' => $config->get("aqj-max-retries"),
      'retry_delay' => $config->get("aqj-retry_delay"),
    ];

    $job = Job::create('media_fits_job', $payload);
    if ($job instanceof Job) {
      $q = Queue::load($config->get("fits-advancedqueue_id"));
      $q->enqueueJob($job);
    }
  }

}
