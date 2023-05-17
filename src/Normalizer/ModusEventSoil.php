<?php

namespace Drupal\farm_modus\Normalizer;

use Drupal\farm_modus\TypedData\ModusSlimBaseDefinition;
use Drupal\farm_modus\TypedData\ModusSlimSoilDefinition;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Normalized modus JSON into modus events.
 */
class ModusEventSoil extends ModusEventBase implements DenormalizerInterface {

  /**
   * The supported format.
   */
  const FORMAT = 'vnd.modus.v1.modus-result.soil+json';

  /**
   * The supported type to (de)normalize to.
   */
  const TYPE = ModusSlimBaseDefinition::class;

  /**
   * {@inheritDoc}
   */
  public function denormalize($data, $type, $format = NULL, array $context = []) {

    // Build a modus slim object for each event.
    $modus_slim = [
      'samples' => [],
    ];

    // Skip if metadata is unavailable.
    if (empty($data['LabMetaData']) || empty($data['EventMetaData'])) {
      return;
    }

    // Get report file descriptions, indexed by ReportID (skip if empty).
    $reports = [];
    foreach ($data['LabMetaData']['Reports'] as $report) {
      $reports[$report['ReportID']] = $report['FileDescription'] ?? '';
    }

    // Check for required event metadata.
    // Date.
    if (empty($data['EventMetaData']['EventDate'])) {
      return;
    }
    $modus_slim['date'] = $data['EventMetaData']['EventDate'];

    // Optional event metadata.
    $modus_slim['id'] = $data['EventMetaData']['EventCode'] ?? NULL;

    // Collect depths.
    $depths = [];
    foreach ($data['EventSamples']['Soil']['DepthRefs'] as $depth) {
      $depths[$depth['DepthID']] = $depth;
    }

    // Build an array of samples.
    $samples = [];
    foreach ($data['EventSamples']['Soil']['SoilSamples'] as $sample) {

      $sample_object = [
        'type' => 'soil',
        'id' => $sample['SampleMetaData']['SampleNumber'] ?? NULL,
        'labid' => $sample['SampleMetaData']['ReportID'] ?? NULL,
        'fmisid' => $sample['SampleMetaData']['FMISSampleID'] ?? NULL,
        'geolocation' => $sample['SampleMetaData']['Geometry']['wkt'] ?? NULL,
        'collection_date' => $sample['SampleMetaData']['CollectionDate'] ?? NULL,
        'results' => [],
      ];

      // Iterate through the sample depths.
      foreach ($sample['Depths'] as $depth_sample) {

        $sample_object['depth'] = [
          'id'     => $depth_sample['DepthID'],
          'top'    => $depths[$depth_sample['DepthID']]['StartingDepth'],
          'bottom' => $depths[$depth_sample['DepthID']]['EndingDepth'],
          'units'  => $depths[$depth_sample['DepthID']]['DepthUnit'],
        ];

        // Iterate through the nutrient results and add a quantity for each
        // (skip if empty).
        if (empty($depth_sample['NutrientResults'])) {
          continue;
        }
        foreach ($depth_sample['NutrientResults'] as $nutrient_result) {

          // Round the value to 5 decimal places.
          $value = round($nutrient_result['Value'], 5);

          // Add a result.
          $sample_object['results'][] = [
            'analyte' => $nutrient_result['Element'],
            'value'   => $value,
            'units'   => $nutrient_result['ValueUnit'],
            // @todo Add modus_test_id.
          ];
        }
        $samples[] = $sample_object;
      }
    }

    // Gather samples and create soil slim definition.
    $modus_slim['samples'] = $samples;
    return $this->typedDataManager->create(ModusSlimSoilDefinition::create(), $modus_slim);
  }

}
