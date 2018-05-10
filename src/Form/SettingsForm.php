<?php

namespace Drupal\nagios\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

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

    $form['nagios_ua'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unique ID'),
      '#default_value' => $config->get('nagios.ua'),
      '#description' => $this->t('Restrict sending information to requests identified by this Unique ID. You should change this to some unique string for your organization, and configure Nagios accordingly. This makes Nagios data less accessible to curious users. See the README.txt for more details.'),
    ];

    $form['nagios_show_outdated_names'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show outdated module/theme name'),
      '#default_value' => $config->get('nagios.show_outdated_names'),
    ];

    $aUrlInfo = [
      ':url' => Url::fromRoute('system.performance_settings')
        ->toString(),
    ];
    $form['nagios_status_page'] = [
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#title' => $this->t('Status page settings'),
      '#description' => $this->t(
        'Control the availability and location of the HTTP status page. NOTE: you must clear the <a href=":url">menu cache</a> for changes to these settings to register.',
        $aUrlInfo),
    ];
    $form['nagios_status_page']['nagios_enable_status_page'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable status page'),
      '#default_value' => $config->get('nagios.statuspage.enabled'),
    ];
    $only_enabled_if_page = ['disabled' => ['#edit-nagios-enable-status-page' => ['checked' => FALSE]]];
    $form['nagios_status_page']['nagios_page_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nagios page path'),
      '#description' => $this->t('Enter the path for the Nagios HTTP status page. It must be a valid Drupal path.'),
      '#default_value' => $config->get('nagios.statuspage.path'),
      '#states' => $only_enabled_if_page,
    ];
    $form['nagios_status_page']['nagios_page_controller'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nagios page controller'),
      '#description' => $this->t('Enter the name of the controller and function to be used by the Nagios status page. Take care and be sure this function exists before clearing the menu cache!'),
      '#default_value' => $config->get('nagios.statuspage.controller'),
      '#states' => $only_enabled_if_page,
    ];

    $form['nagios_status_page']['nagios_enable_status_page_get'] = [
      '#type' => 'radios',
      '#default_value' => (int) $config->get('nagios.statuspage.getparam'),
      '#options' => [
        0 => $this->t('The HTTP User Agent has to be exactly the Unique ID.'),
        1 => $this->t('Enable Unique ID checking via GET parameter in the URL status page'),
      ],
      '#description' => $this->getUserAgentRadioDesc(),
      '#states' => $only_enabled_if_page,
    ];

    $form['nagios_error_levels'] = [
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#title' => $this->t('Error levels'),
      '#description' => $this->t('Set the values to be used for error levels when reporting to Nagios.'),
    ];
    $form['nagios_error_levels']['nagios_status_ok_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Status OK'),
      '#description' => $this->t('The value to send to Nagios for a Status OK message.'),
      '#default_value' => $config->get('nagios.status.ok'),
    ];
    $form['nagios_error_levels']['nagios_status_warning_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Warning'),
      '#description' => $this->t('The value to send to Nagios for a Warning message.'),
      '#default_value' => $config->get('nagios.status.warning'),
    ];
    $form['nagios_error_levels']['nagios_status_critical_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Critical'),
      '#description' => $this->t('The value to send to Nagios for a Critical message.'),
      '#default_value' => $config->get('nagios.status.critical'),
    ];
    $form['nagios_error_levels']['nagios_status_unknown_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unknown'),
      '#description' => $this->t('The value to send to Nagios for an Unknown message.'),
      '#default_value' => $config->get('nagios.status.unknown'),
    ];

    $form[$group] = [
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#title' => $this->t('Modules'),
      '#description' => $this->t('Select the modules that should report their data into Nagios.'),
    ];

    foreach (nagios_invoke_all('nagios_info') as $module => $data) {
      $form[$group]['nagios_enable_' . $module] = [
        '#type' => 'checkbox',
        '#title' => $data['name'] . ' (' . $module . ')',
        '#default_value' => $config->get('nagios.enable.' . $module) !== 0,
      ];
    }

    $form['watchdog'] = [
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#title' => $this->t('Watchdog Settings'),
      '#description' => $this->t('Controls how watchdog messages are retreived and displayed when watchdog checking is set.'),
    ];
    $form['watchdog']['limit_watchdog_display'] = [
      '#type' => 'checkbox',
      '#title' => 'Limit watchdog display',
      '#default_value' => $config->get('nagios.limit_watchdog.display'),
      '#description' => $this->t('Limit watchdog messages to only those that are new since the last check.'),
    ];
    $form['watchdog']['limit_watchdog_results'] = [
      '#type' => 'textfield',
      '#title' => 'Limit watchdog logs',
      '#default_value' => $config->get('nagios.limit_watchdog.results'),
      '#description' => $this->t('Limit the number of watchdog logs that are checked. E.G. 50 will only check the newest 50 logs.'),
    ];

    foreach (nagios_invoke_all('nagios_settings') as $module => $module_settings) {
      $form[$module] = [
        '#type' => 'fieldset',
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#title' => $module,
      ];

      foreach ($module_settings as $element => $data) {
        $form[$module][$element] = $data;

        // set #defaultvalue from #configname for first level form elements
        if (!isset($data['#default_value']) && isset($data['#configname'])) {
          $form[$module][$element]['#default_value'] = $config->get($module . '.' . $data['#configname']);
        }

        // set #defaultvalue from #configname for second level form elements
        if (isset($data['#type']) && $data['#type'] == 'fieldset') {
          foreach ($data as $fieldsetelement => $fieldsetdata) {
            if (is_array($fieldsetdata) && !isset($fieldsetdata['#default_value']) && isset($fieldsetdata['#configname'])) {
              $form[$module][$element][$fieldsetelement]['#default_value'] = $config->get($module . '.' . $fieldsetdata['#configname']);
            }
          }
        }
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * Builds the description.
   *
   * @return string
   */
  private function getUserAgentRadioDesc() {
    $config = $this->config('nagios.settings');
    $aUrlInfo = [
      ':url' => Url::fromRoute(
        'nagios.statuspage',
        ['unique_id' => $config->get('nagios.ua')],
        ['absolute' => TRUE])->toString(),
    ];
    return $this->t('If enabled the $_GET variable "unique_id" is used for checking the correct Unique ID instead of "User Agent" ($_SERVER[\'HTTP_USER_AGENT\']).') . ' ' .
      $this->t('You need to call the following URL from Nagios / Icinga / cURL: <a href=":url">:url</a>.', $aUrlInfo) . ' ' .
      $this->t('This feature is useful to avoid webserver stats with the Unique ID as "User Agent" and helpful for human testing.');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('nagios.settings');
    $config->set('nagios.ua', $form_state->getValue('nagios_ua'));
    $config->set('nagios.show_outdated_names', $form_state->getValue('nagios_show_outdated_names'));
    $config->set('nagios.statuspage.enabled', (bool) $form_state->getValue('nagios_enable_status_page'));
    $config->set('nagios.statuspage.path', $form_state->getValue('nagios_page_path'));
    $config->set('nagios.statuspage.controller', $form_state->getValue('nagios_page_controller'));
    $config->set('nagios.statuspage.getparam', $form_state->getValue('nagios_enable_status_page_get'));
    $config->set('nagios.status.ok', (int) $form_state->getValue('nagios_status_ok_value'));
    $config->set('nagios.status.warning', (int) $form_state->getValue('nagios_status_warning_value'));
    $config->set('nagios.status.critical', (int) $form_state->getValue('nagios_status_critical_value'));
    $config->set('nagios.status.unknown', (int) $form_state->getValue('nagios_status_unknown_value'));

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
          foreach ($data as $fieldsetelement => $fieldsetdata) {
            if (is_array($fieldsetdata) && !isset($fieldsetdata['#default_value']) && isset($fieldsetdata['#configname'])) {
              $config->set($module . '.' . $fieldsetdata['#configname'], $form_state->getValue($fieldsetelement, $config->get($module . '.' . $fieldsetdata['#configname'])));
            }
          }
        }
      }
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
