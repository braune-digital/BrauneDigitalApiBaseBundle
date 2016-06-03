<?php

namespace BrauneDigital\ApiBaseBundle\Controller;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializationContext;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpFoundation\Request;
use BrauneDigital\ApiBaseBundle\Exception\InvalidPageNumberException;
use BrauneDigital\ActivityBundle\Event\ActivityEvent;

abstract class BaseApiController extends FOSRestController
{
    /**
     * @var SerializationContext
     */
    private $serializationContext;

    /**
     * @var array
     */
    private $serializationGroups = array();

    /**
     * creates a view based on data/errors
     * @param null $data
     * @param int $statusCode
     * @param bool $success
     * @param null $errors
     * @param array $headers
     * @return View
     */
    protected function responseView($data = null, $statusCode = 200, $success = true, $errors = null, array $headers = array()) {

        if($data == null) {
            $data = array();
        }

        if($errors == null) {
            $errors = array();
        }

        $data = array(
            'success' => $success,
            'payload' => $data,
            'errors' => $errors
        );

        return $this->view($data, $statusCode, $headers);
    }

    /**
     * creates a view based on a pagerfanta object
     * @param Pagerfanta $pagerfanta
     * @param callable  $resultsCallback callback function for modifying the objects
     * @return View
     */
    protected function pagerfantaResponseView(Pagerfanta $pagerfanta, $resultsCallback = null) {

        try {

            $results = $pagerfanta->getCurrentPageResults();

            if (!is_array($results)) {
                $results = iterator_to_array($results);
            }
        }
        catch(\Exception $e) {
            throw $e;
        }


        if (is_callable($resultsCallback)) {
            $resultsCallback($results);
        }

        $contentRange = sprintf(
            '%d-%d/%d',
            $pagerfanta->getCurrentPageOffsetStart(),
            $pagerfanta->getCurrentPageOffsetEnd(),
            $pagerfanta->count()
        );

        $data = array(
            'success' => true,
            'payload' => $results,
            'errors' => array()
        );

        return $this->view($data, 206, array(
            'Accept-Ranges' => 'items',
            'Range-Unit'    => 'items',
            'Content-Range' => $contentRange,
            'Access-Control-Expose-Headers' => 'Content-Range, Accept-Ranges'
        ));
    }

    /**
     * create a response from a view object, set serializer groups
     * @param View $view
     * @return View
     */
    protected function handleView(View $view, Request $request = null) {

        //merge serialization groups
        if($request) {
            $requestGroups = $this->getRequestSerializationGroups($request);

            foreach($requestGroups as $serializationGroup) {
                $this->addSerializationGroup($serializationGroup);
            }
        }

        $context = $this->getSerializationContext();

        if(count($this->getSerializationGroups()) > 0) {
            $context->setGroups($this->getSerializationGroups());
        }

        $view->setSerializationContext($context);

        //rest is handled by the view handler
        return $view;
    }

    /**
     * get the repository for the current controller
     * @return mixed
     */
    abstract protected function getRepository();

    /**
     * This function is responsible for basic list view pagination
     * @param Request $request
     * @return View
     */
    protected function listAction(Request $request) {
        $qb = $this->createListQueryBuilder();
        $view = $this->handleQuery($request, $qb);
        return $this->handleView($view, $request);
    }

    /**
     * handle query formatting: filtering, ordering, counting and/or pagination based on request parameters
     * @param Request $request
     * @param         $qb
     *
     * //TODO: refactor results and queryCallback
     */
    protected function handleQuery(Request $request, QueryBuilder $qb, $resultsCallback = null, $queryCallback = null) {

        $page = $request->query->get('currentPage');
        $maxPerPage = $request->query->get('maxPerPage');

        $queryManager = null;
        $filterConfig = null;
        $orderConfig = null;

        if($this->container->has('braune_digital_query_filter_bundle.manager')) {
            $queryManager = $this->get('braune_digital_query_filter_bundle.manager');
            $filterConfig = json_decode($request->query->get('filter'), true);
            $orderConfig = json_decode($request->query->get('order'), true);

            if ($filterConfig) {
                $queryManager->filter($qb, $filterConfig, $request->getLocale());
            }
        }

        $countRequest = $request->query->get('count');

        //Count-Request
        if ($countRequest && $countRequest !== 'false') {
            //we only want the count of entities, so no pagination or ordering needed

            $data = array();

            $alias = $qb->getRootAlias();

            //add count manually
            $qb->add('select', 'COUNT('. $alias .'.id)');
            $qb->distinct();

            if ($queryManager != null && $countRequest == 'multiple') {
                $filters = json_decode($request->query->get('filters'), true);

                $data['counts'] = array();

                foreach($filters as $index => $filter) {
                    $countQB = clone $qb;
                    $queryManager->filter($countQB, $filter, $request->getLocale());
                    $query = $countQB->getQuery();
                    $data['counts'][$index] = (int) $query->getSingleScalarResult();
                }
            } else {
                $query = $qb->getQuery();

                $data['count'] = (int) $query->getSingleScalarResult();
            }

            //just return the count(s)
            $view = $this->responseView($data);
            return $this->handleView($view, $request);
        }

        if ($queryManager != null && $orderConfig){
            //order since we have an order configuration
            $queryManager->order($qb, $orderConfig, $request->getLocale());
        }


        if (is_callable($queryCallback)) {
            $queryCallback($qb);
        }

        //check if we want to paginate
        if(!$page && !$maxPerPage) {

            $results = $qb->getQuery()->getResult();

            if (is_callable($resultsCallback)) {
                $resultsCallback($results);
            }

            $view = $this->responseView($results);
            return $this->handleView($view, $request);
        }

        $adapter = new DoctrineORMAdapter($qb);
        $pagerfanta = new Pagerfanta($adapter);


        if($maxPerPage && $maxPerPage > 0) {
            $pagerfanta->setMaxPerPage($maxPerPage); // 10 by default
        }

        //custom error handling when page does not exist
        if($page && $page > 0) {
            try {
                $pagerfanta->setCurrentPage($page); // 1 by default
            } catch(OutOfRangeCurrentPageException $e) {
                throw new InvalidPageNumberException();
            }
        }

        //create the view object for the pagerfanta object

        return $this->pagerfantaResponseView($pagerfanta, $resultsCallback);
    }

    /**
     * Returns a query builder, used to overwrite and restrict in some controllers
     * @param string $alias
     * @return mixed
     */
    protected function createListQueryBuilder($alias = 'e') {
        return $this->getRepository()->createQueryBuilder($alias);
    }

    /**
     * basic update functionality
     * @param Request $request
     * @param null    $entity
     * @param bool    $refresh false, true (refresh entity)
     *
     * @return View
     * @throws EntityNotFoundException
     */
    protected function createAction(Request $request, $entity = null, $refresh = false, $formOptions = null) {

        if(!$entity) {
            $className = $this->getRepository()->getClassName();
            $entity = new $className();
        }

        $form = $this->getForm($entity, 'create', $this->mergeFormOptions($request, $formOptions));

        if($form instanceof Form) {

            $form->handleRequest($request);

            if ($form->isValid()) {

                $this->denyAccessUnlessGranted('create', $entity);

                $em = $this->getDoctrine()->getManager();
                $em->persist($entity);
                $em->flush();

                //reload entites for e.g. post-load operations
                if ($refresh) {
                    $em->refresh($entity);
                }

                return $this->readAction($request, $entity->getId());

            } else {

                $view = $this->responseView(null, 400, false, $this->getErrorMessages($form));
                return $this->handleView($view, $request);
            }
        }
        throw new \LogicException("Check your implementation of the getForm function!");
    }

    /**
     * @param Form $form
     * @return array
     */
    protected function getErrorMessages(\Symfony\Component\Form\Form $form) {

        $errors = array();
        $formErrors = array();

        foreach ($form->getErrors() as $error) {
            $formErrors[implode('.', $error->getOrigin()->getPropertyPath()->getElements())][] = $error->getMessage();
        }

        if (count($formErrors) > 0) {
            $errors['form'] = $formErrors;
        }

        if (count($form->all()) > 0) {
            foreach ($form->all() as $child) {
                if (!$child->isValid()) {
                    $errors[$child->getName()] = $this->getErrorMessages($child);
                }
            }
        }

        return $errors;
    }

    /**
     * basic read functionality
     * @param Request $request
     * @param $id
     * @return View
     * @throws EntityNotFoundException
     */
    protected function readAction(Request $request, $id) {
        $entity = $this->findEntity($id);

        $this->denyAccessUnlessGranted('read', $entity);

        $view = $this->responseView($entity);
        return $this->handleView($view, $request);
    }

    /**
     * basic update functionality
     * @param Request $request
     * @param $refresh
     * @param $id
     */
    protected function updateAction(Request $request, $id, $refresh = false, $formOptions = null) {

        $entity = $this->findEntity($id);

        $this->denyAccessUnlessGranted('update', $entity);

        $form = $this->getForm($entity, 'update', $this->mergeFormOptions($request, $formOptions));

        if($form instanceof Form) {

            $form->handleRequest($request);

            $em = $this->getDoctrine()->getManager();
            if ($form->isValid()) {
                $em->flush();

                if ($refresh) {
                    $em->refresh($entity);
                }
                return $this->readAction($request, $id);
            } else {

                //dont apply changes
                $em->detach($entity);

                $view = $this->responseView(null, 400, false, $this->getErrorMessages($form));
                return $this->handleView($view, $request);
            }

        }
        throw new \LogicException("Check your implementation of the getForm function!");
    }

    /**
     * @param Request $request
     */
    public function getRequestSerializationGroups(Request $request) {

        $headerSerializerGroups = $request->headers->get('serializationGroups');
        if ($headerSerializerGroups && is_string($headerSerializerGroups)) {
            $headerSerializerGroups = explode(',', $headerSerializerGroups);
        }

        if ($headerSerializerGroups && is_array($headerSerializerGroups)) {
            return $headerSerializerGroups;
        }

        return array();
    }

    /**
     * @param Request $request
     * @param         $options
     */
    protected function mergeFormOptions(Request $request, $options) {

        $headerOptions = json_decode($request->headers->get('formOptions'), true);

        if ($headerOptions && is_array($headerOptions)) {
            if ($options) {
                return array_replace($headerOptions, $options);
            } else {
                return $headerOptions;
            }
        } else if ($options && is_array($options)) {
            return $options;
        }
        return array();
    }

    /**
     * basic delete functionality
     * @param Request $request
     * @param         $id
     *
     * @return View
     * @throws EntityNotFoundException
     */
    protected function deleteAction(Request $request, $id) {
        $entity = $this->findEntity($id);

        $this->denyAccessUnlessGranted('delete', $entity);

        $em = $this->getDoctrine()->getManager();

        $em->remove($entity);
        $em->flush();

        $view = $this->responseView();
        return $this->handleView($view, $request);
    }

    /**
     * Fires an event for any activity listener
     * @param $type
     * @param null $data
     * @param null $user
     */
    protected function activityEvent($type, array $tags = array(), $data = null, $user = null) {

        if(!class_exists('BrauneDigital\ActivityBundle\Event\ActivityEvent')) {
            throw new \Exception("You need to install the BrauneDigitalActivityBundle to use this function!");
        }

        if(!$user) {
            $user = $this->getUser();
        }
        //fire login event
        $event = new ActivityEvent($type, $user, $tags, $data);
        $this->get('event_dispatcher')->dispatch(ActivityEvent::EVENT_ID, $event);
    }

    /**
     * @param $entity
     * @param string $mode
     * @param array $options
     * @return null
     */
    protected function getForm($entity, $mode = '', $options = array()) {
        return null;
    }


    /**
     * @param $group
     */
    protected function addSerializationGroup($group) {
        if(!in_array($group, $this->serializationGroups)) {
            array_push($this->serializationGroups, $group);
        }
    }

    /**
     * @param $group
     */
    protected function removeSerializationGroup($group) {
        $index = array_search($group, $this->serializationGroups);
        if($index !== false) {
            $this->serializationGroups = array_splice($this->serializationGroups, $index, 1);
        }
    }

    /**
     * @return array
     */
    protected function getSerializationGroups() {
        return $this->serializationGroups;
    }

    /**
     * @return array
     */
    protected function setSerializationGroups($groups) {
        $this->serializationGroups = $groups;
    }

    /**
     * @return SerializationContext
     * Getter with lazy creation
     */
    protected function getSerializationContext() {
        if($this->serializationContext == null) {
            $this->serializationContext = SerializationContext::create();
        }
        return $this->serializationContext;
    }

    /**
     * @param $id
     *
     * @throws EntityNotFoundException
     * Find one entity by id
     */
    protected function findEntity($id) {
        $repo = $this->getRepository();
        $entity = $repo->findOneById($id);

        if($entity == null) {
            throw new EntityNotFoundException();
        }

        return $entity;
    }
}
