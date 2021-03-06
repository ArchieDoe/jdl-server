<?php

namespace Drupal\jdl_achievements;

use Drupal\Component\Plugin\PluginBase;
use Drupal\user\Entity\User;

/**
 * Base class for jdl_achievements plugins.
 */
abstract class JdlAchievementsPluginBase extends PluginBase implements JdlAchievementsInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  public function grantReward() {
    $achievements = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties([
          'field_plugin_id' => $this->getPluginId(),
        ]
      );
    $achievement = reset($achievements);


    $user = User::load(\Drupal::currentUser()->id());
    if ($achievement->field_player_skin_reward) {

      $award_skin_id = $achievement->field_player_skin_reward->target_id;

      $existing_skins = $user->field_skins ? $user->field_skins->getValue() : [];

      $has_skin = FALSE;
      foreach ($existing_skins as $skin) {
        if ($skin['target_id'] == $award_skin_id) {
          $has_skin = TRUE;
          break;
        }
      }

      if (!$has_skin) {
        if (!$user->field_skins) {
          $user->field_skins->target_id = $award_skin_id;
        }
        else {
          $user->field_skins->appendItem($award_skin_id);
        }
      }

      $user->save();
    }
  }

}
