<?php

namespace Drupal\media_fits\Plugin\AdvancedQueue\JobType;

use function JmesPath\search;
use Drupal\taxonomy\Entity\Term;
use Drupal\media\Entity\Media;
use Drupal\file\FileInterface;
use Drupal\media\Plugin\media\Source\File;
use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use GuzzleHttp\Client;
use Drupal\advancedqueue\JobResult;

/**
 * Fits Job definition.
 *
 * @AdvancedQueueJobType(
 *   id = "media_fits_job",
 *   label = @Translation("Fits for Media"),
 * )
 */
class MediaFitsJob extends JobTypeBase {

  /**
   * Implements process().
   */
  public function process(Job $job) {
    try {
      $payload = $job->getPayload();

      // Set retry config.
      $this->pluginDefinition['max_retries'] = $payload['max_tries'];
      $this->pluginDefinition['retry_delay'] = $payload['retry_delay'];

      /** @var \Drupal\file\FileInterface $file */
      $media = Media::load($payload['mid']);
      $result = $this->extractFits($media);

      if ($result['result'] === TRUE) {
        return JobResult::success($this->t("%outcome", ['%outcome' => $result['outcome']]));
      }
      else {
        return JobResult::failure($this->t("%outcome", ['%outcome' => $result['outcome']]));
      }

    }
    catch (\Exception $e) {
      return JobResult::failure($e->getMessage());
    }
  }

  /**
   * Extract Fits.
   */
  public function extractFits($media = NULL) {
    /** @var \Drupal\media\MediaInterface $media */
    $config = \Drupal::config('media_fits.fitsconfig');
    $report = "";
    $sucess = TRUE;
    if (!isset($media)) {
      $report .= "Failed to get the media.\n";
      return ['result' => FALSE, "outcome" => $report];
    }
    
    // get the main source file
    $source = $media->getSource();
    if (!$source instanceof File) {
      $report .= "Failed to get the media's source file.\n";
      return ['result' => FALSE, "outcome" => $report];
    }
   
    $fid = $source->getSourceFieldValue($media);
    $file = \Drupal::entityTypeManager()->getStorage('file')->load($fid);
    if (!isset($file)) {
      $report .= "Failed to get the file in the media.\n";
      return ['result' => FALSE, "outcome" => $report];
    }
    // Extract Fits from xml.
    $fits_result = $this->getFits($file);

    if ($fits_result['code'] === 500) {
      $report .= 'Get Fits XML: ' . $fits_result['message'] . "\n";
      return ['result' => FALSE, "outcome" => $report];
    }

    $report .= "Get Fits XML: " . $fits_result['message'] . "\n";
    $fits_xml = $fits_result['output'];

    $fits = simplexml_load_string($fits_xml);
    $fit_json = json_encode($fits);

    if (json_last_error() == JSON_ERROR_NONE && $fit_json !== "false") {
      // Store the whole fits to json field.
      if ($media->hasField("field_media_file_fits")) {
        
        // Update fits field
        $media->field_media_file_fits->setValue($fit_json);
  
        // Extract selective fields and save to other fields.
        $media->save();
      }
      else {
        $report = "Media doesn't have JSON Fits field";
        return ['result' => FALSE, "outcome" => $report];
      }
    }
    else {
      $report .= "Unable to generate technical metadata for file, Please check the Fits configuration again.\n";
      return ['result' => FALSE, "outcome" => $report];
    }
    
    // TODO: write code to have metadata in file 
    return ['result' => $sucess, "outcome" => $report];
  }

  /**
   * Analyze field's description text and get Jmespath.
   */
  public function getJmespath($desc) {
    preg_match_all("/\[\{(.*?)\}\]/", $desc, $matches);
    $jmespath = $matches[1];
    if (is_array($jmespath) && count($jmespath) > 1) {
      return $jmespath;
    }
    elseif (is_array($jmespath) && count($jmespath) == 1) {
      return $jmespath[0];
    }
    else {
      return "";
    }

  }

  /**
   * From Jmespath(s), Get value of field from Fits json.
   */
  public function jmesPathSearch($path, $fits) {
    if (is_array($path)) {
      $value = "";
      foreach ($path as $p) {
        $value = search($p, $fits);
        if (!empty($value)) {
          break;
        }
      }
      return $value;
    }
    return search($path, $fits);
  }

  /**
   * Search PRONOM term.
   */
  public function searchPronom($pronom) {
    $tid = -1;
    if (isset($pronom)) {
      $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree("pronom");
      foreach ($terms as $term) {
        if ($pronom === $term->name) {
          $tid = $term->tid;
          break;
        }
      }
    }
    return $tid;
  }

  /**
   * Rest call to Fits.
   */
  public function getFits($file) {
    $config = \Drupal::config('media_fits.fitsconfig');
    if ($config->get("fits-method") === "remote") {
      try {
        $options = [
          'base_uri' => $config->get("fits-server-url"),
        ];
        $client = new Client($options);
        $response = $client->post($config->get("fits-server-url"), [
          'multipart' => [
            [
              'name' => 'datafile',
              'filename' => $file->label(),
              'contents' => file_get_contents($file->getFileUri()),
            ],
          ],
        ]);
        if (isset($response)) {
          return [
            "code" => 200,
            "message" => "Get Fits Technical Metadata successfully",
            'output' => $response->getBody()->getContents(),
          ];
        }
        else {
          return [
            "code" => 417,
            "message" => "Failed Get Fits Technical Metadata.",
            'output' => $response->getBody()->getContents(),
          ];
        }

      }
      catch (\Exception $e) {
        return ["code" => 500, 'message' => $e->getMessage()];
      }
    }
    else {
      try {
        $fits_path = $config->get("fits-path");
        $file_path = \Drupal::service('file_system')->realpath($file->getFileUri());
        $cmd = $fits_path . " -i '" . $file_path . "'";

        // Set env LANG for file name in multiple languages.
        putenv('LANG=en_US.UTF-8');
        $xml = `$cmd`;

        if (isset($xml)) {
          return [
            "code" => 200,
            "message" => "Get Fits Technical Metadata successfully",
            'output' => $xml,
          ];
        }
        else {
          return [
            "code" => 417,
            "message" => "Failed to get Fits Technical Metadata.",
            'output' => $xml,
          ];
        }
      }
      catch (\Exception $e) {
        return ["code" => 500, 'message' => $e->getMessage()];
      }
    }
  }

}
