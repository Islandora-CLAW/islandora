<?php

namespace Drupal\islandora\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\migrate_tools\Commands\MigrateToolsCommands;
use Drupal\user\Entity\User;
use Drush\Commands\DrushCommands;

/**
 * Adds a userid option to migrate:import
 *
 * ... because the --user option was removed from drush 9.
 */
class IslandoraCommands extends DrushCommands {

  /**
  * @hook option migrate:import
  * @option userid User ID to run the migration.
  */
  public function optionsetImportUser($options = ['userid' => self::REQ])
  {
  }

  /**
   * @hook validate migrate:import
   */
  public function validateUser(CommandData $commandData)
  {
    $userid = $commandData->input()->getOption('userid');
    if($userid)
    {
      $account = \Drupal\user\Entity\User::load($userid);
      if (!$account) {
          throw new \Exception("User ID does not match an existing user.");
      }
    }
  }

  /**
  * @hook pre-command migrate:import
  */
  public function preImport(CommandData $commandData)
  {
    //
    $userid = $commandData->input()->getOption('userid');
    if ($userid)
    {
      $account = \Drupal\user\Entity\User::load($userid);
      $accountSwitcher = \Drupal::service('account_switcher');
      $userSession = new UserSession([
                                      'uid' => $account->id(),
                                      'name'=>$account->getUsername(),
                                      'roles'=>$account->getRoles()
                                    ]);
      $accountSwitcher->switchTo($userSession);
      $this->logger()->notice(
          dt(
              'Now acting as user ID @id',
              ['@id'=>\Drupal::currentUser()->id()]
            )
      );
    }
  }

  /**
   * @hook post-command migrate:import
   */
  public function postImport($result, CommandData $commandData)
  {
    if ($commandData->input()->getOption('userid'))
    {
      $accountSwitcher = \Drupal::service('account_switcher');
      $this->logger()->notice(dt(
                                  'Switching back from user @uid.',
                                  ['@uid'=>\Drupal::currentUser()->id()]
                                ));
      $accountSwitcher->switchBack();
    }
  }
}
