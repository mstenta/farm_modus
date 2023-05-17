<?php

namespace Drupal\farm_modus\Normalizer;

use Drupal\farm_modus\TypedData\ModusSlimBaseDefinition;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

/**
 * Normalized modus JSON into modus events.
 */
class ModusJsonWrapper implements DenormalizerInterface, SerializerAwareInterface {

  use SerializerAwareTrait;

  /**
   * The supported format.
   */
  const FORMAT = 'vnd.modus.v1.modus-result+json';

  /**
   * The supported type to (de)normalize to.
   */
  const TYPE = ModusSlimBaseDefinition::class;

  /**
   * {@inheritDoc}
   */
  public function denormalize($data, $type, $format = NULL, array $context = []) {

    // Build an array of events.
    $events = [];
    foreach ($data['Events'] ?? [] as $modus_event) {

      // Defer to normalizer based on event type.
      foreach ($modus_event['EventMetaData']['EventType'] ?? [] as $event_type_id => $included) {

        // Only normalize event types that are included.
        if (!$included) {
          continue;
        }

        $event_format = strtolower($event_type_id);
        $format = "vnd.modus.v1.modus-result.$event_format+json";

        // Denormalize based on the format.
        $events[] = $this->serializer->denormalize(
          $modus_event,
          ModusSlimBaseDefinition::class,
          $format,
        );
      }
    }
    return $events;
  }

  /**
   * {@inheritDoc}
   */
  public function supportsDenormalization($data, $type, $format = NULL) {
    return $type === static::TYPE && $format === static::FORMAT;
  }

}
