<?php

namespace Drupal\farm_modus_soil\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * Modus slim soil data type.
 *
 * @DataType(
 *   id = "modus_slim:soil",
 *   label = @Translation("Modus Slim: Soil"),
 *   definition_class = "\Drupal\farm_modus_soil\TypedData\ModusSlimSoilDefinition"
 * )
 */
class ModusSlimSoil extends Map {

}
