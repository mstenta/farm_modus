<?php

namespace Drupal\Tests\farm_modus_soil\Kernel;

use Drupal\farm_modus\TypedData\ModusResultNutrientDefinition;
use Drupal\farm_modus\TypedData\ModusSlimBaseDefinition;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests serialization of modus data.
 */
class ModusSlimSerializationTest extends KernelTestBase {

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
    'farm_modus',
    'farm_modus_soil',
    'serialization',
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
  }

  /**
   * Test deserializing modus v1 json to modus slim soil.
   */
  public function testDeserialization() {

    // Test data.
    $json = file_get_contents($this->dataPath . '/basic-modus-soil.json');
    $format = 'vnd.modus.v1.modus-result+json';

    /** @var \Drupal\farm_modus_soil\Plugin\DataType\ModusSlimSoil[] $events */
    $events = $this->serializer->deserialize($json, ModusSlimBaseDefinition::class, $format);
    $this->assertCount(1, $events, 'One modus event is deserialized.');

    // Test event metadata.
    $event = reset($events);
    $this->assertEquals('ece3a2a8-4340-48b1-ae1f-d48d1f1e1692', $event->get('id')->getValue());
    $this->assertEquals('2021-09-24T00:00:00+00:00', $event->get('date')->getDateTime()->format('c'));

    // @todo Test lab metadata.

    // Test samples.
    $samples = $event->get('samples');
    $this->assertCount(1, $samples);
    $sample = $samples->first();

    // Test sample results.
    $results = $sample->get('results');
    $this->assertCount(14, $results);

    // Test specific nutrient result.
    $test = $this->typedDataManager->create(ModusResultNutrientDefinition::create(), [
      'analyte' => 'P',
      'value' => 34.0,
      'units' => 'ppm',
    ]);
    $this->assertEquals($test->getValue(), $results[2]->getValue(), 'Has expected P result.');
  }

}
