<?php

namespace Drupal\farm_modus;

use Drupal\Component\Serialization\Json;

/**
 * Provides a service class for parsing Modus JSON.
 */
class ModusParser implements ModusParserInterface {

  /**
   * {@inheritdoc}
   */
  public function parseJson(string $json): array {

    // Decode the JSON data.
    $data = Json::decode($json);

    // Bail if empty.
    if (empty($data)) {
      return [];
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function draftLogs(array $data): array {
    $logs = [];

    // Iterate through Modus Events (bail if none).
    if (!(!empty($data['Events']) && is_array($data['Events']))) {
      return $logs;
    }
    foreach ($data['Events'] as $modus_event) {

      // Skip if metadata is unavailable.
      if (empty($modus_event['LabMetaData']) || empty($modus_event['EventMetaData'])) {
        continue;
      }

      // Get report file descriptions, indexed by ReportID (skip if empty).
      $reports = [];
      foreach ($modus_event['LabMetaData']['Reports'] as $report) {
        $reports[$report['ReportID']] = $report['FileDescription'];
      }

      // Parse the event date to a timestamp (skip if unavailable).
      if (empty($modus_event['EventMetaData']['EventDate'])) {
        continue;
      }
      $timestamp = strtotime($modus_event['EventMetaData']['EventDate']);

      // Validate that this event contains soil samples (skip otherwise).
      if (!(!empty($modus_event['EventMetaData']['EventType']) && !empty($modus_event['EventMetaData']['EventType']['Soil']))) {
        continue;
      }

      // Get soil sample depth references, indexed by DepthID (skip if empty).
      if (empty($modus_event['EventSamples']['Soil']['DepthRefs'])) {
        continue;
      }
      $depths = [];
      foreach ($modus_event['EventSamples']['Soil']['DepthRefs'] as $depth) {
        $depths[$depth['DepthID']] = $depth;
      }

      // Iterate through soil samples.
      foreach ($modus_event['EventSamples']['Soil']['SoilSamples'] as $sample) {

        // Concatenate the report file description and FMIS sample ID
        // (if available) to generate a log name.
        $log_name_parts = [];
        if (!empty($sample['SampleMetaData']['ReportID']) && array_key_exists($sample['SampleMetaData']['ReportID'], $reports)) {
          $log_name_parts[] = $reports[$sample['SampleMetaData']['ReportID']];
        }
        if (!empty($sample['SampleMetaData']['FMISSampleID'])) {
          $log_name_parts[] = $sample['SampleMetaData']['FMISSampleID'];
        }
        $log_name = implode(' ', $log_name_parts);

        // Get WKT geometry, if available.
        $geometry = $sample['SampleMetaData']['Geometry']['wkt'] ?? '';

        // Iterate through the sample depths.
        foreach ($sample['Depths'] as $depth_sample) {

          // Start an array of quantities for the log.
          $quantities = [];

          // Add starting and ending depths as the first two quantities.
          $quantities[] = [
            'label' => 'Starting depth',
            'value' => $depths[$depth_sample['DepthID']]['StartingDepth'],
            'units' => $depths[$depth_sample['DepthID']]['DepthUnit'],
          ];
          $quantities[] = [
            'label' => 'Ending depth',
            'value' => $depths[$depth_sample['DepthID']]['EndingDepth'],
            'units' => $depths[$depth_sample['DepthID']]['DepthUnit'],
          ];

          // Iterate through the nutrient results and add a quantity for each
          // (skip if empty).
          if (empty($depth_sample['NutrientResults'])) {
            continue;
          }
          foreach ($depth_sample['NutrientResults'] as $nutrient_result) {

            // Round the value to 5 decimal places.
            $value = round($nutrient_result['Value'], 5);

            // Add a quantity.
            $quantities[] = [
              'label' => $nutrient_result['Element'],
              'value' => $value,
              'units' => $nutrient_result['ValueUnit'],
            ];
          }

          // Assemble a draft log.
          $logs[] = [
            'type' => 'lab_test',
            'lab_test_type' => 'soil',
            'name' => $log_name,
            'timestamp' => $timestamp,
            'quantity' => $quantities,
            'geometry' => $geometry,
            'status' => 'done',
          ];
        }
      }
    }

    return $logs;
  }

}
