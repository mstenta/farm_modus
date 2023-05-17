<?php

namespace Drupal\farm_modus\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * Modus soil sample data type.
 *
 * @DataType(
 *   id = "modus_sample:soil",
 *   label = @Translation("Modus Soil Sample"),
 *   definition_class = "\Drupal\farm_modus\TypedData\ModusSampleSoilDefinition"
 * )
 */
class ModusSampleSoil extends Map {

}
