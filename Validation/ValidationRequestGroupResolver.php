<?php

namespace BrauneDigital\ApiBaseBundle\Validation;

use Doctrine\ORM\EntityManagerInterface;
use Nelmio\Alice\ProcessorInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ValidationRequestGroupResolver
{
	private $request;

	public function __construct(RequestStack $requestStack)
	{
		$this->request = $requestStack->getCurrentRequest();
	}

	/**
	 * @param FormInterface $form
	 * @return array
	 */
	public function __invoke(FormInterface $form)
	{
		$groups = array();
		if ($this->request->headers->get('validationgroups')) {
			$groups = array_merge($groups, explode(',', str_replace(' ', '', $this->request->headers->get('validationgroups'))));
		}
		return $groups;
	}

}