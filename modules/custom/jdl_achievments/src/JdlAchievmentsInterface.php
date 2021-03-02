<?php

namespace Drupal\jdl_achievments;

use Drupal\Core\Session\AccountInterface;

/**
 * Interface for jdl_achievments plugins.
 */
interface JdlAchievmentsInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();
  
  public function check(AccountInterface $account);

}
