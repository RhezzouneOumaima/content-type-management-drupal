<?php

namespace Drupal\radioactivity;

/**
 * The worker class for the Radioactivity rest endpoint.
 *
 * The rest endpoint is a thin wrapper around the methods of this worker class.
 * The rest functionality is split in two files to allow unit testing. The
 * return values of the public functions are specific for the use in the
 * endpoint to allow maximum test coverage.
 *
 * @see endpoints/file/rest.php
 *
 * @package Drupal\radioactivity
 */
class RestProcessor {

  /**
   * The path and file name where the payload data is stored.
   *
   * @var string
   */
  private string $payloadFile;

  /**
   * Constructor.
   *
   * @param array $config
   *   Processor configuration to override the default.
   */
  public function __construct(array $config = []) {

    $config += [
      'payload_file' => sys_get_temp_dir() . '/radioactivity-payload.json',
    ];

    $this->payloadFile = $config['payload_file'];
  }

  /**
   * Processes incoming radioactivity incident data.
   *
   * @param string $data
   *   Json formatted incident data. The format must match the return value
   *   of \Drupal\radioactivity\Incident::toJson.
   *
   * @return string
   *   Json formatted result status message.
   */
  public function processData(string $data): string {

    if (!$this->verifyData($data)) {
      return $this->restStatus('error', 'Invalid json.');
    }

    $fh = fopen($this->payloadFile, 'a+');
    fwrite($fh, $data . ',' . PHP_EOL);
    fclose($fh);

    return $this->restStatus('ok', 'Inserted.');
  }

  /**
   * Clears the rest data storage.
   *
   * @return string
   *   Json formatted result status message.
   */
  public function clearData(): string {

    if (file_exists($this->payloadFile)) {
      unlink($this->payloadFile);
    }
    return $this->restStatus('ok', 'Cleared.');
  }

  /**
   * Returns the stored incident data.
   *
   * @return string
   *   Json formatted incident data.
   */
  public function getData(): string {

    if (file_exists($this->payloadFile)) {
      $fh = fopen($this->payloadFile, 'r');
      $data = fread($fh, filesize($this->payloadFile));
      fclose($fh);

      return '[' . rtrim($data, ',' . PHP_EOL) . ']';
    }
    else {
      return '[]';
    }
  }

  /**
   * Returns a generic error message.
   *
   * @return string
   *   Json formatted error status message.
   */
  public function error(): string {
    return $this->restStatus('error', 'Nothing to do.');
  }

  /**
   * Simple verification of the incident data.
   *
   * @param string $data
   *   Json encoded emission data received in $_POST.
   *
   * @return bool
   *   True when $data is a valid object, false if not.
   */
  private function verifyData(string $data): bool {

    $incidents = json_decode($data, TRUE);
    $keys = ['fn', 'et', 'id', 'ti', 'e', 'h'];
    foreach ($incidents as $incident) {
      if (count($keys) !== count($incident) || count(array_intersect_key(array_flip($keys), $incident)) !== count($keys)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Returns status and message.
   *
   * @param string $status
   *   The status code (ok, error).
   * @param string $message
   *   The message describing the status.
   */
  private function restStatus(string $status, string $message) {

    return json_encode([
      'status' => $status,
      'message' => $message,
    ]);
  }

}
