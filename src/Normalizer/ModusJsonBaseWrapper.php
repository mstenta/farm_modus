<?php

namespace Drupal\farm_modus\Normalizer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

/**
 * Abstract base class for denormalizing modus json to a specified data type.
 */
abstract class ModusJsonBaseWrapper implements DenormalizerInterface, SerializerAwareInterface {

  use SerializerAwareTrait;

  /**
   * The supported format.
   */
  const FORMAT = 'vnd.modus.v1.modus-result+json';

  /**
   * {@inheritDoc}
   */
  public function denormalize($data, $type, $format = NULL, array $context = []) {

    // Build an array of results.
    $results = [];
    foreach ($data['Events'] ?? [] as $modus_event) {

      // Defer to normalizer based on event type.
      foreach ($modus_event['EventMetaData']['EventType'] ?? [] as $event_type_id => $included) {

        // Only normalize event types that are included.
        if (!$included) {
          continue;
        }

        $event_format = strtolower($event_type_id);
        $format = "vnd.modus.v1.modus-result.$event_format+json";

        // Denormalize based on the type and format.
        $result = $this->serializer->denormalize(
          $modus_event,
          static::TYPE,
          $format,
        );

        // Collect a single or array of results.
        if (is_array($result)) {
          $results += $result;
        }
        else {
          $results[] = $result;
        }
      }
    }
    return $results;
  }

  /**
   * {@inheritDoc}
   */
  public function supportsDenormalization($data, $type, $format = NULL) {
    return $type === static::TYPE && $format === static::FORMAT;
  }

}
