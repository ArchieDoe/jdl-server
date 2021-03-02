<?php

namespace Drupal\jdl_achievments;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for jdl_achievments plugins.
 */
abstract class JdlAchievmentsPluginBase extends PluginBase implements JdlAchievmentsInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

}
