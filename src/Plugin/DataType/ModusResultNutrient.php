<?php

namespace Drupal\farm_modus\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * Modus nutrient result data type.
 *
 * @DataType(
 *   id = "modus_result:nutrient",
 *   label = @Translation("Modus Result: Nutrient"),
 *   definition_class = "\Drupal\farm_modus\TypedData\ModusResultNutrientDefinition"
 * )
 */
class ModusResultNutrient extends Map {

}
