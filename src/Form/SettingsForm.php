<?php

namespace Drupal\nagios\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nagios_settings';
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
    $group = 'modules';
    $config = $this->config('nagios.settings');

    $form['nagios_ua'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Unique ID'),
      '#default_value' => $config->get('nagios.ua'),
      '#description' => $this->t('Restrict sending information to requests identified by this Unique ID. You should change this to some unique string for your organization, and configure Nagios accordingly. This makes Nagios data less accessible to curious users. See the README.txt for more details.')
    );

    $form['nagios_show_outdated_names'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show outdated module/theme name?'),
      '#default_value' => $config->get('nagios.show_outdated_names'),
    );

    $form['nagios_status_page'] = array(
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#title' => t('Status page settings'),
      '#description' => t('Control the availability and location of the HTTP status page. NOTE: you must clear the menu cache for changes to these settings to register.'),
    );
    $form['nagios_status_page']['nagios_enable_status_page'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable status page?'),
      '#default_value' => $config->get('nagios.statuspage.enabled'),
    );
    $form['nagios_status_page']['nagios_page_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Nagios page path'),
      '#description' => t('Enter the path for the Nagios HTTP status page. It must be a valid Drupal path.'),
      '#default_value' => $config->get('nagios.statuspage.path'),
    );
    $form['nagios_status_page']['nagios_page_controller'] = array(
      '#type' => 'textfield',
      '#title' => t('Nagios page controller'),
      '#description' => t('Enter the name of the controller and function to be used by the Nagios status page. Take care and be sure this function exists before clearing the menu cache!'),
      '#default_value' => $config->get('nagios.statuspage.controller'),
    );
    $form['nagios_status_page']['nagios_enable_status_page_get'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable Unique ID checking via URL on status page?'),
      '#default_value' => $config->get('nagios.statuspage.getparam'),
      '#description' => t('If enabled the $_GET variable "unique_id" is used for checking the correct Unique ID instead of "User Agent" ($_SERVER[\'HTTP_USER_AGENT\']). This alternative checking is only working if the URL is containing the value like "/nagios?unique_id=*****". This feature is useful to avoid webserver stats with the Unique ID as "User Agent" and helpful for human testing.'),
    );

    $form['nagios_error_levels'] = array(
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#title' => t('Error levels'),
      '#description' => t('Set the values to be used for error levels when reporting to Nagios.'),
    );
    $form['nagios_error_levels']['nagios_status_ok_value'] = array(
      '#type' => 'textfield',
      '#title' => t('Status OK'),
      '#description' => t('The value to send to Nagios for a Status OK message.'),
      '#default_value' => $config->get('nagios.status.ok'),
    );
    $form['nagios_error_levels']['nagios_status_warning_value'] = array(
      '#type' => 'textfield',
      '#title' => t('Warning'),
      '#description' => t('The value to send to Nagios for a Warning message.'),
      '#default_value' => $config->get('nagios.status.warning'),
    );
    $form['nagios_error_levels']['nagios_status_critical_value'] = array(
      '#type' => 'textfield',
      '#title' => t('Critical'),
      '#description' => t('The value to send to Nagios for a Critical message.'),
      '#default_value' => $config->get('nagios.status.critical'),
    );
    $form['nagios_error_levels']['nagios_status_unknown_value'] = array(
      '#type' => 'textfield',
      '#title' => t('Unknown'),
      '#description' => t('The value to send to Nagios for an Unknown message.'),
      '#default_value' => $config->get('nagios.status.unknown')
    );

    $form[$group] = array(
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#title' => t('Modules'),
      '#description' => t('Select the modules that should report their data into Nagios.'),
    );

    foreach (nagios_invoke_all('nagios_info') as $module => $data) {
      $form[$group]['nagios_enable_' . $module] = array(
        '#type' => 'checkbox',
        '#title' => $data['name'] . ' (' . $module . ')',
        '#default_value' => $config->get('nagios.enable.' . $module) !== 0,
      );
    }

    $form['watchdog'] = array(
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#title' => t('Watchdog Settings'),
      '#description' => t('Controls how watchdog messages are retreived and displayed when watchdog checking is set.'),
    );
    $form['watchdog']['limit_watchdog_display'] = array(
      '#type' => 'checkbox',
      '#title' => 'Limit watchdog display',
      '#default_value' => $config->get('nagios.limit_watchdog.display'),
      '#description' => t('Limit watchdog messages to only those that are new since the last check.'),
    );
    $form['watchdog']['limit_watchdog_results'] = array(
      '#type' => 'textfield',
      '#title' => 'Limit watchdog logs',
      '#default_value' => $config->get('nagios.limit_watchdog.results'),
      '#description' => t('Limit the number of watchdog logs that are checked. E.G. 50 will only check the newest 50 logs.'),
    );

    foreach (nagios_invoke_all('nagios_settings') as $module => $module_settings) {
      $form[$module] = array(
        '#type' => 'fieldset',
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#title' => $module,
      );

      foreach ($module_settings as $element => $data) {
        $form[$module][$element] = $data;
        
        // set #defaultvalue from #configname for first level form elements
        if (!isset($data['#default_value']) && isset($data['#configname'])) {
          $form[$module][$element]['#default_value'] = $config->get($module . '.' . $data['#configname']);
        }
        
        // set #defaultvalue from #configname for second level form elements
        if (isset($data['#type']) && $data['#type'] == 'fieldset') {
          foreach($data as $fieldsetelement => $fieldsetdata) {
            if (is_array($fieldsetdata)) {
              if (!isset($fieldsetdata['#default_value']) && isset($fieldsetdata['#configname'])) {
                $form[$module][$element][$fieldsetelement]['#default_value'] = $config->get($module . '.' . $fieldsetdata['#configname']);
              }
            }
          }
        }        
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('nagios.settings');
    $config->set('nagios.ua', $form_state->getValue('nagios_ua'));
    $config->set('nagios.show_outdated_names', $form_state->getValue('nagios_show_outdated_names'));
    $config->set('nagios.statuspage.enabled', $form_state->getValue('nagios_enable_status_page'));
    $config->set('nagios.statuspage.path', $form_state->getValue('nagios_page_path'));
    $config->set('nagios.statuspage.controller', $form_state->getValue('nagios_page_controller'));
    $config->set('nagios.statuspage.getparam', $form_state->getValue('nagios_enable_status_page_get'));
    $config->set('nagios.status.ok', $form_state->getValue('nagios_status_ok_value'));
    $config->set('nagios.status.warning', $form_state->getValue('nagios_status_warning_value'));
    $config->set('nagios.status.critical', $form_state->getValue('nagios_status_critical_value'));
    $config->set('nagios.status.unknown', $form_state->getValue('nagios_status_unknown_value'));
    
    foreach (nagios_invoke_all('nagios_info') as $module => $data) {
      $config->set('nagios.enable.' . $module, $form_state->getValue('nagios_enable_' . $module));
    }
    
    $config->set('nagios.limit_watchdog.display', $form_state->getValue('limit_watchdog_display'));
    $config->set('nagios.limit_watchdog.results', $form_state->getValue('limit_watchdog_results'));
    
    foreach (nagios_invoke_all('nagios_settings') as $module => $module_settings) {
      foreach ($module_settings as $element => $data) {       
        // save config for first level form elements
        if (isset($data['#configname'])) {
          $config->set($module . '.' . $data['#configname'], $form_state->getValue($element, $config->get($module . '.' . $data['#configname'])));
        }
        
        // save config for second level form elements
        if (isset($data['#type']) && $data['#type'] == 'fieldset') {
          foreach($data as $fieldsetelement => $fieldsetdata) {
            if (is_array($fieldsetdata)) {
              if (!isset($fieldsetdata['#default_value']) && isset($fieldsetdata['#configname'])) {
                $config->set($module . '.' . $fieldsetdata['#configname'], $form_state->getValue($fieldsetelement, $config->get($module . '.' . $fieldsetdata['#configname'])));
              }
            }
          }
        }        
      }
    }
    
    $config->save();

    parent::submitForm($form, $form_state);
  }
}
