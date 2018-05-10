<?php

namespace Drupal\nagios\EventSubscriber;

use Drupal\nagios\Controller\StatuspageController;
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
    $nagios_path = '/' . $config->get('nagios.statuspage.path');
    if ($request->getPathInfo() === $nagios_path) {
      $oController = StatuspageController::create(\Drupal::getContainer());
      if ($oController->access()->isAllowed()) {
        $event->setResponse($oController->content());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onKernelRequestMaintenance', 35];
    return $events;
  }

}
