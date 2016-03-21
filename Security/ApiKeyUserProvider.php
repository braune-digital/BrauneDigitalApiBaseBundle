<?php

namespace BrauneDigital\ApiBaseBundle\Security;

use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Security\UserProvider;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ApiKeyUserProvider extends UserProvider
{

    public function getUserForApiKey($apiKey)
    {
        return $this->userManager->findUserBy(array('token' => $apiKey));
    }
}