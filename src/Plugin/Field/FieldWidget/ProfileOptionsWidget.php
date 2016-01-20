<?php

/**
 * @file
 * Contains \Drupal\profile\Plugin\Field\FieldWidget\ProfileOptionsWidget.
 */

namespace Drupal\profile\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;

/**
 * Plugin implementation of the 'profile_options' widget.
 *
 * @FieldWidget(
 *   id = "profile_options",
 *   label = @Translation("Profile Options list"),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */
class ProfileOptionsWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $multiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();
    $values = $items->getValue();
    if ($multiple) {
      $default_value = array_column($values, 'target_id');
      $type = 'checkboxes';
    }
    else {
      $default_value = !empty($values) ? $values[0]['target_id'] : NULL;
      $type = 'radios';
    }

    $element += array(
      '#type' => $type,
      '#options' => $this->getOptions($items->getEntity()),
      '#default_value' => $default_value,
      '#multiple' => $multiple,
      '#attached' => [
        'library' => [
          'profile/drupal.profile-options-widget',
        ],
      ],
      '#attributes' => [
        'class' => [
          'profile-options-widget'
        ],
      ],
    );

    return $element;
  }

  /**
   * Returns the array of options for the widget.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity for which to return options.
   *
   * @return array
   *   The array of options for the widget.
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    if (!isset($this->options)) {
      $options = [];

      // Limit the settable options for the current user account.
      $handler_settings = $this->fieldDefinition->getSetting('handler_settings');
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('profile');
      $renderer = \Drupal::service('renderer');
      foreach ($handler_settings['target_bundles'] as $bundle) {
        $profiles = \Drupal::entityTypeManager()->getStorage('profile')->loadMultipleByUser(\Drupal::currentUser(), $bundle);
        foreach ($profiles as $profile) {
          $renderable = $view_builder->view($profile, 'profile.profile-options-widget');
          $options[$profile->id()] = $renderer->render($renderable);
        }
      }

      // Add an empty option if the widget needs one.
      if ($empty_label = $this->getEmptyLabel()) {
        $options = ['_none' => $empty_label] + $options;
      }

      $module_handler = \Drupal::moduleHandler();
      $context = array(
        'fieldDefinition' => $this->fieldDefinition,
        'entity' => $entity,
      );
      $module_handler->alter('options_list', $options, $context);

      // Options might be nested ("optgroups"). If the widget does not support
      // nested options, flatten the list.
      if (!$this->supportsGroups()) {
        $options = OptGroup::flattenOptions($options);
      }

      $this->options = $options;
    }
    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  protected function supportsGroups() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyLabel() {
    // Add a 'none' option for non-required fields, and a 'select a value'
    // option for required fields that do not come with a value selected.
    if (!$this->required) {
      return t('- None -');
    }
  }

}
