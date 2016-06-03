<?php

namespace BrauneDigital\ApiBaseBundle\EventListener;

use FOS\RestBundle\View\View;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

/**
 * Class ViewResponseListener
 *
 * @package BrauneDigital\ApiBaseBundle\EventListener
 *          Injects the configuration
 */
class ViewResponseListener {

    protected $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }
    public function onKernelView(GetResponseForControllerResultEvent $event) {

        if($event->getRequest()->attributes->get('_braune_digital_api_base_config')) {
            $result = $event->getControllerResult();
            $configuration = $this->container->getParameter('braune_digital_api_base.configuration');
            if($result instanceof View) {
                $data = $result->getData();
                $data['configuration'] = $configuration;
                $result->setData($data);
            } else {
                $result['configuration'] = $configuration;
            }
            $event->setControllerResult($result);
        }

        
    }
}