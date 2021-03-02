<?php

namespace Drupal\jdl_achievments;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * JdlAchievments plugin manager.
 */
class JdlAchievmentsPluginManager extends DefaultPluginManager {

  /**
   * Constructs JdlAchievmentsPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/JdlAchievments',
      $namespaces,
      $module_handler,
      'Drupal\jdl_achievments\JdlAchievmentsInterface',
      'Drupal\jdl_achievments\Annotation\JdlAchievments'
    );
    $this->alterInfo('jdl_achievments_info');
    $this->setCacheBackend($cache_backend, 'jdl_achievments_plugins');
  }

}
