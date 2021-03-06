<?php

namespace Drupal\jdl_achievements\Plugin\JdlAchievements;

use Drupal\jdl_achievements\JdlAchievementsPluginBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Plugin implementation of the jdl_achievements.
 *
 * @JdlAchievements(
 *   id = "sand_fan",
 *   label = @Translation("Sand Fan"),
 *   description = @Translation("Sand Fan.")
 * )
 */
class SandFan extends JdlAchievementsPluginBase {
    public function check(AccountInterface $account) {
        $database = \Drupal::database();
        $current_user = \Drupal::currentUser();

        $query = $database->select('node_field_data', 'node');
        $query->join('node_revision__field_level', 'level_ref', 'node.nid = level_ref.entity_id');
        $query->join('taxonomy_term__field_world', 'world_ref', 'level_ref.field_level_target_id = world_ref.entity_id');

        $query->fields('world_ref', ['entity_id']);
        $query->condition('world_ref.field_world_target_id', 28);
        $query->condition('node.uid', $current_user->id());
        $completed_beach_levels = $query->execute()->fetchCol();

        $query = $database->select('taxonomy_term__field_world', 'world_ref');
        $query->fields('world_ref', ['entity_id']);
        $query->condition('world_ref.field_world_target_id', 28);
        $all_beach_levels = $query->execute()->fetchCol();

        if (count($completed_beach_levels) == count($all_beach_levels)) {
          $this->grantReward();
          return TRUE;
        }

        return FALSE;
    }
}
