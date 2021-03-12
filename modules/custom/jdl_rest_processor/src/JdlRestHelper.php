<?php

namespace Drupal\jdl_rest_processor;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * JdlRestHelper service.
 */
class JdlRestHelper {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\Core\Entity\EntityBase|\Drupal\Core\Entity\EntityInterface|null
   */
  protected $user;

  /**
   * Constructs a JdlRestHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $connection, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->currentUser = $current_user;
    $this->user = User::load($current_user->id());
  }

  /**
   * Method description.
   */
  public function loadPlayerData(): array {
    return [
      'replays' => $this->getReplays(),
      'skins' => $this->getSkins(),
      'achievements' => $this->getAchievements(),
      'active_skin' => $this->getActiveSkin(),
      'available_levels' => $this->getAvailableLevels(),
      'current_level' => $this->getCurrentLevel(),
    ];
  }

  private function getAvailableLevels() {
    $levels = $this->user->field_available_levels ? json_decode($this->user->field_available_levels->value) : [];
    $levels[] = LEVEL_START;
    return array_flip($levels);
  }

  private function getCurrentLevel() {
    if (!$this->user->field_current_level) {
      return LEVEL_START;
    }
    $level = $this->user->field_current_level->referencedEntities();

    if (!$level) {
      return LEVEL_START;
    }
    $level = reset($level);

    return $level->getName();
  }

  private function getReplays() {
    $diffs = [
      DIFF_EASY => 'easy',
      DIFF_NORMAL => 'normal',
      DIFF_HARD => 'hard',
    ];

    $query = $this->connection->select('node_field_data', 'node');
    $query->join('node__field_data', 'data', 'node.nid = data.entity_id');
    $query->join('node__field_time', 'time', 'node.nid = time.entity_id');
    $query->join('node__field_difficulty', 'difficulty', 'node.nid = difficulty.entity_id');
    $query->join('node__field_level', 'level_ref', 'node.nid = level_ref.entity_id');
    $query->join('taxonomy_term_field_data', 'level', 'level_ref.field_level_target_id = level.tid');
    $query->join('node__field_rating', 'rating', 'node.nid = rating.entity_id');
    $query->condition('node.uid', $this->user->id());
    $query->condition('node.status', Node::PUBLISHED);
    $query->addField('data', 'field_data_value', 'data');
    $query->addField('time', 'field_time_value', 'time');
    $query->addField('level', 'name', 'id');
    $query->addField('rating', 'field_rating_value', 'rating');
    $query->addField('difficulty', 'field_difficulty_value', 'difficulty');

    $results = $query->execute();

    $replays = [];


    foreach ($results as $result) {
      $replays[$result->id][$diffs[$result->difficulty]] = [
        'time' => $result->time,
        'rating' => $result->rating,
        'data' => $result->data,
      ];
    }
    return $replays;
  }

  private function getSkins() {
    $query = $this->connection->select('user__field_skins', 'skins_ref');
    $query->join('taxonomy_term_field_data', 'skin', 'skins_ref.field_skins_target_id = skin.tid');
    $query->condition('skins_ref.entity_id', $this->user->id());
    $query->addField('skin', 'name', 'id');

    $skins = $query->execute()->fetchAllAssoc('id');

    if (!$skins) {
      $skins = [PLAYER_SKIN_DEFAULT => PLAYER_SKIN_DEFAULT];
    }

    return $skins;
  }

  private function getAchievements() {
    $query = $this->connection->select('user__field_achievements', 'achievement_ref');
    $query->join('taxonomy_term_field_data', 'achievement', 'achievement_ref.field_achievements_target_id = achievement.tid');
    $query->condition('achievement_ref.entity_id', $this->user->id());
    $query->addField('achievement', 'name', 'id');

    return $query->execute()->fetchAllAssoc('id');
  }

  private function getActiveSkin() {
    if (!$this->user->field_active_skin) {
      return PLAYER_SKIN_DEFAULT;
    }
    $skins = $this->user->field_active_skin->referencedEntities();

    if (!$skins) {
      return PLAYER_SKIN_DEFAULT;
    }
    $skin = reset($skins);

    return $skin->getName();
  }

}
