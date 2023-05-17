<?php

namespace Drupal\farm_modus\Encoder;

use Drupal\serialization\Encoder\JsonEncoder as SerializationJsonEncoder;

/**
 * Encodes modus json data.
 */
class JsonEncoder extends SerializationJsonEncoder {

  /**
   * {@inheritdoc}
   */
  protected static $format = ['vnd.modus.v1.modus-result+json'];

}
