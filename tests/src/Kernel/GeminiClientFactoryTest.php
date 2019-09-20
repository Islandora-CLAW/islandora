<?php

namespace Drupal\Tests\islandora\Kernel;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\islandora\GeminiClientFactory;
use Islandora\Crayfish\Commons\Client\GeminiClient;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

/**
 * Class GeminiClientFactoryTest.
 *
 * @package Drupal\Tests\islandora\Kernel
 * @group islandora
 * @coversDefaultClass \Drupal\islandora\GeminiClientFactory
 */
class GeminiClientFactoryTest extends IslandoraKernelTestBase {

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $prophecy = $this->prophesize(LoggerInterface::class);
    $prophecy->notice(Argument::any());
    $this->logger = $prophecy->reveal();
  }

  /**
   * @covers ::create
   * @expectedException \Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException
   */
  public function testNoUrlBlank() {
    $prophecy = $this->prophesize(ImmutableConfig::class);
    $prophecy->get(Argument::any())->willReturn('');
    $immutConfig = $prophecy->reveal();

    $prophecy = $this->prophesize(ConfigFactoryInterface::class);
    $prophecy->get(Argument::any())->willReturn($immutConfig);
    $configFactory = $prophecy->reveal();

    GeminiClientFactory::create($configFactory, $this->logger);
  }

  /**
   * @covers ::create
   * @expectedException \Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException
   */
  public function testNoUrlNull() {
    $prophecy = $this->prophesize(ImmutableConfig::class);
    $prophecy->get(Argument::any())->willReturn(NULL);
    $immutConfig = $prophecy->reveal();

    $prophecy = $this->prophesize(ConfigFactoryInterface::class);
    $prophecy->get(Argument::any())->willReturn($immutConfig);
    $configFactory = $prophecy->reveal();

    GeminiClientFactory::create($configFactory, $this->logger);
  }

  /**
   * @covers ::create
   * @throws \Exception
   */
  public function testUrl() {
    $prophecy = $this->prophesize(ImmutableConfig::class);
    $prophecy->get(Argument::any())->willReturn('http://localhost:8000/gemini');
    $immutConfig = $prophecy->reveal();

    $prophecy = $this->prophesize(ConfigFactoryInterface::class);
    $prophecy->get(Argument::any())->willReturn($immutConfig);
    $configFactory = $prophecy->reveal();

    $this->assertInstanceOf(GeminiClient::class, GeminiClientFactory::create($configFactory, $this->logger));
  }

}
