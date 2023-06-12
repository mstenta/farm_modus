<?php

namespace Drupal\farm_modus\Normalizer;

use Drupal\farm_modus\TypedData\ModusSlimBaseDefinition;

/**
 * Normalizes modus JSON into modus slim objects.
 */
class ModusJsonSlimWrapper extends ModusJsonBaseWrapper {

  /**
   * The supported type to (de)normalize to.
   */
  const TYPE = ModusSlimBaseDefinition::class;

}
