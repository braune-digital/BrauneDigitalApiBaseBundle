<?php

namespace BrauneDigital\ApiBaseBundle\Service;

use BrauneDigital\ApiBaseBundle\Entity\Token;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TokenManager  {

    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

	/**
	 * @param UserInterface $user
	 * @return Token
	 */
	public function createToken(UserInterface $user) {
		$this->cleanupTokens($user);
		$user->setLastLogin(new \DateTime());
		$token = new Token($this->generateToken(), $user);
		$expiresAt = clone $user->getLastLogin();
		$expiresAt->modify('+' . (intval($this->container->getParameter('braune_digital_api_base.timeout')) / 1000) . ' seconds');
		$token->setExpiresAt($expiresAt);
		$user->addToken($token);
		$this->container->get('doctrine')->getManager()->persist($token);
		return $token;
	}



	/**
	 * @param UserInterface $user
	 * @return Token
	 */
	public function refreshToken(UserInterface $user) {
		foreach ($user->getTokens() as $token) {
			$expiresAt = clone $user->getLastLogin();
			$expiresAt->modify('+' . (intval($this->container->getParameter('braune_digital_api_base.timeout')) / 1000) . ' seconds');
			$token->setExpiresAt($expiresAt);
			$em = $this->container->get('doctrine')->getManager();
			$em->persist($token);
			$em->flush();
		}
	}

	/**
	 * @param UserInterface $user
	 */
	private function cleanupTokens(UserInterface $user) {

		$qb = $this->container
			->get('doctrine')
			->getRepository('BrauneDigitalApiBaseBundle:Token')
			->createQueryBuilder('t')
		;

		$qb
			->where($qb->expr()->eq('t.user', $user->getId()))
		;

		$tokens = $qb->getQuery()->getResult();

		if (count($tokens)) {
			$em = $this->container->get('doctrine')->getManager();
			foreach ($tokens as $token) {
				$em->remove($token);
			}
			$em->flush();
		}
	}

	/**
	 * @return string
	 */
	private function generateToken() {

		$tokenGenerator = $this->container->get('fos_user.util.token_generator');
		return substr($tokenGenerator->generateToken(), 0, 32);
	}


}