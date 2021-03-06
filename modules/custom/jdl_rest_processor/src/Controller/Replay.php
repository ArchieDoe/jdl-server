<?php

namespace Drupal\jdl_rest_processor\Controller;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\example\ExampleInterface;
use Drupal\node\NodeInterface;
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

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    PluginManagerInterface $plugin_manager_jdl_achievements,
    AccountInterface $current_user
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->pluginManagerJdlAchievements = $plugin_manager_jdl_achievements;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.jdl_achievements'),
      $container->get('current_user')
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


    $user = User::load($this->currentUser->id());

    $l = count($data);
    $difficulty = $data[$l - 1];
    unset($data[$l - 1]);

    $time = $data[count($data) - 4];

    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['name' => $raw_data->level]);

    $level = reset($terms);
    if (!$level) {
      throw new BadRequestHttpException(t('Level is unknown.'));
    }


    $existing_replay = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([
        'field_level' => $level->id(),
        'uid' => $user->id(),
        'field_difficulty' => $difficulty,
      ]
    );


    if ($existing_replay) {
      $replay = reset($existing_replay);
      $this->replayUpdate($replay, $difficulty, $time, $data, $level);
    }
    else {
      $this->replayCreate($difficulty, $time, $data, $level);
    }


    $achievements = $level->field_achievements->referencedEntities();

    $completed_achievements = [];
    foreach ($achievements as $achievement) {
        $this->applyAchievement($user, $achievement);
    }


    $result = new \stdClass();
    $result->achievements = $completed_achievements;
    return new JsonResponse($result, 200);
  }

  private function dataValidate($data) {
    return true;
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

      $user->save();
    }
  }

  private function replayUpdate(NodeInterface $replay, $difficulty, $time, $data, $level) {
    $replay->field_time = $time;
    $replay->field_data = json_encode($data);

    $replay->save();
  }

  private function replayCreate($difficulty, $time, $data, TermInterface $level) {
    $replay = Node::create([
      'type' => REPLAY_CT_ID,
      'title' => $level->getName() . ' ' . DIFF_NAMES[$difficulty],
      'field_difficulty' => $difficulty,
      'field_time' => $time * 0.016666,
      'field_data' => json_encode($data),
      'field_level' => $level->id(),
    ]);

    $replay->save();

    return $replay;
  }
}
