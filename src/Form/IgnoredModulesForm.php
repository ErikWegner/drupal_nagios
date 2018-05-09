<?php

namespace Drupal\nagios\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

class IgnoredModulesForm extends ConfigFormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a ModulesListForm object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    $this->moduleHandler = $module_handler;
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('module_handler'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nagios_ignored_modules';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'nagios.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('nagios.settings');
    $enabled = TRUE;

    $settings_url = Url::fromRoute('nagios.settings')->toString();
    // Is the nagios module itself disabled?
    if ($config->get('nagios.enable.nagios') === 0) {
      \Drupal::messenger()->addMessage(
        $this->t(
          'These settings are not available, because the nagios module is not enabled within the <a href="@nagios-settings">nagios settings</a>.', [
            '@nagios-settings' => $settings_url,
          ]
        ), 'warning');
      $enabled = FALSE;
    }

    // Is "Checking of hook_requirements." disabled?
    if ($config->get('nagios.function.requirements') === 0) {
      \Drupal::messenger()->addMessage(
        $this->t(
          'These settings are not available, because the requirements check is not enabled within the <a href="@nagios-settings">nagios settings</a>.', [
            '@nagios-settings' => $settings_url,
          ]
        ), 'warning');
      $enabled = FALSE;
    }

    $this->addDescription($form);
    $this->buildTable($form, $enabled);
    $form = parent::buildForm($form, $form_state);
    if (!$enabled) {
      $form['actions']['submit']['#disabled'] = TRUE;
    }

    return $form;
  }

  /**
   * Build the list of modules
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  protected function addDescription(array &$form) {
    $form['intro'] = [
      '#markup' => $this->t('Select those modules that should be ignored for requirement checks.'),
    ];
  }

  /**
   * Build the list of modules
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param boolean $enabled
   *   Enable the check boxes
   */
  protected function buildTable(array &$form, $enabled) {
    $config = $this->config('nagios.settings');

    $header = [
      'title' => $this->t('Title'),
      'description' => $this->t('Description'),
    ];

    $options = [];

    // Include system.admin.inc so we can use the sort callbacks.
    $this->moduleHandler->loadInclude('system', 'inc', 'system.admin');

    // Sort all modules by their names.
    $modules = system_rebuild_module_data();
    uasort($modules, 'system_sort_modules_by_info_name');

    // Build the rows
    foreach ($modules as $filename => $module) {
      if (empty($module->info['hidden'])) {
        $options[$filename] = $this->buildRow($module);
        $options[$filename]['#disabled'] = TRUE;
      }
    }

    // Set up the check boxes
    $defaults = [];
    $nagios_ignored_modules = $config->get('nagios.ignored_modules') ?: [];
    foreach ($nagios_ignored_modules as $ignored_module) {
      $defaults[$ignored_module] = 1;
    }

    $form['modules'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('No modules available.'),
      '#default_value' => $defaults,
    ];

    if (!$enabled) {
      foreach ($form['modules']['#options'] as $key => $value) {
        $form['modules']['#options']['#disabled'] = TRUE;
      }
    }
  }

  /**
   * Build one row in the list of modules
   *
   * @param Extension $module
   *  The module that the row is build for
   *
   * @return array
   *  The row data for the table select element
   */
  protected function buildRow(Extension $module) {
    $row = [];
    $row['title'] = $module->info['name'];
    $row['description'] = $this->t($module->info['description']);
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('nagios.settings');
    $ignored_modules = array_keys(array_filter($form_state->getValue('modules')));
    $config->set('nagios.ignored_modules', $ignored_modules);
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
