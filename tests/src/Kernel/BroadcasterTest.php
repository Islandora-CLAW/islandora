<?php

namespace Drupal\Tests\islandora\Kernel;

use Stomp\Exception\StompException;
use Stomp\StatefulStomp;
use Drupal\islandora\Plugin\RulesAction\Broadcaster;

/**
 * Broadcaster tests.
 *
 * @group islandora
 * @coversDefaultClass \Drupal\islandora\Plugin\RulesAction\Broadcaster
 */
class BroadcasterTest extends IslandoraKernelTestBase {

  protected $testQueue = 'islandora-broadcaster-test-queue';

  /**
   * Tests that the action does not WSOD Drupal when there's a StompException.
   *
   * @covers \Drupal\islandora\Plugin\RulesAction\Broadcaster::execute
   */
  public function testExecuteSquashesStompExceptions() {
    // Set up a fake Stomp client to throw a StompException when used.
    $prophecy = $this->prophesize(StatefulStomp::CLASS);
    $prophecy->begin()->willThrow(new StompException('STOMP EXCEPTION'));
    $stomp = $prophecy->reveal();

    $action = $this->createBroadcaster($stomp);

    try {
      // Execute the action.
      $action->execute();
      $this->assertTrue(TRUE, "The execute() method must squash StompExceptions.");
    }
    catch (\Exception $e) {
      $this->assertTrue(FALSE, "The execute() method must squash StompExceptions.");
    }

  }

  /**
   * Tests that the action DOES NOT squash any other Exception.
   *
   * @covers \Drupal\islandora\Plugin\RulesAction\Broadcaster::execute
   * @expectedException \Exception
   */
  public function testExecuteDoesNotSquashOtherExceptions() {
    // Set up a fake Stomp client to throw a non-StompException when used.
    $prophecy = $this->prophesize(StatefulStomp::CLASS);
    $prophecy->begin()->willThrow(new \Exception('NOT A STOMP EXCEPTION'));
    $stomp = $prophecy->reveal();

    $action = $this->createBroadcaster($stomp);

    // This should throw an exception.
    $action->execute();
  }

  /**
   * Tests that the action publishes the message to be broadcast to a broker.
   *
   * @covers \Drupal\islandora\Plugin\RulesAction\Broadcaster::execute
   */
  public function testBrokerIntegration() {
    // Grab a legit stomp client, using values from config.
    $this->installConfig('islandora');
    $stomp = $this->container->get('islandora.stomp');

    // Create and execute the action.
    $action = $this->createBroadcaster($stomp);
    $action->execute();

    // Verify the message actually got sent.
    try {
      $stomp->subscribe($this->testQueue);
      $msg = $stomp->read();

      $this->assertTrue(
        strcmp($msg->getBody(), 'test') == 0,
        "Message body is not 'test'"
      );

      $headers = $msg->getHeaders();
      $this->assertTrue(
        strcmp($headers['IslandoraBroadcastRecipients'], 'activemq:queue:foo,activemq:queue:bar') == 0,
        "IslandoraBroadcastRecipients header must be a comma separated list of endpoints"
      );
      $stomp->unsubscribe();
    }
    catch (StompException $e) {
      $this->assertTrue(FALSE, "There was an error connecting to the stomp broker");
    }
  }

  /**
   * Utility function to create a broadcaster action from a Stomp prophecy.
   *
   * @param \Stomp\StatefulStomp $stomp
   *   Stomp instance or prophecy.
   *
   * @return \Drupal\islandora\Plugin\RulesAction\Broadcaster
   *   Broadcaster, ready to test.
   */
  protected function createBroadcaster(StatefulStomp $stomp) {
    // Pull the plugin definition out of the plugin system.
    $actionManager = $this->container->get('plugin.manager.rules_action');
    $jwt = $this->container->get('jwt.authentication.jwt');
    $definitions = $actionManager->getDefinitions();
    $pluginDefinition = $definitions['islandora_broadcast'];

    $action = new Broadcaster(
      [],
      'islandora_broadcast',
      $pluginDefinition,
      $this->testQueue,
      $stomp,
      $jwt
    );

    // Set the required contexts for the action to run.
    $action->setContextValue('message', "test");
    $action->setContextValue('recipients', ['activemq:queue:foo', 'activemq:queue:bar']);

    return $action;
  }

}
