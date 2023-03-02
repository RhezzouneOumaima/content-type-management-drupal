<?php

namespace Drupal\Tests\radioactivity\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\user\Entity\Role;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the Radioactivity Emit Controller Endpoint.
 *
 * @group radioactivity
 */
class EmitControllerEndpointTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'radioactivity',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Give anonymous users permission to access content, so the emit endpoint
    // can be reached.
    $this->installConfig(['user']);
    $anonymous_role = Role::load(Role::ANONYMOUS_ID);
    $anonymous_role->grantPermission('access content');
    $anonymous_role->save();
  }

  /**
   * Tests that the emit endpoint does not accept invalid requests.
   */
  public function testRestValidation() {

    $http_kernel = $this->container->get('http_kernel');

    $request = Request::create('/radioactivity/emit');
    $response = $http_kernel->handle($request);

    $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    $this->assertStringContainsString('"status":"error"', $response->getContent());
    $this->assertStringContainsString('"message":"Empty request."', $response->getContent());
  }

}
