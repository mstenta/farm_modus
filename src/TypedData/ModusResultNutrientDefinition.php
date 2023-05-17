<?php

namespace Drupal\farm_modus\TypedData;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Data definition for Modus Nutient Result.
 */
class ModusResultNutrientDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritDoc}
   */
  public static function create($type = 'modus_result:nutrient') {
    $definition['type'] = $type;
    return new self($definition);
  }

  /**
   * {@inheritDoc}
   */
  public function getPropertyDefinitions() {
    $properties = [];
    $properties['analyte'] = DataDefinition::create('string');
    $properties['value'] = DataDefinition::create('string');
    $properties['units'] = DataDefinition::create('string');
    $properties['modus_test_id'] = DataDefinition::create('string')
      ->addConstraint('NotNull');

    return $properties;
  }

}
