<?php

namespace BrauneDigital\ApiBaseBundle\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class SerializationListener
{

	const SERIALIZATION_GROUP_KEY = '_braune_digital_api_base_serialization_groups';
	protected $config;

	public function __construct(array $config = array())
	{
		$this->config = $config;

		if (!array_key_exists('allow_custom_groups', $this->config)) {
			$this->config['allow_custom_groups'] = false;
		}

		if (!array_key_exists('custom_groups_key', $this->config) || !$this->config['custom_groups_key']) {
			$this->config['custom_groups_key'] = 'serializationGroups';
		}

		if (!array_key_exists('route_as_default', $this->config)) {
			$this->config['route_as_default'] = false;
		}

		if (!array_key_exists('default_groups', $this->config)) {
			$this->config['default_groups'] = ['Default']; //default JMS Serialization Group => include properties which got no groups defined
		}
	}


	public function getRequestSerializationGroups(Request $request) {

		$headerSerializerGroups = $request->headers->get($this->config['custom_groups_key']);
		if ($headerSerializerGroups && is_string($headerSerializerGroups)) {
			$headerSerializerGroups = explode(',', $headerSerializerGroups);
		}

		if ($headerSerializerGroups && is_array($headerSerializerGroups)) {
			return $headerSerializerGroups;
		}

		return array();
	}

	/**
	 * Adding "offset" and "limit" to request parameters when "Range" header is detected
	 *
	 * @param GetResponseEvent         $event
	 * @param string                   $eventName
	 * @param EventDispatcherInterface $dispatcher
	 */
	public function onKernelRequest(GetResponseEvent $event, $eventName, EventDispatcherInterface $dispatcher)
	{
		if (!$event->isMasterRequest()) {
			return;
		}

		$request = $event->getRequest();
		if ('json' !== $request->getRequestFormat() && !$request->isXmlHttpRequest()) {
			return;
		}

		//get current set groups
		$groups = $request->attributes->get(self::SERIALIZATION_GROUP_KEY, []);

		//add custom groups if allowed
		if ($this->config['allow_custom_groups']) {
			$groups = $this->getRequestSerializationGroups($request);
		}

		//add current route as default group
		if ($this->config['route_as_default']) {

			$route = $request->attributes->get('_route');
			if ($route) {
				$groups[] = $route;
			}
		}

		if ($this->config['default_groups']) {
			$groups = array_merge($groups, $this->config['default_groups']);
		}

		//kick multiple groups
		$groups = array_unique($groups);
		$request->attributes->set('_braune_digital_api_base_serialization_groups', $groups);
	}
}