<?php

namespace Drupal\jdl_rest_processor\Controller;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\example\ExampleInterface;
use Drupal\jdl_rest_processor\JdlRestHelper;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Returns responses for JDL REST Processor routes.
 */
class Replay extends ControllerBase {


  /**
   * @var PluginManagerInterface
   */
  private $pluginManagerJdlAchievements;

  /**
   * @var \Drupal\Core\Entity\EntityBase|\Drupal\Core\Entity\EntityInterface|null
   */
  protected $user;

  /**
   * @var \Drupal\jdl_rest_processor\JdlRestHelper
   */
  protected $restHelper;

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    PluginManagerInterface $plugin_manager_jdl_achievements,
    AccountInterface $current_user,
    JdlRestHelper $jdl_rest_helper
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->pluginManagerJdlAchievements = $plugin_manager_jdl_achievements;
    $this->currentUser = $current_user;
    $this->user = User::load($this->currentUser->id());
    $this->restHelper = $jdl_rest_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.jdl_achievements'),
      $container->get('current_user'),
      $container->get('jdl.rest_helper')

    );
  }

  /**
   * Builds the response.
   */
  public function build(Request $request) {
    $raw_data = $request->getContent();
    $raw_data = json_decode($raw_data);

    $data = $raw_data->data;
    $data = substr($data, 2);
    $data = json_decode(base64_decode($data));

    if (!$this->dataValidate($data)) {
      throw new BadRequestHttpException(t('Bad data.'));
    }

    $l = count($data);
    $difficulty = $data[$l - 2];
    $rating = $data[$l - 1];
    unset($data[$l - 1], $data[$l - 2]);

    $time = $data[count($data) - 4] * 0.01666666 + 0.01666666;

    $level = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['name' => $raw_data->level]);

    $level = reset($level);
    if (!$level) {
      throw new BadRequestHttpException(t('Level is unknown.'));
    }


    $existing_replay = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([
          'field_level' => $level->id(),
          'uid' => $this->user->id(),
          'field_difficulty' => $difficulty,
        ]
      );


    if ($existing_replay) {
      $replay = reset($existing_replay);
      $this->replayUpdate($replay, $difficulty, $time, $data, $level, $rating);
    }
    else {
      $this->replayCreate($difficulty, $time, $data, $level, $rating);
    }


    $achievements = $level->field_achievements->referencedEntities();
    foreach ($achievements as $achievement) {
      $this->applyAchievement($this->user, $achievement);
    }

    $this->updateAvailableLevels($level);
    $this->user->save();

    $data = $this->restHelper->loadPlayerData();
    return new JsonResponse($data, 200);
  }

  private function dataValidate($data) {
    return TRUE;
  }

  /**
   * Performs needed actions with achievement.
   *
   * @param $user
   * @param \Drupal\taxonomy\TermInterface $achievement
   */
  private function applyAchievement($user, TermInterface $achievement) {
    // Making sure user don't have that achievement.
    if ($user->field_achievements) {
      foreach ($user->field_achievements as $user_achievement) {
        if ($user_achievement->target_id == $achievement->id()) {
          return;
        }
      }
    }

    $plugin_id = $achievement->field_plugin_id->value;
    $achievements_manager = \Drupal::service('plugin.manager.jdl_achievements');
    $achievement_plugin = $achievements_manager->createInstance($plugin_id);
    $completed = $achievement_plugin->check(\Drupal::currentUser());

    if ($completed) {
      $completed_achievements[] = $plugin_id;

      if ($user->field_achievements) {
        $user->field_achievements->appendItem($achievement->id());
      }
      else {
        $user->field_achievements->target_id = $achievement->id();
      }
    }
  }

  private function replayUpdate(NodeInterface $replay, $difficulty, $time, $data, $level, $rating) {
    $replay->set('field_time', $time);
    $replay->set('field_data', json_encode($data));
    $replay->set('field_rating', $rating);

    $replay->save();
  }

  private function replayCreate($difficulty, $time, $data, TermInterface $level, $rating) {
    $replay = Node::create([
      'type' => REPLAY_CT_ID,
      'title' => $level->getName() . ' ' . DIFF_NAMES[$difficulty],
      'field_difficulty' => $difficulty,
      'field_time' => $time,
      'field_data' => json_encode($data),
      'field_level' => $level->id(),
      'field_rating' => $rating,
    ]);

    $replay->save();

    return $replay;
  }

  private function updateAvailableLevels(TermInterface $level) {
    $available_levels = $this->user->field_available_levels ? $this->user->field_available_levels->value : '[]';
    $available_levels = json_decode($available_levels);
    $this->user->set('field_current_level', $level->id());
    // Extracting next level.
    $next_level = $this->getNextLevel($level);
    if (!$next_level) {
      return;
    }

    if (!in_array($next_level->getName(), $available_levels)) {
      $available_levels[] = $next_level->getName();
      $this->user->set('field_available_levels', json_encode($available_levels));
    }

    $this->user->set('field_current_level', $level->id());
  }

  /**
   * @param \Drupal\taxonomy\TermInterface $level
   *
   * @return TermInterface|NULL
   */
  private function getNextLevel(TermInterface $level) {
    $connection = \Drupal::database();
    $query = $connection->select('taxonomy_term_field_data', 'levels');
    $query->condition('levels.vid', 'level');
    $query->condition('levels.weight', $level->getWeight(), '>');
    $query->orderBy('weight');
    $query->range(0, 1);
    $query->addField('levels', 'tid', 'tid');
    $query->addField('levels', 'weight', 'weight');

    $tid = $query->execute()->fetchField();

    if ($tid) {
      $level = Term::load($tid);
      return $level;
    }

    return NULL;
  }
}
