<?php

/**
 * All Schema.org itemListElement tags should extend this class.
 */
class SchemaItemListElementBase extends SchemaNameBase {

  /**
   * {@inheritdoc}
   */
  public function getForm(array $options = []) {
    $form = parent::getForm($options);
    $form['value']['#description'] = $this->t('To create a list, provide a token for a multiple value field, or a comma-separated list of values.');
    // Validation from parent::getForm() got wiped out, so add callback.
    $form['value']['#element_validate'][] = 'schema_metatag_element_validate';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function outputValue($input_value) {
    $items = [];
    $values = static::getItems($input_value);
    if (!empty($values) && is_array($values)) {
      foreach ($values as $key => $value) {
        // Complex arrays of values are displayed as ListItem objects, otherwise
        // values are presented in a simple list.
        if (is_array($value)) {
          $items[] = [
            '@type' => 'ListItem',
            'position' => $key,
            'item' => $value,
          ];
        }
        else {
          $items[] = $value;
        }
      }
    }
    return $items;
  }

  /**
   * Process the input value into an array of items.
   *
   * Each type of ItemList can extend this to process the input value into a
   * list of items. The default behavior will be a simple array from a
   * comma-separated list.
   */
  public static function getItems($input_value) {
    if (!is_array($input_value)) {
      $input_value = SchemaMetatagManager::explode($input_value);
    }
    return $input_value;
  }

  /**
   * {@inheritdoc}
   */
  public static function testValue() {
    return static::testDefaultValue(3, ',');
  }

}
