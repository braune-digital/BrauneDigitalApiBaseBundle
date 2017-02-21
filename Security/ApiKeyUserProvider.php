<?php

namespace BrauneDigital\ApiBaseBundle\Security;

use Doctrine\ORM\NoResultException;
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
            $qb = $this->container->get('doctrine')->getRepository($this->userManager->getClass())->createQueryBuilder('u');
            $qb
                ->leftJoin('u.tokens', 't')
                ->where($qb->expr()->eq('t.token', $qb->expr()->literal($apiKey)));

            if(intval($this->container->getParameter('braune_digital_api_base.timeout')) > 0) {
                $qb->andWhere($qb->expr()->gte('t.expiresAt', ':now'))->setParameter('now', new \DateTime(), \Doctrine\DBAL\Types\Type::DATETIME);
            }

            $qb->setMaxResults(1);

            try {
                $user = $qb->getQuery()->getSingleResult();
                $this->container->get('braune_digital_api_base.service.token_manager')->refreshToken($apiKey);
                return $user;
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