<?php

namespace BrauneDigital\ApiBaseBundle\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RangeHeaderListener
{
	/**
	 * @var string
	 */
	protected $contentRange;

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

		if ($request->query->has('maxPerPage') || $request->query->has('currentPage')) {
			return; //DO not override manual page requests
		}

		$offset = 0;
		$limit  = 49;

		if ($request->headers->get('Range')) {
			list($offset, $limit) = explode('-', $request->headers->get('Range'));
		}

		$request->query->add([
			'maxPerPage'  => intval(($limit - $offset) + 1),
			'currentPage' => intval($offset / (($limit - $offset) + 1) + 1)
		]);
	}
}