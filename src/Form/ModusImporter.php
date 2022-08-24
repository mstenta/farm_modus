<?php

namespace Drupal\farm_modus\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\farm_modus\ModusParserInterface;
use Drupal\farm_quick\Traits\QuickQuantityTrait;
use Drupal\log\Entity\Log;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for importing Modus data as lab test logs.
 */
class ModusImporter extends FormBase {

  use QuickQuantityTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The Modus parser service.
   *
   * @var \Drupal\farm_modus\ModusParserInterface
   */
  protected $modusParser;

  /**
   * Constructs a new KmlImporter object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\farm_modus\ModusParserInterface $modus_parser
   *   The Modus parser service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FileSystemInterface $file_system, ModusParserInterface $modus_parser) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->modusParser = $modus_parser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('modus.parser'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'farm_modus_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Input fieldset.
    $form['input'] = [
      '#type' => 'details',
      '#title' => $this->t('Input'),
      '#open' => TRUE,
    ];

    // Modus JSON file upload.
    $form['input']['file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Modus JSON File'),
      '#description' => $this->t('Upload your Modus JSON file here and click "Parse".'),
      '#upload_location' => 'private://modus',
      '#upload_validators' => [
        'file_validate_extensions' => ['json'],
      ],
      '#required' => TRUE,
    ];

    // Optionally specify a location asset to link the logs to.
    $form['input']['location'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Location'),
      '#description' => $this->t('Optionally associate logs with a location.'),
      '#target_type' => 'asset',
      '#selection_handler' => 'views',
      '#selection_settings' => [
        'view' => [
          'view_name' => 'farm_location_reference',
          'display_name' => 'entity_reference',
          'arguments' => [],
        ],
        'match_operator' => 'CONTAINS',
      ],
    ];

    // Parse button.
    $form['input']['parse'] = [
      '#type' => 'submit',
      '#value' => $this->t('Parse'),
      '#submit' => ['::parseModus'],
      '#ajax' => [
        'callback' => '::parseModusAjax',
        'wrapper' => 'output',
      ],
    ];

    // Create a wrapper container for output (to be replaced via Ajax).
    $form['output'] = [
      '#type' => 'container',
      '#prefix' => '<div id="output">',
      '#suffix' => '</div>',
    ];

    // Hidden field to track if the file was parsed. This helps with validation.
    $form['input']['parsed'] = [
      '#type' => 'hidden',
      '#value' => FALSE,
    ];

    // Only generate output if logs have been parsed.
    if (empty($form_state->getValue('parsed_logs'))) {
      return $form;
    }

    // Mark the form as parsed.
    $form['input']['parsed']['#value'] = TRUE;

    // Get the parsed logs from form state.
    $logs = $form_state->getValue('parsed_logs');

    // Display the output details.
    $form['output']['#type'] = 'details';
    $form['output']['#title'] = $this->t('Output');
    $form['output']['#open'] = TRUE;

    // Build a tree for editing draft logs.
    $form['output']['logs'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];
    foreach ($logs as $i => $log) {

      // Create a fieldset for the log.
      $form['output']['logs'][$i] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Log') . ' ' . ($i+ 1),
      ];

      // Store the raw log data from the Modus parser.
      $form['output']['logs'][$i]['log'] = [
        '#type' => 'value',
        '#value' => $log,
      ];

      // Log name.
      $form['output']['logs'][$i]['name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
        '#default_value' => $log['name'],
        '#required' => TRUE,
      ];

      // Timestamp.
      $form['output']['logs'][$i]['timestamp'] = [
        '#type' => 'date',
        '#title' => $this->t('Date'),
        '#date_year_range' => '-10:+3',
        '#default_value' => date('Y-m-d', $log['timestamp']),
        '#required' => TRUE,
      ];

      // Geometry.
      $form['output']['logs'][$i]['geometry'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Geometry'),
        '#default_value' => $log['geometry'],
      ];

      // Confirmation checkbox.
      $form['output']['logs'][$i]['confirm'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create this log'),
        '#description' => $this->t('Uncheck this if you do not want to create this log to be created.'),
        '#default_value' => TRUE,
      ];
    }

    // Submit button for creating the logs.
    $form['output']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create lab test logs'),
    ];

    return $form;
  }

  /**
   * Submit function for parsing an uploaded Modus JSON file.
   */
  public function parseModus(array &$form, FormStateInterface $form_state) {

    // Get the uploaded file contents (bail if empty).
    $file_ids = $form_state->getValue('file', []);
    if (empty($file_ids)) {
      return;
    }
    /** @var \Drupal\file\FileInterface $file */
    $file = $this->entityTypeManager->getStorage('file')->load(reset($file_ids));
    $path = $file->getFileUri();
    $json = file_get_contents($path);

    // Parse the Modus JSON data.
    $data = $this->modusParser->parseJson($json);

    // Bail if there is no data.
    if (empty($data)) {
      $this->messenger()->addWarning($this->t('Modus JSON data could not be parsed.'));
      return;
    }

    // Draft a set of logs from the Modus data.
    $logs = $this->modusParser->draftLogs($data);

    // Save the logs to form state.
    $form_state->setValue('parsed_logs', $logs);

    // Rebuild the form so that log fieldsets are generated.
    $form_state->setRebuild(TRUE);
  }

  /**
   * Ajax callback that returns the output fieldset after parsing Modus JSON.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return mixed
   *   The elements to replace.
   */
  public function parseModusAjax(array &$form, FormStateInterface $form_state) {
    return $form['output'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Only validate if the file has been parsed.
    if (!$form_state->getValue('parsed')) {
      return;
    }

    $logs = $form_state->getValue('logs', []);
    $confirmed_logs = array_filter($logs, function ($log) {
      return !empty($log['confirm']);
    });

    // Set an error if no logs are selected to be created.
    if (empty($confirmed_logs)) {
      $form_state->setErrorByName('submit', $this->t('At least one log must be created.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Bail if no file was uploaded.
    $file_ids = $form_state->getValue('file', []);
    if (empty($file_ids)) {
      $this->messenger()->addError($this->t('File upload failed.'));
      return;
    }

    // Load the logs to create.
    $logs = $form_state->getValue('logs', []);
    $confirmed_logs = array_filter($logs, function ($log) {
      return !empty($log['confirm']);
    });

    // Create new logs.
    foreach ($confirmed_logs as $log) {

      // If a location was specified, associate the log with it.
      if (!empty($form_state->getValue('location'))) {
        $log['log']['location'] = $form_state->getValue('location');
      }

      // Create the log object from values.
      $new_log = Log::create($log['log']);

      // Convert date to timestamp.
      $log['timestamp'] = strtotime($log['timestamp']);

      // Override values set by the user.
      $user_values = [
        'name',
        'timestamp',
        'geometry',
      ];
      foreach ($user_values as $user_value) {
        $new_log->{$user_value} = $log[$user_value];
      }

      // Build quantities.
      foreach ($log['log']['quantity'] as $qty) {
        $new_log->quantity[] = $this->createQuantity($qty);
      }

      // Set log revision message to indicate that it was created from a Modus
      // JSON import.
      $new_log->setNewRevision(TRUE);
      $new_log->setRevisionLogMessage($this->t('Generated by Modus JSON file importer.'));

      // Save the log and display a message with a link to it.
      $new_log->save();
      $log_url = $new_log->toUrl()->setAbsolute()->toString();
      $this->messenger()->addMessage($this->t('Created lab test log: <a href=":url">@log_label</a>', [':url' => $log_url, '@log_label' => $new_log->label()]));
    }
  }

}
