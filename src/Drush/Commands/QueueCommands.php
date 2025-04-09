<?php

namespace Drupal\media_fits\Drush\Commands;

use Drush\Commands\DrushCommands;
use Drupal\media\Entity\Media;
use Drupal\advancedqueue\Entity\Queue;
use Drupal\advancedqueue\Job;

/**
 * Drush commands for Media FITS operations.
 */
class QueueCommands extends DrushCommands
{

    /**
     * Queues jobs for media based on a CSV file or a comma-separated list.
     *
     * @command media-fits:queue
     * @aliases mfq
     *
     * @param mixed $queueId
     *   The name of the queue you want to index it to
     * 
     * @option csv
     *   The full path to a CSV file containing media IDs.
     *
     * @option mids
     *   A comma-separated list of media IDs (e.g., "1,2,3,4").
     *
     * @usage drush media-fits:queue --csv=/path/to/media_ids.csv
     *   Reads the CSV file, skips the header row, and queues jobs for each media ID.
     *
     * @usage drush media-fits:queue --mids="1,2,3,4"
     *   Directly queues jobs for media IDs 1, 2, 3, and 4.
     *
     * @bootstrap full
     */
    public function queueMediaJobs($queueId, $options = ['csv' => null,
     'mids' => null]
    ) {

        $config = \Drupal::config('media_fits.fitsconfig');
        $advancedQueueId = $queueId;
        $maxTries = $config->get("aqj-max-retries");
        $retryDelay = $config->get("aqj-retry_delay");


        $queue = Queue::load($advancedQueueId);
        if (!$queue) {
            $this->logger()->error(
                "Unable to load advanced 
            queue with ID {$advancedQueueId}."
            );
            return;
        }

        $media_ids = [];


        if (!empty($options['mids'])) {
            $media_ids = array_map('trim',   explode(',', $options['mids']));
        } elseif (!empty($options['csv'])) {
            $csv_file = $options['csv'];
            if (($handle = fopen($csv_file, 'r')) !== false) {
                $row_count = 0;
                while (($data = fgetcsv($handle)) !== false) {
                    // Skip header row.
                    if ($row_count === 0) {
                        $row_count++;
                        continue;
                    }
                    if (!empty($data[0])) {
                        $media_ids[] = trim($data[0]);
                    }
                    $row_count++;
                }
                fclose($handle);
            } else {
                $this->logger()->error("Unable to open CSV file at {$csv_file}.");
                return;
            }
        } else {
            $this->logger()->error(
                "Please provide either the
             --csv or --mids option."
            );
            return;
        }

        // Process each media ID and queue a job.
        $queued_count = 0;
        foreach ($media_ids as $mid) {
            $media = Media::load($mid);
            if (!$media) {
                $this->logger()->warning(
                    "Media with ID {$mid}
                 not found. Skipping."
                );
                continue;
            }

            $payload = [
            'mid' => $media->id(),
            'media_name' => $media->getName(),
            'type' => $media->getEntityTypeId(),
            'action' => "extract_Fits",
            'max_tries' => $maxTries,
            'retry_delay' => $retryDelay,
            ];

            $job = Job::create('media_fits_job', $payload);
            if ($job instanceof Job) {
                $queue->enqueueJob($job);
                $this->logger()->success("Queued job for media ID {$mid}.");
                $queued_count++;
            } else {
                $this->logger()->error("Failed to create job for media ID {$mid}.");
            }
        }

        $this->logger()->notice(
            "Finished processing.
         Total queued jobs: {$queued_count}."
        );
    }
}
