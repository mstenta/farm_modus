<?php

namespace Drupal\farm_modus\Normalizer;

use Drupal\Core\TypedData\TypedDataManagerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Normalized modus JSON into modus events.
 *
 * @todo Add helper functions for denormalizing lab and event metadata.
 */
abstract class ModusEventBase implements DenormalizerInterface {

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * Constructs a ModusEventbase object.
   */
  public function __construct(TypedDataManagerInterface $typed_data_manager) {
    $this->typedDataManager = $typed_data_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDenormalization($data, $type, $format = NULL) {
    return $type === static::TYPE && $format === static::FORMAT;
  }

}
