<?php

namespace Drupal\jdl_achievments\Plugin\JdlAchievments;

use Drupal\jdl_achievments\JdlAchievmentsPluginBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Plugin implementation of the jdl_achievments.
 *
 * @JdlAchievments(
 *   id = "sand_enjoyer",
 *   label = @Translation("Sand Enjoyer"),
 *   description = @Translation("Sand Enjoyer.")
 * )
 */
class SandEnjoyer extends JdlAchievmentsPluginBase {
    public function check(AccountInterface $account) {
        return false;
    }
}
