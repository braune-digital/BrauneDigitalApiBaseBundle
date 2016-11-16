<?php

namespace BrauneDigital\ApiBaseBundle\Security;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Security\UserProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ApiKeyUserProvider extends UserProvider
{

	/**
	 * @var ContainerInterface
	 */
	protected $container;

	public function getUserForApiKey($apiKey)
	{
		$features = $this->container->getParameter('braune_digital_api_base.features');
		if ($features['use_token_relation']) {
			$qb = $this->container->get('doctrine')->getRepository('VSFFahrschuleFlorinBaseBundle:User')->createQueryBuilder('u');
			$qb
				->leftJoin('u.tokens', 't')
				->where($qb->expr()->eq('t.token', $qb->expr()->literal($apiKey)))
				->andWhere($qb->expr()->gte('t.expiresAt', ':now'))
				->setParameter('now', new \DateTime(), \Doctrine\DBAL\Types\Type::DATETIME)
				->setMaxResults(1)
			;
			try {
				return $qb->getQuery()->getSingleResult();
			} catch (NoResultException $e) {
				return false;
			}
		} else {
			return $this->userManager->findUserBy(array('token' => $apiKey));
		}
	}

	/**
	 * @param ContainerInterface $container
	 */
	public function setContainer($container)
	{
		$this->container = $container;
	}


}