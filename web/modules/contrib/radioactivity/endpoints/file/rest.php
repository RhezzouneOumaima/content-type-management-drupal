<?php

/**
 * @file
 * Rest endpoint for handling radioactivity incidents for radioactivity module.
 */

use Drupal\radioactivity\RestProcessor;

include '../../src/RestProcessor.php';
$processor = new RestProcessor();

header('Content-Type: application/json; charset=utf-8');

// Process incoming incident data.
$data = file_get_contents('php://input');
if (strlen($data) > 0) {
  echo $processor->processData($data);
  exit();
}

// Return stored incident data.
if (isset($_GET['get'])) {
  echo $processor->getData();
  exit();
}

// Delete stored data.
if (isset($_GET['clear'])) {
  echo $processor->clearData();
  exit();
}

// Respond to unknown request.
echo $processor->error();
