<?php

namespace Drupal\farm_modus;

/**
 * Provides an interface for Modus parser classes.
 */
interface ModusParserInterface {

  /**
   * Parse Modus JSON data.
   *
   * @param string $json
   *   The Modus data in JSON format.
   *
   * @return array
   *   Returns an array of Modus data.
   */
  public function parseJson(string $json): array;

  /**
   * Generate a set of draft logs from Modus data.
   *
   * @param array $data
   *   The Modus data (eg: parsed from JSON).
   *
   * @return array
   *   Returns an array of draft logs.
   */
  public function draftLogs(array $data): array;

}
