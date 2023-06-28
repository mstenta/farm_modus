<?php

namespace Drupal\farm_modus\TypedData;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Base class for modus sample data definitions.
 */
abstract class ModusSampleDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritDoc}
   */
  public function getPropertyDefinitions() {
    $properties = [];
    $properties['id'] = DataDefinition::create('string');
    $properties['labid'] = DataDefinition::create('string');
    $properties['fmisid'] = DataDefinition::create('string');
    $properties['collection_date'] = DataDefinition::create('datetime_iso8601');

    $properties['geolocation'] = DataDefinition::create('modus_geolocation');

    return $properties;
  }

}
