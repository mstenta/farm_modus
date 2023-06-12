<?php

namespace Drupal\farm_modus_soil\TypedData;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\farm_modus\TypedData\ModusSampleDefinition;

/**
 * Data definition for modus soil sample.
 */
class ModusSampleSoilDefinition extends ModusSampleDefinition {

  /**
   * {@inheritdoc}
   */
  public static function create($type = 'modus_sample:soil') {
    $definition['type'] = $type;
    return new self($definition);
  }

  /**
   * {@inheritDoc}
   */
  public function getPropertyDefinitions() {
    $properties = parent::getPropertyDefinitions();

    // Sample depth.
    $properties['depth'] = MapDataDefinition::create()
      ->setPropertyDefinition(
        'id',
        DataDefinition::create('string')
      )
      ->setPropertyDefinition(
        'top',
        DataDefinition::create('integer')
      )
      ->setPropertyDefinition(
        'bottom',
        DataDefinition::create('integer')
      )
      ->setPropertyDefinition(
        'units',
        DataDefinition::create('string')
      );

    // List of nutrient results.
    $properties['results'] = ListDataDefinition::create('modus_result:nutrient')
      ->setRequired(TRUE);

    return $properties;
  }

}
