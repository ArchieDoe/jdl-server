<?php

namespace Drupal\jdl_rest_processor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jdl_rest_processor\JdlRestHelper;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for JDL REST Processor routes.
 */
class Player extends ControllerBase {

  /**
   * @var \Drupal\jdl_rest_processor\JdlRestHelper
   */
  protected $restHelper;

  /**
   * The controller constructor.
   *
   * @param \Drupal\jdl_rest_processor\JdlRestHelper $rest_helper
   */
  public function __construct(JdlRestHelper $jdl_rest_helper) {
    $this->restHelper = $jdl_rest_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('jdl.rest_helper')
    );
  }

  /**
   * Builds the response.
   */
  public function load() {
    $data = $this->restHelper->loadPlayerData();

    return new JsonResponse($data, 200);
  }
}
