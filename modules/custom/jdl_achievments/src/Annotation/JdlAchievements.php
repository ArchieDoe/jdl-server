<?php

namespace Drupal\jdl_achievements\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines jdl_achievements annotation object.
 *
 * @Annotation
 */
class JdlAchievements extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
