<?php

namespace Drupal\farm_modus\Normalizer;

use Drupal\log\Entity\Log;

/**
 * Normalizes modus JSON into lab test logs.
 */
class ModusJsonLogWrapper extends ModusJsonBaseWrapper {

  /**
   * The supported type to (de)normalize to.
   */
  const TYPE = Log::class;

}
