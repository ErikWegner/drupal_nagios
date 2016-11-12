<?php

namespace Drupal\nagios\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

class StatuspageController extends ControllerBase {

  public function content() {
    $config = \Drupal::config('nagios.settings');

    // Disable cache
    \Drupal::service('page_cache_kill_switch')->trigger();

    $args = func_get_args();
    // Module to run checks for.
    $module = array_shift($args);
    // ID to run checks for.
    $id = array_shift($args);

    $codes = nagios_status();

    // Check the unique ID string and access permissions first.
    $ua = $config->get('nagios.ua');
    $request_code = $_SERVER['HTTP_USER_AGENT'];

    // Check if HTTP GET variable "unique_id" is used and the usage is allowed.
    if (isset($_GET['unique_id'])) {
      if ($config->get('nagios.statuspage.getparam') == TRUE) {
        $request_code = $_GET['unique_id'];
      }
    }

    if (\Drupal::currentUser()->hasPermission('administer site configuration') || ($request_code == $ua)) {
      // Authorized so calling other modules
      if ($module) {
        // A specific module has been requested.
        $nagios_data = array();
        $nagios_data[$module] = \Drupal::moduleHandler()->invoke($module, 'nagios', $id);
      } else {
        $nagios_data = nagios_invoke_all('nagios');
      }
    } else {
      // This is not an authorized unique id or uer, so just return this default status.
      $nagios_data = array(
        'nagios' => array(
          'DRUPAL' => array(
            'status' => NAGIOS_STATUS_UNKNOWN,
            'type' => 'state',
            'text' => t('Unauthorized'),
          ),
        ),
      );
    }

    // Find the highest level to be the overall status
    $severity = NAGIOS_STATUS_OK;
    $min_severity = $config->get('nagios.min_report_severity');

    foreach ($nagios_data as $module_name => $module_data) {
      foreach ($module_data as $key => $value) {
        if (is_array($value) && array_key_exists('status', $value) && $value['status'] >= $min_severity) {
          $severity = max($severity, $value['status']);
        }
      }
    }

    // Identifier that we check on the other side
    $output = "\n" . 'nagios=' . $codes[$severity] . ', ';

    $output_state = array();
    $output_perf = array();

    foreach ($nagios_data as $module_name => $module_data) {
      foreach ($module_data as $key => $value) {
        switch ($value['type']) {
          case 'state':
            // If status is larger then minimum severity
            if ($value['status'] >= $min_severity) {
              $tmp_state = $key . ':' . $codes[$value['status']];
            } else {
              $tmp_state = $key . ':' . $codes[NAGIOS_STATUS_OK];
            }

            if (!empty($value['text'])) {
              $tmp_state .= '=' . $value['text'];
            }

            if (
              $config->get('nagios.show_outdated_names') &&
              $key == 'ADMIN' &&
              $value['text'] == 'Module and theme update status'
            ) {
              $tmp_projects = update_calculate_project_data(\Drupal::service('update.manager')->getProjects());
              $nagios_ignored_modules = $config->get('nagios.ignored_modules') ?: array();
              $nagios_ignored_themes = $config->get('nagios.ignored_themes') ?: array();
              $nagios_ignored_projects = $nagios_ignored_modules + $nagios_ignored_themes;
              $outdated_count = 0;
              $tmp_modules = '';
              foreach ($tmp_projects as $projkey => $projval) {
                if (!isset($nagios_ignored_projects[$projkey])) {
                  if ($projval['status'] < UPDATE_CURRENT && $projval['status'] >= UPDATE_NOT_SECURE) {
                    switch ($projval['status']) {
                      case UPDATE_NOT_SECURE:
                        $tmp_projstatus = t('NOT SECURE');
                        break;
                      case UPDATE_REVOKED:
                        $tmp_projstatus = t('REVOKED');
                        break;
                      case UPDATE_NOT_SUPPORTED:
                        $tmp_projstatus = t('NOT SUPPORTED');
                        break;
                      case UPDATE_NOT_CURRENT:
                        $tmp_projstatus = t('NOT CURRENT');
                        break;
                      default:
                        $tmp_projstatus = $projval['status'];
                    }
                    $tmp_modules .= ' ' . $projkey . ':' . $tmp_projstatus;
                    $outdated_count++;
                  }
                }
              }
              if ($outdated_count > 0) {
                $tmp_modules = trim($tmp_modules);
                $tmp_state .= " ($tmp_modules)";
              }
            }

            $output_state[] = $tmp_state;
            break;

          case 'perf':
            $output_perf[] = $key . '=' . $value['text'];
            break;
        }
      }
    }

    $output .= implode(', ', $output_state) . ' | ' . implode('; ', $output_perf) . "\n";

    $response = new Response($output, Response::HTTP_OK, ['Content-Type' => 'text/plain']);

    // Disable browser cache
    $response->setMaxAge(0);
    $response->setExpires();

    return $response;
  }

  public function routes() {
    $config = \Drupal::config('nagios.settings');
    $routes = array();
    // Declares a single route under the name 'example.content'.
    // Returns an array of Route objects. 
    $routes['nagios.statuspage'] = new Route(
      // Path to attach this route to:
      $config->get('nagios.statuspage.path'),
      // Route defaults:
      array(
      '_controller' => $config->get('nagios.statuspage.controller'),
      '_title' => 'Nagios Status'
      ),
      // Route requirements:
      array(
      '_custom_access' => '\Drupal\nagios\Controller\StatuspageController::access'
      )
    );
    return $routes;
  }

  public function access() {
    $config = \Drupal::config('nagios.settings');
    return AccessResult::allowedIf($config->get('nagios.statuspage.enabled') === 1);
  }

}
