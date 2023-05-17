<?php

namespace Drupal\farm_modus\TypedData;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Base data definition for Modus slim.
 *
 * @see https://github.com/OADA/formats/blob/modus-slim/schemas/modus/slim/modus-result.schema.cts
 */
abstract class ModusSlimBaseDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritDoc}
   */
  public function getPropertyDefinitions() {
    $properties = [];
    $properties['id'] = DataDefinition::create('string')
      ->setRequired(TRUE)
      ->addConstraint('Length', [
        'min' => 36,
        'max' => 36,
      ]);
    $properties['date'] = DataDefinition::create('datetime_iso8601')
      ->setRequired(TRUE);
    $properties['type'] = DataDefinition::create('string')
      ->setRequired(TRUE);

    // @todo Add lab metadata.
    $properties['lab'] = DataDefinition::create('string');

    // @todo Add FMIS metadata.
    $properties['fmis'] = DataDefinition::create('string');

    return $properties;
  }

}
