<?php
namespace BrauneDigital\ApiBaseBundle\Security\Authorization\Voter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use FOS\UserBundle\Model\User;

abstract class BaseCRUDVoter extends Voter
{
    const CREATE = 'create';
    const READ = 'read';
    const UPDATE = 'update';
    const DELETE = 'delete';

    protected $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    /**
     * @param string         $attribute
     * @param mixed          $subject
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token) {

        $user = $token->getUser();

        // make sure there is a user object (i.e. that the user is logged in)
        if (!$user || !$user instanceof UserInterface) {
            return false;
        }

        // double-check that the User object is the expected entity.
        // It always will be, unless there is some misconfiguration of the
        // security system.
        if (!$user instanceof User) {
            throw new \LogicException('The user is somehow not our User class!');
        }

        return true;
    }

    /**
     * @return mixed
     */
    protected function isAdmin() {
        return $this->container->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN');
    }
}