<?php

/**
 * @file
 * Contains \Drupal\profile\ProfileViewBuilder.
 */

namespace Drupal\profile;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Render\Element;

/**
 * Render controller for profile entities.
 */
class ProfileViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $defaults = parent::getBuildDefaults($entity, $view_mode);
    $defaults['#theme'] = 'profile';
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    parent::buildComponents($build, $entities, $displays, $view_mode);

    // Fake view mode user by profile-options-widget to hide labels.
    if ($view_mode == 'profile.profile-options-widget') {
      // Remove labels from display
      foreach ($build as &$display_build) {
        foreach (Element::children($display_build) as $field_name) {
          if (isset($display_build[$field_name]['#label_display'])) {
            $display_build[$field_name]['#label_display'] = 'hidden';
          }
        }
      }
    }
  }

}
