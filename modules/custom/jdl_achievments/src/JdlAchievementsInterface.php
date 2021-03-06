<?php

namespace Drupal\jdl_achievements;

use Drupal\Core\Session\AccountInterface;

/**
 * Interface for jdl_achievements plugins.
 */
interface JdlAchievementsInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

  public function check(AccountInterface $account);

}
