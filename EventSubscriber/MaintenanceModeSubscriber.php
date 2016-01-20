<?php
/**
 * @file
 * Contains \Drupal\nagios\EventSubscriber\MaintenanceModeSubscriber.
 */

namespace Drupal\nagios\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class MaintenanceModeSubscriber implements EventSubscriberInterface {
  /**
   * Make the status page available when Drupal is in maintenance mode.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onKernelRequestMaintenance(GetResponseEvent $event) {
    $config = \Drupal::config('nagios.settings');
    $request = $event->getRequest();
    if ($request->attributes->get('_maintenance') == MENU_SITE_OFFLINE && request_path() == $config->get('nagios.statuspage.path')) {
      $request->attributes->set('_maintenance') == MENU_SITE_ONLINE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequestMaintenance', 35);
    return $events;
  }
}