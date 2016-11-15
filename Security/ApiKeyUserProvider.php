<?php

namespace BrauneDigital\ApiBaseBundle\Security;

use Doctrine\ORM\EntityManager;
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
			$this->userManager->findUserBy(array('tokens.token' => $apiKey));
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