<?php

namespace Drupal\jdl_rest_processor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Returns responses for JDL REST Processor routes.
 */
class Leaderboard extends ControllerBase {

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
   * The controller constructor.
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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('current_user')
    );
  }

  /**
   * Builds the response.
   */
  public function view(Request $request, $level_id, $difficulty) {
    $levels = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'name' => $level_id,
    ]);

    if (!$levels) {
      throw new BadRequestHttpException('Bad request.');
    }

    $level = reset($levels);

    $data = [];
    switch($level->field_level_type->value) {
      case 'time':
        $query = $this->connection->select('taxonomy_term_field_data', 'level');
        $query->join('node__field_level', 'level_ref', 'level.tid = level_ref.field_level_target_id');
        $query->join('node__field_time', 'replay_time', 'level_ref.entity_id = replay_time.entity_id');
        $query->join('node__field_difficulty', 'difficulty', 'level_ref.entity_id = difficulty.entity_id');
        $query->join('node_field_data', 'replay', 'level_ref.entity_id = replay.nid');
        $query->join('users_field_data', 'player', 'replay.uid = player.uid');
        $query->addField('replay', 'nid', 'replay_id');
        $query->addField('replay_time', 'field_time_value', 'time');
        $query->addField('player', 'name', 'player');
        $query->orderBy('time');
        $query->range(0, 5);
        $query->condition('level.name', $level_id);
        $query->condition('difficulty.field_difficulty_value', $difficulty);
        $data = $query->execute()->fetchAll();

        $data = [
          'lvl' => $level_id,
          'leaderboard' => $data,
        ];
        break;

      case 'survival':
        break;

      case 'rhythm':
        break;
    }


    return new JsonResponse($data);
  }

}
