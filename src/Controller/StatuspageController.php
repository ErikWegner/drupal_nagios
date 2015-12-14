<?php

/**
 * @file
 * Contains \Drupal\nagios\Controller\StatuspageController.
 */

namespace Drupal\nagios\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

class StatuspageController extends ControllerBase {

  public function content() {
    $codes = nagios_status();

    $nagios_data = nagios_invoke_all('nagios');

    // Find the highest level to be the overall status
    $severity = NAGIOS_STATUS_OK;

    foreach ($nagios_data as $module_name => $module_data) {
      foreach ($module_data as $key => $value) {
        $severity = max($severity, $value['status']);
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
            $tmp_state = $key . ':' . $codes[$value['status']];
            if (!empty($value['text'])) {
              $tmp_state .= '=' . $value['text'];
            }
            $output_state[] = $tmp_state;
            break;

          case 'perf':
            $output_perf[] = $key . '=' . $value['text'];
            break;
        }
      }
    }

    $output .= implode(', ', $output_state) . ' | ' . implode(';', $output_perf) . "\n";
    
    $response = new Response($output, Response::HTTP_OK, ['Content-Type' => 'text/plain']);
    
    // Disable browser cache
    $response->setMaxAge(0);
    $response->setExpires($date);
    
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
    return $config->get('nagios.statuspage.enabled') === 1;
  }
}
