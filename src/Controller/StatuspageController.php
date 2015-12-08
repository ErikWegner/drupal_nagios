<?php

/**
 * @file
 * Contains \Drupal\nagios\Controller\StatuspageController.
 */

namespace Drupal\nagios\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Cache\CacheableResponse;

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


    $response = new CacheableResponse($output, 200);
    
    // Disable browser cache
    $response->setMaxAge(0);
    $response->setExpires($date);
    
    // Disable Drupal cache
    $cacheableMetadata = $response->getCacheableMetadata();
    $cacheableMetadata->setCacheMaxAge(0);
    
    return $response;
  }

}
