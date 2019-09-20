<?php

namespace Drupal\islandora\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Session\UserSession;
use Drupal\user\Entity\User;
use Drush\Commands\DrushCommands;

/**
 * Adds a userid option to migrate:import.
 *
 * ... because the --user option was removed from drush 9.
 */
class IslandoraCommands extends DrushCommands {

  /**
   * Add the userid option.
   *
   * @hook option migrate:import
   * @option userid User ID to run the migration.
   */
  public function optionsetImportUser($options = ['userid' => self::REQ]) {
  }

  /**
   * Validate the provided userid.
   *
   * @hook validate migrate:import
   */
  public function validateUser(CommandData $commandData) {
    $userid = $commandData->input()->getOption('userid');
    if ($userid) {
      $account = User::load($userid);
      if (!$account) {
        throw new \Exception("User ID does not match an existing user.");
      }
    }
  }

  /**
   * Switch the active user account to perform the import.
   *
   * @hook pre-command migrate:import
   */
  public function preImport(CommandData $commandData) {
    $userid = $commandData->input()->getOption('userid');
    if ($userid) {
      $account = User::load($userid);
      $accountSwitcher = \Drupal::service('account_switcher');
      $userSession = new UserSession([
        'uid'   => $account->id(),
        'name'  => $account->getUsername(),
        'roles' => $account->getRoles(),
      ]);
      $accountSwitcher->switchTo($userSession);
      $this->logger()->notice(
          dt(
              'Now acting as user ID @id',
              ['@id' => \Drupal::currentUser()->id()]
            )
      );
    }
  }

  /**
   * Switch the user back once the migration is complete.
   *
   * @hook post-command migrate:import
   */
  public function postImport($result, CommandData $commandData) {
    if ($commandData->input()->getOption('userid')) {
      $accountSwitcher = \Drupal::service('account_switcher');
      $this->logger()->notice(dt(
                                  'Switching back from user @uid.',
                                  ['@uid' => \Drupal::currentUser()->id()]
                                ));
      $accountSwitcher->switchBack();
    }
  }

}
