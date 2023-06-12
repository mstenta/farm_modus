<?php

namespace Drupal\Tests\farm_modus_soil\Kernel;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\KernelTests\KernelTestBase;
use Drupal\log\Entity\Log;

/**
 * Tests serialization of modus data.
 */
class ModusLogSerializationTest extends KernelTestBase {

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * Path to test data.
   *
   * @var string
   */
  protected string $dataPath;

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'asset',
    'entity',
    'entity_reference_revisions',
    'farm_entity',
    'farm_entity_fields',
    'farm_field',
    'farm_format',
    'farm_log',
    'farm_log_asset',
    'farm_log_quantity',
    'farm_lab_test',
    'farm_modus',
    'farm_modus_soil',
    'farm_quantity_standard',
    'farm_quantity_test',
    'file',
    'filter',
    'fraction',
    'geofield',
    'image',
    'log',
    'options',
    'quantity',
    'serialization',
    'state_machine',
    'system',
    'taxonomy',
    'text',
    'user',
  ];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->dataPath = $this->container->get('module_handler')
      ->getModule('farm_modus_soil')->getPath() . '/tests/data';

    $this->typedDataManager = $this->container->get('typed_data_manager');
    $this->serializer = $this->container->get('serializer');

    $this->installEntitySchema('taxonomy_term');
  }

  /**
   * Test deserializing modus v1 json to soil lab test logs.
   */
  public function testSoilLabTestDeserialization() {

    // Test data.
    $json = file_get_contents($this->dataPath . '/basic-modus-soil.json');
    $format = 'vnd.modus.v1.modus-result+json';

    /** @var \Drupal\log\Entity\LogInterface[] $logs */
    $logs = $this->serializer->deserialize($json, Log::class, $format);
    $this->assertCount(1, $logs, 'One lab test soil log is deserialized.');

    // Test log metadata.
    $log = reset($logs);
    $this->assertEquals('lab_test', $log->bundle());
    $this->assertEquals('soil', $log->get('lab_test_type')->value);
    $this->assertEquals('2021-09-24T00:00:00+00:00', DrupalDateTime::createFromTimestamp($log->get('timestamp')->value, 'UTC')->format('c'));
    $this->assertEquals('done', $log->get('status')->value);

    // @todo Test lab metadata.
    // @todo Include modus test ID in log.

    // Test quantities.
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $quantities */
    $quantities = $log->get('quantity');
    $this->assertCount(16, $quantities);

    // Test depths.
    $start = $quantities->get(0)->entity;
    $this->assertEquals('Starting Depth', $start->get('label')->value);
    $this->assertEquals('in', $start->get('units')->entity->get('name')->value);
    $this->assertEquals(0, $start->get('value')->decimal);
    $end = $quantities->get(1)->entity;
    $this->assertEquals('Ending Depth', $end->get('label')->value);
    $this->assertEquals('in', $end->get('units')->entity->get('name')->value);
    $this->assertEquals(8, $end->get('value')->decimal);

    // Test results.
    $result = $quantities->get(4)->entity;
    $this->assertEquals('P', $result->get('label')->value);
    $this->assertEquals('ppm', $result->get('units')->entity->get('name')->value);
    $this->assertEquals(34.0, $result->get('value')->decimal);
  }

}
