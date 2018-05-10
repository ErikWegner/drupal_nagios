<?php

namespace Drupal\nagios\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\ControllerBase;
use Drupal\update\UpdateManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

/**
 * Class StatuspageController produces the HTTP output that the bash script in
 * the nagios-plugin directory understands.
 *
 * @package Drupal\nagios\Controller
 */
class StatuspageController extends ControllerBase {

  /**
   * Main function building the string to show via HTTP.
   *
   * @return Response
   */
  public function content() {
    $config = \Drupal::config('nagios.settings');

    // Disable cache:
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
    if (isset($_GET['unique_id']) && $config->get('nagios.statuspage.getparam') == TRUE) {
      $request_code = $_GET['unique_id'];
    }

    if ($request_code == $ua || \Drupal::currentUser()
        ->hasPermission('administer site configuration')) {
      // Authorized, so go ahead calling all modules:
      if ($module) {
        // A specific module has been requested.
        $nagios_data = [];
        $nagios_data[$module] = \Drupal::moduleHandler()
          ->invoke($module, 'nagios', $id);
      }
      else {
        $nagios_data = nagios_invoke_all('nagios');
      }
    }
    else {
      // This is not an authorized unique id or uer, so just return this default
      // status.
      $nagios_data = [
        'nagios' => [
          'DRUPAL' => [
            'status' => NAGIOS_STATUS_UNKNOWN,
            'type' => 'state',
            'text' => $this->t('Unauthorized'),
          ],
        ],
      ];
    }

    // Find the highest level to be the overall status:
    $severity = NAGIOS_STATUS_OK;
    $min_severity = $config->get('nagios.min_report_severity');

    $output_state = [];
    $output_perf = [];

    foreach ($nagios_data as $module_name => $module_data) {
      foreach ($module_data as $key => $value) {
        // Check status and set global severity:
        if (is_array($value) && array_key_exists('status', $value) && $value['status'] >= $min_severity) {
          $severity = max($severity, $value['status']);
        }
        switch ($value['type']) {
          case 'state':
            // Complain only if status is larger then minimum severity:
            if ($value['status'] >= $min_severity) {
              $tmp_state = $key . ':' . $codes[$value['status']];
            }
            else {
              $tmp_state = $key . ':' . $codes[NAGIOS_STATUS_OK];
            }

            if (!empty($value['text'])) {
              $tmp_state .= '=' . $value['text'];
            }

            if (
              $key == 'ADMIN' &&
              $value['text'] == 'Module and theme update status' &&
              $config->get('nagios.show_outdated_names')
            ) {
              $tmp_projects = update_calculate_project_data(\Drupal::service('update.manager')
                ->getProjects());
              $nagios_ignored_modules = $config->get('nagios.ignored_modules') ?: [];
              $nagios_ignored_themes = $config->get('nagios.ignored_themes') ?: [];
              $nagios_ignored_projects = $nagios_ignored_modules + $nagios_ignored_themes;
              $outdated_count = 0;
              $tmp_modules = '';
              foreach ($tmp_projects as $projkey => $projval) {
                if (!isset($nagios_ignored_projects[$projkey]) && $projval['status'] < UpdateManagerInterface::CURRENT && $projval['status'] >= UpdateManagerInterface::NOT_SECURE) {
                  switch ($projval['status']) {
                    case UpdateManagerInterface::NOT_SECURE:
                      $tmp_projstatus = $this->t('NOT SECURE');
                      break;

                    case UpdateManagerInterface::REVOKED:
                      $tmp_projstatus = $this->t('REVOKED');
                      break;

                    case UpdateManagerInterface::NOT_SUPPORTED:
                      $tmp_projstatus = $this->t('NOT SUPPORTED');
                      break;

                    case UpdateManagerInterface::NOT_CURRENT:
                      $tmp_projstatus = $this->t('NOT CURRENT');
                      break;

                    default:
                      $tmp_projstatus = $projval['status'];
                  }
                  $tmp_modules .= ' ' . $projkey . ':' . $tmp_projstatus;
                  $outdated_count++;
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

    // Identifier that we check on the bash side:
    $output = "\n" . 'nagios=' . $codes[$severity] . ', ';

    $output .= implode(', ', $output_state) . ' | ' . implode('; ', $output_perf) . "\n";

    $response = new Response($output, Response::HTTP_OK, ['Content-Type' => 'text/plain']);

    // Disable browser cache:
    $response->setMaxAge(0);
    $response->setExpires();

    return $response;
  }

  /**
   * Route callback to allow for user-defined URL of status page.
   *
   * @return Route[]
   */
  public function routes() {
    $config = \Drupal::config('nagios.settings');
    $routes = [];
    // Declares a single route under the name 'example.content'.
    // Returns an array of Route objects.
    $routes['nagios.statuspage'] = new Route(
    // Path to attach this route to:
      $config->get('nagios.statuspage.path'),
      // Route defaults:
      [
        '_controller' => $config->get('nagios.statuspage.controller'),
        '_title' => 'Nagios Status',
      ],
      // Route requirements:
      [
        '_custom_access' => '\Drupal\nagios\Controller\StatuspageController::access',
      ]
    );
    return $routes;
  }

  /**
   * Checks if the status page should exist.
   *
   * @return AccessResult
   */
  public function access() {
    $config = \Drupal::config('nagios.settings');
    return AccessResult::allowedIf($config->get('nagios.statuspage.enabled'));
  }

  /**
   * For backwards compatibility, this module uses defines to set levels.
   * This function is called globally in nagios.module.
   *
   * @param ImmutableConfig|NULL $config
   */
  public static function setNagiosStatusConstants(ImmutableConfig $config = NULL) {
    // Defines to be used by this modules and others that use its hook_nagios().
    if (!$config) {
      $config = \Drupal::config('nagios.settings');
    }
    if ($config->get('nagios.status.ok') === NULL) {
      // Should only happen in tests, as the config might not be loaded yet.
      return;
    }
    define('NAGIOS_STATUS_OK', $config->get('nagios.status.ok') /* Default: 0 */);
    define('NAGIOS_STATUS_WARNING', $config->get('nagios.status.warning') /* Default: 1 */);
    define('NAGIOS_STATUS_CRITICAL', $config->get('nagios.status.critical') /* Default: 2 */);
    define('NAGIOS_STATUS_UNKNOWN', $config->get('nagios.status.unknown') /* Default: 3 */);
  }

}
