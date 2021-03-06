<?php

namespace Drupal\jdl_achievements\Plugin\JdlAchievements;

use Drupal\jdl_achievements\JdlAchievementsPluginBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Plugin implementation of the jdl_achievements.
 *
 * @JdlAchievements(
 *   id = "sand_enjoyer",
 *   label = @Translation("Sand Enjoyer"),
 *   description = @Translation("Sand Enjoyer.")
 * )
 */
class SandEnjoyer extends JdlAchievementsPluginBase {
    public function check(AccountInterface $account) {
        return false;
    }
}
