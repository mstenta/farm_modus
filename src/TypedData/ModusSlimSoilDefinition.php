<?php

namespace Drupal\farm_modus\TypedData;

use Drupal\Core\TypedData\ListDataDefinition;

/**
 * Data definition for Modus slim soil.
 */
class ModusSlimSoilDefinition extends ModusSlimBaseDefinition {

  /**
   * {@inheritdoc}
   */
  public static function create($type = 'modus_slim:soil') {
    $definition['type'] = $type;
    return new self($definition);
  }

  /**
   * {@inheritDoc}
   */
  public function getPropertyDefinitions() {
    $properties = parent::getPropertyDefinitions();

    // Add list of soil samples.
    $properties['samples'] = ListDataDefinition::create('modus_sample:soil')
      ->setRequired(TRUE);

    return $properties;
  }

}
