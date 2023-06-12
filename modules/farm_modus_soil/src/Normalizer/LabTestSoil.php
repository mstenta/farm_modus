<?php

namespace Drupal\farm_modus_soil\Normalizer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\farm_modus\TypedData\ModusSlimBaseDefinition;
use Drupal\log\Entity\Log;
use Drupal\quantity\Entity\Quantity;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

/**
 * Normalizes modus soil json into soil lab test logs.
 */
class LabTestSoil implements DenormalizerInterface, SerializerAwareInterface {

  use SerializerAwareTrait;

  /**
   * The supported format.
   */
  const FORMAT = 'vnd.modus.v1.modus-result.soil+json';

  /**
   * The supported type to (de)normalize to.
   */
  const TYPE = Log::class;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a LabTestSoil object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritDoc}
   */
  public function denormalize($data, $type, $format = NULL, array $context = []) {

    // Denormalize to modus slim typed data.
    $events[] = $this->serializer->denormalize(
      $data,
      ModusSlimBaseDefinition::class,
      $format,
    );

    // Create a log for each event.
    $logs = [];
    foreach ($events as $event) {
      $timestamp = $event->get('date')->getDateTime()->format('U');
      $default_log_data = [
        'type' => 'lab_test',
        'lab_test_type' => 'soil',
        'timestamp' => $timestamp,
        'status' => 'done',
      ];
      foreach ($event->get('samples') as $sample) {

        // Create log and quantities.
        $log = Log::create($default_log_data);
        $quantities = [];

        // Depths.
        $depth_units = $this->createOrLoadTerm($sample->get('depth')->get('units')->getValue(), 'units');
        $quantities[] = [
          'type' => 'standard',
          'label' => 'Starting Depth',
          'value' => $sample->get('depth')->get('top')->getValue(),
          'units' => $depth_units,
        ];
        $quantities[] = [
          'type' => 'standard',
          'label' => 'Ending Depth',
          'value' => $sample->get('depth')->get('bottom')->getValue(),
          'units' => $depth_units,
        ];

        // Results.
        foreach ($sample->get('results') as $result) {
          $quantities[] = [
            'type' => 'test',
            'label' => $result->get('analyte')->getValue(),
            'value' => $result->get('value')->getValue(),
            'units' => $this->createOrLoadTerm($result->get('units')->getValue(), 'units'),
          ];
        }

        /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $quantity_field */
        $quantity_field = $log->get('quantity');
        foreach ($quantities as $quantity) {
          $quantity_field->appendItem(Quantity::create($quantity));
        }

        $logs[] = $log;
      }
    }

    return $logs;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDenormalization($data, $type, $format = NULL) {
    return $type === static::TYPE && $format === static::FORMAT;
  }

  /**
   * Create a term.
   *
   * @param array $values
   *   An array of values to initialize the term with.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The term entity that was created.
   */
  protected function createTerm(array $values = []) {

    // Alias 'vocabulary' to 'vid'.
    if (!empty($values['vocabulary'])) {
      $values['vid'] = $values['vocabulary'];
    }

    // Start a new term entity with the provided values.
    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = Term::create($values);

    // Return the term entity.
    return $term;
  }

  /**
   * Given a term name, create or load a matching term entity.
   *
   * @param string $name
   *   The term name.
   * @param string $vocabulary
   *   The vocabulary to search or create in.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The term entity that was created or loaded.
   */
  protected function createOrLoadTerm(string $name, string $vocabulary) {

    // First try to load an existing term.
    $search = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['name' => $name, 'vid' => $vocabulary]);
    if (!empty($search)) {
      return reset($search);
    }

    // Create a new term.
    return $this->createTerm([
      'name' => $name,
      'vid' => $vocabulary,
    ]);
  }

}
