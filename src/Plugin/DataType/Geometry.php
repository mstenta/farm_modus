<?php

namespace Drupal\farm_modus\Plugin\DataType;

use Drupal\Core\TypedData\PrimitiveBase;

/**
 * GeoPHP geometry typed data.
 *
 * @DataType(
 *   id = "modus_geolocation",
 *   label = @Translation("Modus Geolocation"),
 * )
 */
class Geometry extends PrimitiveBase {

  /**
   * {@inheritdoc}
   */
  public function getCastedValue() {
    return $this->value->asText();
  }

}
