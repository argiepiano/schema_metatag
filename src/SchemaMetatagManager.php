<?php

/**
 * @file
 * A generic substitution for Drupal 8 Random utility.
 */
class Random {

  /**
   * return a random string of a given length.
   */
  public function name($length, $other) {
    return $this->string($length, $other);
  }

  /**
   * return a random string of a given length.
   */
  public function string($length, $other) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randstring = '';
    for ($i = 0; $i < $length; $i++) {
      $randstring .= $characters[rand(0, (strlen($characters) - 1))];
    }
    return $randstring;
  }

}

/**
 * Class SchemaMetatagManager.
 *
 * @package Drupal\schema_metatag
 */
class SchemaMetatagManager implements SchemaMetatagManagerInterface {

  /**
   * {@inheritdoc}
   */
  public static function parseJsonld(&$elements) {
    $schema_metatags = [];
    foreach ($elements as $key => $info) {
      if (!empty($info['#attributes']['schema_metatag'])) {
        // Nest tags by group.
        $group = $info['#attributes']['group'];
        $name = $info['#attributes']['name'];
        $value = $info['#attributes']['content'];
        $schema_metatags[$group][$name] = $value;
        // Remove this tag from the elements array.
        unset($elements[$key]);
      }
    }
    $items = [];
    foreach ($schema_metatags as $data) {
      if (empty($items)) {
        $items['@context'] = 'http://schema.org';
      }
      $items['@graph'][] = $data;
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public static function encodeJsonld($items) {
    // If some group has been found, render the JSON LD,
    // otherwise return nothing.
    if (!empty($items)) {
      return json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    }
    else {
      return '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function renderArrayJsonLd($jsonld) {
    return [
      '#type' => 'html_tag',
      '#tag' => 'script',
      '#value' => $jsonld,
      '#attributes' => ['type' => 'application/ld+json'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getRenderedJsonld($entity = NULL, $entity_type = NULL) {
    // If nothing was passed in, assume the current entity.
    // @see schema_metatag_entity_load() to understand why this works.
    if (empty($entity)) {
      $entity_type = $entity->entity_type;
      $entity = menu_get_object($entity_type);
    }
    // Get all the metatags for this entity.
    $elements = metatag_generate_entity_metatags($entity, $entity_type);
    // Parse the Schema.org metatags out of the array.
    if ($items = self::parseJsonld($elements)) {
      // Encode the Schema.org metatags as JSON LD.
      if ($jsonld = self::encodeJsonld($items)) {
        // Pass back the rendered result.
        return drupal_render(self::renderArrayJsonLd($jsonld));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function pivot($content) {
    // Figure out the maximum number of items to include in the pivot.
    // Nested associative arrays should be excluded, only count numeric arrays.
    $count = max(array_map('self::countNumericKeys', $content));
    $pivoted = [];
    for ($i = 0; $i < $count; $i++) {
      foreach ($content as $key => $item) {
        // Some properties, like @type, may need to repeat the first item,
        // others may have too few values to fill out the array.
        // Make sure all properties have the right number of values.
        if (is_string($item) || (!is_string($item) && count($item) < $count)) {
          $content[$key] = [];
          $prev = '';
          for ($x = 0; $x < $count; $x++) {
            if (!is_string($item) && count($item) > $x) {
              $content[$key][$x] = $item[$x];
              $prev = $item[$x];
            } elseif (!is_string($item)) {
              $content[$key][$x] = $prev;
            } else {
              $content[$key][$x] = $item;
            }
          }
        }
        $pivoted[$i][$key] = $content[$key][$i];
      }
    }
    return $pivoted;
  }

  /**
   * If the item is an array with numeric keys, count the keys.
   */
  public static function countNumericKeys($item) {
    if (!is_array($item)) {
      return FALSE;
    }
    foreach (array_keys($item) as $key) {
      if (!is_numeric($key)) {
        return FALSE;
      }
    }
    return count($item);
  }

  /**
   * {@inheritdoc}
   */
  public static function explode($value) {
    $value = explode(',', $value);
    $value = array_map('trim', $value);
    $value = array_unique($value);
    if (count($value) == 1) {
      return $value[0];
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public static function serialize($value) {
    // Make sure the same value isn't serialized more than once if this is
    // called multiple times.
    if (is_array($value)) {
      // Don't serialize an empty array.
      // Otherwise Metatag won't know the field is empty.
      $trimmed = self::arrayTrim($value);
      if (empty($trimmed)) {
        return '';
      }
      else {
        $value = serialize($value);
      }
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public static function unserialize($value) {
    // Make sure the the value is not just a plain string and that
    // the same value isn't unserialized more than once if this is called
    // multiple times.
    if (self::isSerialized($value)) {
      // Fix problems created if token replacements are a different size
      // than the original tokens.
      $value = self::recomputeSerializedLength($value);
      $value = self::arrayTrim(unserialize($value));
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public static function isSerialized($value) {
    // If it isn't a string, it isn't serialized.
    if (!is_string($value)) {
      return FALSE;
    }
    $data = trim($value);
    if ('N' == $value) {
      return TRUE;
    }
    if (!preg_match('/^([adObis]):/', $value, $badions)) {
      return FALSE;
    }
    switch ($badions[1]) {
      case 'a':
      case 'O':
      case 's':
        if (preg_match("/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $value)) {
          return TRUE;
        }
        break;

      case 'b':
      case 'i':
      case 'd':
        if (preg_match("/^{$badions[1]}:[0-9.E-]+;\$/", $value)) {
          return TRUE;
        }
        break;

    }
    return FALSE;
  }

  /**
   * Not used, test to remove empty element from array.
   */
  public static function test($input) {
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveCallbackFilterIterator(
        new \RecursiveArrayIterator($input),
        function ($value) {
          return trim($value) !== NULL && trim($value) !== '';
        }
      ), \RecursiveIteratorIterator::CHILD_FIRST
    );
    $result = $iterator->getArrayCopy();
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public static function arrayTrim($input) {
    return is_array($input) ? array_filter($input,
      function (& $value) {
        return $value = self::arrayTrim($value);
      }
    ) : $input;
  }

  /**
   * {@inheritdoc}
   */
  public static function recomputeSerializedLength($value) {
    $value = preg_replace_callback('!s:(\d+):"(.*?)";!', function ($match) {
      return ($match[1] == strlen($match[2])) ? $match[0] : 's:' . strlen($match[2]) . ':"' . $match[2] . '";';
    }, $value);
    return $value;
  }

  /**
   * Generates a pseudo-random string of ASCII characters of codes 32 to 126.
   *
   * @param int $length
   *   Length of random string to generate.
   *
   * @return string
   *   Pseudo-randomly generated unique string including special characters.
   */
  public static function randomString($length = 8) {
    $randomGenerator = new Random();
    if ($length < 4) {
      return $randomGenerator->string($length, TRUE);
    }
    // Swap special characters into the string.
    $replacement_pos = floor($length / 2);
    $string = $randomGenerator->string($length - 2, TRUE);
    return substr_replace($string, '>&', $replacement_pos, 0);
  }

  /**
   * Generates a unique random string containing letters and numbers.
   *
   * @param int $length
   *   Length of random string to generate.
   *
   * @return string
   *   Randomly generated unique string.
   */
  public static function randomMachineName($length = 8) {
    $randomGenerator = new Random();
    return $randomGenerator->name($length, TRUE);
  }

}
