<?php

namespace BrauneDigital\ApiBaseBundle\Service;

use BrauneDigital\ApiBaseBundle\Entity\Token;
use Doctrine\ORM\NoResultException;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class TokenManager  {

    protected $container;

	protected $fieldname;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container, $fieldname) {
        $this->container = $container;
        $this->fieldname = $fieldname;
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
		$expiresAt->modify('+' . (intval($this->container->getParameter('braune_digital_api_base.timeout'))) . ' seconds');
		$token->setExpiresAt($expiresAt);
		$user->addToken($token);
		$this->container->get('doctrine')->getManager()->persist($token);
		return $token;
	}



	/**
	 * @param UserInterface $user
	 * @return Token
	 */
	public function refreshToken($apiKey) {

		try {
			$token = $this->container->get('doctrine')->getRepository('BrauneDigitalApiBaseBundle:Token')->findOneBy([
				'token' => $apiKey
			]);
			if ($token) {
				$expiresAt = new \DateTime();
				$expiresAt->modify('+' . (intval($this->container->getParameter('braune_digital_api_base.timeout')) / 1000) . ' seconds');
				$token->setExpiresAt($expiresAt);
				$em = $this->container->get('doctrine')->getManager();
				$em->persist($token);
				$em->flush();
			}

		} catch (NoResultException $e) {
			// Don't do anything here.
		}

	}

	/**
	 * @param Request $request
	 */
	public function onLogout(Request $request, UserInterface $user = null) {
		$apiKey = $request->headers->get($this->fieldname);
		if ($apiKey) {
			$token = $this->container->get('doctrine')->getRepository('BrauneDigitalApiBaseBundle:Token')->findOneBy([
				'token' => $apiKey
			]);
			$em = $this->container->get('doctrine')->getManager();
			$em->remove($token);
			$em->flush();
		} else if ($user) {
			$this->cleanupTokens($user);
		}

	}

	/**
	 * @param UserInterface $user
	 */
	public function cleanupTokens(UserInterface $user) {

		$qb = $this->container
			->get('doctrine')
			->getRepository('BrauneDigitalApiBaseBundle:Token')
			->createQueryBuilder('t')
		;

		$qb
			->where($qb->expr()->eq('t.user', $user->getId()))
			->andWhere($qb->expr()->lt('t.expiresAt', ':now'))
			->setParameter('now', new \DateTime(), \Doctrine\DBAL\Types\Type::DATETIME)
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