<?php

/**
 * @file
 * Contains \Drupal\nagios\Form\SettingsForm.
 */

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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $group = 'modules';
    $config = $this->config('nagios.settings');

    $form['nagios_ua'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Unique ID'),
      '#default_value' => $config->get('nagios_ua'),
      '#description' => $this->t('Restrict sending information to requests identified by this Unique ID. You should change this to some unique string for your organization, and configure Nagios accordingly. This makes Nagios data less accessible to curious users. See the README.txt for more details.')
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
        '#default_value' => $config->get('nagios_enable_' . $module) !== 0,
      );
    }

    foreach (nagios_invoke_all('nagios_settings') as $module => $module_settings) {
      $form[$module] = array(
        '#type' => 'fieldset',
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#title' => $module,
      );

      foreach ($module_settings as $element => $data) {
        $form[$module][$element] = $data;
      }
    }
    return parent::buildForm($form, $form_state);
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('nagios.settings');
    $config->set('nagios_ua', $form_state->getValue('nagios_ua'));
    foreach (nagios_invoke_all('nagios_info') as $module => $data) {
      $config->set('nagios_enable_' . $module, $form_state->getValue('nagios_enable_' . $module));
    }
    $config->save();
  }
  
  protected function getEditableConfigNames() {
    return ['nagios.settings'];
  }

}
