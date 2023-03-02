<?php

namespace Drupal\radioactivity\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\radioactivity\IncidentStorageInterface;
use Drupal\radioactivity\StorageFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Serialization\Json;
use Drupal\radioactivity\Incident;

/**
 * Controller for radioactivity emit routes.
 *
 * Handles incoming emits, verifies the data and stores it in incident storage.
 * Returns a JSON formatted response with a status.
 */
class EmitController implements ContainerInjectionInterface {

  /**
   * The incident storage.
   *
   * @var \Drupal\radioactivity\IncidentStorageInterface
   */
  protected IncidentStorageInterface $incidentStorage;

  /**
   * Constructs an EmitController object.
   *
   * @param \Drupal\radioactivity\StorageFactory $storageFactory
   *   Radioactivity storage factory.
   */
  public function __construct(StorageFactory $storageFactory) {
    $this->incidentStorage = $storageFactory->get('default');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('radioactivity.storage')
    );
  }

  /**
   * Callback for /radioactivity/emit.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Response object.
   */
  public function emit(Request $request): JsonResponse {

    $postData = $request->getContent();
    if (empty($postData)) {
      return new JsonResponse([
        'status' => 'error',
        'message' => 'Empty request.',
      ]);
    }

    $count = 0;
    $incidents = Json::decode($postData);

    foreach ($incidents as $data) {

      $incident = Incident::createFromPostData($data);
      if (!$incident->isValid()) {
        return $this->buildJsonStatusResponse('error', "invalid incident ($count).");
      }

      $this->incidentStorage->addIncident($incident);
      $count++;
    }

    return $this->buildJsonStatusResponse('ok', "$count incidents added");
  }

  /**
   * Creates a JSON status message response.
   *
   * @param string $status
   *   The status content of the response.
   * @param string $message
   *   The message content of the response.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response.
   */
  protected function buildJsonStatusResponse(string $status, string $message): JsonResponse {
    return new JsonResponse([
      'status' => $status,
      'message' => $message,
    ]);
  }

}
