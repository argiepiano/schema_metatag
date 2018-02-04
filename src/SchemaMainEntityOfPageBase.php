<?php

/**
 * Schema.org MainEntityOfPage items should extend this class.
 */
class SchemaMainEntityOfPageBase extends SchemaNameBase {

  /**
   * {@inheritdoc}
   */
  public function getForm(array $options = []) {
    $form = parent::getForm($options);
    $form['value']['#attributes']['placeholder'] = '[current-page:url]';
    $form['value']['#description'] = $this->t('If this is the main content of the page, provide url of the page. Only one object on each page should be marked as the main entity of the page.');
    // Validation from parent::getForm() got wiped out, so add callback.
    $form['value']['#element_validate'][] = 'schema_metatag_element_validate';
    return $form;
  }

}
