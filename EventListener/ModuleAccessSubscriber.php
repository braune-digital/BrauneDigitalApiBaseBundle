<?php

namespace BrauneDigital\ApiBaseBundle\EventListener;

use Sensio\Bundle\FrameworkExtraBundle\Security\ExpressionLanguage;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Debug\Exception\UndefinedFunctionException;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ModuleAccessSubscriber implements EventSubscriberInterface
{
    protected $tokenStorage;
    protected $authChecker;
    protected $trustResolver;
    protected $roleHierarchy;
    protected $moduleConfiguration;

    public function __construct(SecurityContextInterface $securityContext = null, AuthenticationTrustResolverInterface $trustResolver = null, RoleHierarchyInterface $roleHierarchy = null, TokenStorageInterface $tokenStorage = null, AuthorizationCheckerInterface $authChecker = null, $moduleConfiguration)
    {
        $this->tokenStorage = $tokenStorage ?: $securityContext;
        $this->authChecker = $authChecker ?: $securityContext;
        $this->trustResolver = $trustResolver;
        $this->roleHierarchy = $roleHierarchy;
        $this->moduleConfiguration = $moduleConfiguration;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        //get parameters which are being injected by the Sensio\Bundle\FrameworkExtraBundle\EventListener\ControllerListener
        $request = $event->getRequest();
        if (!$moduleAccess = $request->attributes->get('_module_access')) {
            return;
        }

        if(!$this->checkAccess($request, $moduleAccess->getModules())) {
            throw new AccessDeniedException('ModuleAccessListener denied access.');
        }
    }

    //TODO: move this to a service
    protected function checkAccess(Request $request, $modules) {
        $token = $this->tokenStorage->getToken();

        if (null !== $this->roleHierarchy) {
            $roles = $this->roleHierarchy->getReachableRoles($token->getRoles());
        } else {
            $roles = $token->getRoles();
        }

        $user = $token->getUser();

        if(!$user || !$user instanceof UserInterface) {
            return false;
        }

        $roles = array_map(function ($role) { return $role->getRole(); }, $roles);

        foreach($modules as $module) {
            if(!array_key_exists($module, $this->moduleConfiguration)) {
                throw new InvalidConfigurationException('Configuration for used module ' . $module . ' is missing');
            }

            $moduleConfig = $this->moduleConfiguration[$module];

            if(count($moduleConfig['roles']) == 0) {
                //since no more roles are needed, we can access this module
                return true;
            }

            foreach($moduleConfig['roles'] as $neededRole) {
                if(in_array($neededRole, $roles)) {
                    //we have the right role for at least one module
                    return true;
                }
            }
        }
        //return true if there are no modules
        return count($modules) === 0;
    }

    public static function getSubscribedEvents()
    {
        return array(KernelEvents::CONTROLLER => 'onKernelController');
    }
}
