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
		if ('json' !== $event->getRequest()->getRequestFormat()) {
			return;
		}

		$offset = 0;
		$limit  = 49;

		if ($event->getRequest()->headers->get('Range')) {
			list($offset, $limit) = explode('-', $event->getRequest()->headers->get('Range'));
		}

		$event->getRequest()->query->add([
			'maxPerPage'  => intval(($limit - $offset) + 1),
			'currentPage' => intval($offset / (($limit - $offset) + 1) + 1)
		]);
	}
}