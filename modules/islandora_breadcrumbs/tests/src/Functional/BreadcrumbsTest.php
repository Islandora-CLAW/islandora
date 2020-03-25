<?php

namespace Drupal\Tests\islandora_breadcrumbs\Functional;

use Drupal\Core\Url;
use Drupal\Tests\islandora\Functional\IslandoraFunctionalTestBase;
use Drupal\Tests\system\Functional\Menu\AssertBreadcrumbTrait;

/**
 * Tests the Islandora Breadcrumbs Builder.
 *
 * @group islandora_breadcrumbs
 */
class BreadcrumbsTest extends IslandoraFunctionalTestBase {

  use AssertBreadcrumbTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'islandora_breadcrumbs',
  ];


  /**
   * A node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $nodeA;

  /**
   * Another node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $nodeB;

  /**
   * Yet another node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $nodeC;

  /**
   * Another one.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $nodeD;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create some nodes.
    $this->nodeA = $this->container->get('entity_type.manager')->getStorage('node')->create([
      'type' => $this->testType->id(),
      'title' => 'Node A',
    ]);
    $this->nodeA->save();

    $this->nodeB = $this->container->get('entity_type.manager')->getStorage('node')->create([
      'type' => $this->testType->id(),
      'title' => 'Node B',
    ]);
    $this->nodeB->set('field_member_of', [$this->nodeA->id()]);
    $this->nodeB->save();

    $this->nodeC = $this->container->get('entity_type.manager')->getStorage('node')->create([
      'type' => $this->testType->id(),
      'title' => 'Node C',
    ]);
    $this->nodeC->set('field_member_of', [$this->nodeB->id()]);
    $this->nodeC->save();

    $this->nodeD = $this->container->get('entity_type.manager')->getStorage('node')->create([
      'type' => $this->testType->id(),
      'title' => 'Node D',
    ]);
    $this->nodeD->set('field_member_of', [$this->nodeC->id()]);
    $this->nodeD->save();

    $this->drupalPlaceBlock(
      'system_breadcrumb_block',
      [
        'region' => 'content',
        'theme' => $this->config('system.theme')->get('default'),
      ]
    );
  }

  /**
   * @covers \Drupal\islandora_breadcrumbs\IslandoraBreadcrumbBuilder::applies
   */
  public function testDefaults() {
    $breadcrumbs = [
      Url::fromRoute('<front>')->toString() => 'Home',
      $this->nodeA->toUrl()->toString() => $this->nodeA->label(),
      $this->nodeB->toUrl()->toString() => $this->nodeB->label(),
      $this->nodeC->toUrl()->toString() => $this->nodeC->label(),
    ];
    $this->assertBreadcrumb($this->nodeD->toUrl()->toString(), $breadcrumbs);

    // Create a reference loop.
    $this->nodeA->set('field_member_of', [$this->nodeD->id()]);
    $this->nodeA->save();

    // We should still escape it and have the same trail as before.
    $this->assertBreadcrumb($this->nodeD->toUrl()->toString(), $breadcrumbs);
  }

}
