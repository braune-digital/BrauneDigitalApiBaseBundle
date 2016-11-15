<?php

namespace BrauneDigital\ApiBaseBundle\EventListener;

use BrauneDigital\ApiBaseBundle\Entity\Token;
use Doctrine\Common\EventSubscriber;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

class EntitySubscriber implements EventSubscriber {

    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(Events::prePersist, Events::preUpdate);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args) {

		$this->onTokenPersist($args->getEntity());
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preUpdate(LifecycleEventArgs $args) {

		$this->onTokenPersist($args->getEntity());
    }


	/**
	 * @param Course $entity
	 * @param EntityManager $em
	 */
    public function onTokenPersist($entity) {
		if ($entity instanceof Token) {
			$expiresAt = clone $entity->getUser()->getLastLogin();
			$expiresAt->modify('+15 minutes');
			$entity->setExpiresAt($expiresAt);
		}
	}

}