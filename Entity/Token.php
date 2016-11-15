<?php

namespace BrauneDigital\ApiBaseBundle\Entity;


use FOS\UserBundle\Model\UserInterface;

class Token
{

	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $token;

	/**
	 * @var \DateTime
	 */
	protected $expiresAt;

	/**
	 * @var UserInterface
	 */
	protected $user;

	/**
	 * Token constructor.
	 * @param $token
	 */
	public function __construct($token, UserInterface $user)
	{
		$this->token = $token;
		$this->user = $user;
	}


	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getToken()
	{
		return $this->token;
	}

	/**
	 * @param string $token
	 */
	public function setToken($token)
	{
		$this->token = $token;
	}

	/**
	 * @return \DateTime
	 */
	public function getExpiresAt()
	{
		return $this->expiresAt;
	}

	/**
	 * @param \DateTime $expiresAt
	 */
	public function setExpiresAt($expiresAt)
	{
		$this->expiresAt = $expiresAt;
	}

	/**
	 * @return UserInterface
	 */
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * @param UserInterface $user
	 */
	public function setUser($user)
	{
		$this->user = $user;
	}

}