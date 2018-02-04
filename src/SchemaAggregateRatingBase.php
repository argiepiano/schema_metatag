<?php

/**
 * Provides a plugin to extend for the 'aggregateRating' meta tag.
 */
class SchemaAggregateRatingBase extends SchemaNameBase {

  use SchemaAggregateRatingTrait;

  /**
   * {@inheritdoc}
   */
  public function getForm(array $options = []) {
    $value = SchemaMetatagManager::unserialize($this->value());
    $input_values = [
      'title' => $this->label(),
      'description' => $this->description(),
      'value' => $value,
      '#required' => isset($element['#required']) ? $element['#required'] : FALSE,
      'visibility_selector' => $this->visibilitySelector() . '[@type]',
    ];

    $form = parent::getForm($options);
    $form['value'] = $this->aggregateRatingForm($input_values);
    // Validation from parent::getForm() got wiped out, so add callback.
    $form['value']['#element_validate'][] = 'schema_metatag_element_validate';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function testValue() {
    $items = [];
    $keys = ['@type', 'ratingValue', 'ratingCount', 'bestRating', 'worstRating'];
    foreach ($keys as $key) {
      switch ($key) {
        case '@type':
          $items[$key] = 'AggregateRating';
          break;

        default:
          $items[$key] = parent::testDefaultValue(2, ' ');
          break;

      }
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public static function outputValue($input_value) {
    return $input_value;
  }

}
