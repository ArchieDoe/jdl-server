<?php

namespace Drupal\jdl_rest_processor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\example\ExampleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for JDL REST Processor routes.
 */
class Replay extends ControllerBase {

  /**
   * The entity_type_manager service.
   *
   * @var \Drupal\example\ExampleInterface
   */
  protected $entityTypeManager;

  /**
   * The plugin.manager.jdl_achievments service.
   *
   * @var \Drupal\example\ExampleInterface
   */
  protected $pluginManagerJdlAchievments;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The sd service.
   *
   * @var \Drupal\example\ExampleInterface
   */
  protected $sd;

  /**
   * The controller constructor.
   *
   * @param \Drupal\example\ExampleInterface $entity_type_manager
   *   The entity_type_manager service.
   * @param \Drupal\example\ExampleInterface $plugin_manager_jdl_achievments
   *   The plugin.manager.jdl_achievments service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\example\ExampleInterface $sd
   *   The sd service.
   */
  public function __construct(ExampleInterface $entity_type_manager, ExampleInterface $plugin_manager_jdl_achievments, AccountInterface $current_user, ExampleInterface $sd) {
    $this->entityTypeManager = $entity_type_manager;
    $this->pluginManagerJdlAchievments = $plugin_manager_jdl_achievments;
    $this->currentUser = $current_user;
    $this->sd = $sd;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type_manager'),
      $container->get('plugin.manager.jdl_achievments'),
      $container->get('current_user'),
      $container->get('sd')
    );
  }

  /**
   * Builds the response.
   */
  public function build() {

    
  }

}
