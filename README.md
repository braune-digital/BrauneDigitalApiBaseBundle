# BrauneDigitalApiBaseBundle
This Symfony-Bundle provides basic functionality for simple and clean apis.
## Features
* BaseApiController: A foundation for your api controllers
* ApiKey authentication: Authenticate users using an api-token
* Pagination
* Query-Filtering: Filter Lists (coming soon)
* Module Access: Split your Api into modules and restrict their access to certain user roles
* Custom Configuration in Response: Add your custom configuration to specific responses
  
## Requirements
* FOSRestBundle
* WhiteOctoberPagerFantaBundle
* JMSSerializerBundle (optional)
  

## Installation

### Download using composer:
```bash
composer require braune-digital/api-base-bundle
```  
### And enable the Bundle in your AppKernel.  
You may use the BaseApiController without registering the bundle too.

```php
public function registerBundles()
    {
        $bundles = array(
          ...
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new FOS\RestBundle\FOSRestBundle(),

            new BrauneDigital\ApiBaseBundle\BrauneDigitalApiBaseBundle(),
            new Nelmio\CorsBundle\NelmioCorsBundle(),
          ...
        );
```
## Configuration
### DefaultConfiguration
```yaml
braune_digital_api_base:
    modules: ~ #Used for Module-Access
    timeout: 0 # Timeout for Api-Tokens (use 0 for no timeout)
    configuration: # Your configuration to be send
```

###  FOSRest Configuration
```
fos_rest:
    disable_csrf_role: ROLE_API
    param_fetcher_listener: true
    body_listener:
        array_normalizer: fos_rest.normalizer.camel_keys
    format_listener: true
    view:
        view_response_listener: force
        exception_wrapper_handler: 'BrauneDigital\ApiBaseBundle\View\ExceptionWrapperHandler'
    routing_loader:
        default_format: json
    body_converter:
        enabled: true
        validate: true
    exception:
        codes:
            'Doctrine\ORM\EntityNotFoundException': 403
        messages:
            'Doctrine\ORM\EntityNotFoundException': false
```

### NelmioCors Configuration
To support OPTIONS calls from your clients.
```
nelmio_cors:
   defaults:
       allow_credentials: false
       allow_origin: []
       allow_headers: []
       allow_methods: []
       expose_headers: []
       max_age: 0
       hosts: []
       origin_regex: false
   paths:
       '^/api/':
           allow_credentials: true
           allow_origin: ['*']
           allow_headers: ['*']
           allow_methods: ['POST', 'PUT', 'GET', 'DELETE', 'OPTIONS']
           max_age: 0
```


### Security.yml Configuration 
```
providers:
    braune_digital_api_base:
            id: braune_digital_api_base.security.apikey_user_provider
    firewalls:
        api_doc: #Open API Documentation
            pattern: ^/api/doc
            anonymous: true
            security: false
        api_login:
            pattern: ^/api/v1/login$
            anonymous: true
        api_password_reset:
            pattern: ^/api/v1/password-((request$)|(reset$))
            anonymous: true
        api: #Secured API-Area
            pattern: ^/api
            stateless: true #we are using tokens
            simple_preauth:
                authenticator: braune_digital_api_base.security.apikey_authenticator #use apikeys for authentication
            provider: braune_digital_api_base #use apikeys for authentication
```
## Usage
### BaseApiController
The BaseApiController provides the underlying logic to create api-endpoints fast and easy: Just extend the *BrauneDigital\ApiBaseBundle\Controller\BaseApiController* and add your functions:
```php
<?php

namespace BrauneDigital\DemoBundle\Controller\V1;

use BrauneDigital\ApiBaseBundle\Controller\BaseApiController;
use BrauneDigital\DemoBundle\Form\Type\ProjectType;
use BrauneDigital\Pitcher\BaseBundle\Entity\Company;
use BrauneDigital\Pitcher\BaseBundle\Entity\Project;
use Doctrine\Common\Collections\ArrayCollection;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author    Patrick Rathje <pr@braune-digital.com>
 * @copyright 2016 Braune Digital GmbH
 */
class ProjectController extends BaseApiController {

    protected function getRepository() {
        return $this->getDoctrine()->getRepository('BrauneDigitalDemoBundle:Project');
    }

    /**
     *
     * @ApiDoc(
     *  resource=false,
     *  section="Project",
     *  description="Get a project by id",
     *  requirements= {
     *      {"name": "id", "description":"Project-ID", "dataType": "integer"},
     *      {"name": "_format", "description":"Response-Format", "requirement": "json|xml|html", "dataType": "string"},
     *     {"name": "version", "description":"API-Version", "requirement": "json|xml|html", "dataType": "integer"}
     * }
     *)
     *
     * @param Request $request
     * @param $id
     * @return \FOS\RestBundle\View\View
     *
     * @Rest\Get("/projects/{id}", name="project_read", defaults={"_format": "json"})
     */
    public function readAction(Request $request, $id) {
        return parent::readAction($request, $id);
    }

    /**
     *
     * @ApiDoc(
     *  resource=false,
     *  section="Project",
     *  description="Get projects",
     *  requirements= {
     *      {"name": "_format", "description":"Response-Format", "requirement": "json|xml|html", "dataType": "string"},
     *     {"name": "version", "description":"API-Version", "requirement": "json|xml|html", "dataType": "integer"}
     *  }
     *)
     *
     * @param Request $request
     * @param $id
     * @return \FOS\RestBundle\View\View
     *
     * @Rest\Get("/projects", name="project_list", defaults={"_format": "json"})
     */
    public function listAction(Request $request) {
        return parent::listAction($request);
    }


    /**
     *
     * @ApiDoc(
     *  resource=false,
     *  section="Project",
     *  description="Create a project",
     * requirements= {
     *      {"name": "_format", "description":"Response-Format", "requirement": "json|xml|html", "dataType": "string"},
     *     {"name": "version", "description":"API-Version", "requirement": "json|xml|html", "dataType": "integer"}
     * },
     *  input="BrauneDigital\DemoBundle\Form\Type\ProjectType"
     *)
     *
     * @param Request $request
     * @param $id
     * @return \FOS\RestBundle\View\View
     *
     * @Rest\Post("/projects", name="project_create", defaults={"_format": "json"})
     */
    public function createAction(Request $request, $entity = null, $refresh = false, $formOptions = null) {
        $entity = new Project();
        return parent::createAction($request, $entity);
    }

    /**
     *
     * @ApiDoc(
     *  resource=false,
     *  section="Project",
     *  description="Update a project",
     * requirements= {
     *     {"name": "id", "description":"Project-ID", "dataType": "integer"},
     *      {"name": "_format", "description":"Response-Format", "requirement": "json|xml|html", "dataType": "string"},
     *     {"name": "version", "description":"API-Version", "requirement": "json|xml|html", "dataType": "integer"}
     * },
     *  input="BrauneDigital\DemoBundle\Form\Type\ProjectType"
     *)
     *
     * @param Request $request
     * @param $id
     * @return \FOS\RestBundle\View\View
     *
     * @Rest\Post("/projects/{id}", name="project_update", defaults={"_format": "json"})
     */
    public function updateAction(Request $request, $id, $refresh = false, $formOptions = null) {
        //add validation groups
        return parent::updateAction($request, $id, false, array('validation_groups' => array('ProjectUpdate')));
    }

    /**
     *
     * @ApiDoc(
     *  resource=false,
     *  section="Project",
     *  description="Delete a project",
     * requirements= {
     *     {"name": "id", "description":"Project-ID", "dataType": "integer"},
     *      {"name": "_format", "description":"Response-Format", "requirement": "json|xml|html", "dataType": "string"},
     *     {"name": "version", "description":"API-Version", "requirement": "json|xml|html", "dataType": "integer"}
     * }
     *)
     *
     * @param Request $request
     * @param $id
     * @return \FOS\RestBundle\View\View
     *
     * @Rest\Delete("/projects/{id}", name="project_delete", defaults={"_format": "json"})
     */
    public function deleteAction(Request $request, $id) {
        return parent::deleteAction($request, $id);
    }

    /**
    * Override the getForm Method for create and update functionalities, you may want to return different forms accordings to the mode
    **/
    protected function getForm($entity, $mode = '', $options = array()) {
        return $this->createForm(new ProjectType(), $entity, $options);
    }
}
```
You will have to specifiy a Repository and you may need to override the ```getForm``` function, if you want to create or update entities.
### Security System
To restrict the access to single resources you will need to use symfony voters. Take a look at the *BrauneDigital\ApiBaseBundle\Security\Authorization\Voter\BaseCrudVoter* which specifies the attributes that are used for the corresponding routes.
### Filter the ListAction
To filter list actions, one can override the ```createListQueryBuilder($alias = 'e')``` method. The querybuilder can be customized before returning.
### Serialization Groups (JMSSerializerBundle required)
Serialization Groups are used by the JMS Serializer to get a better control over the serialization process.

#### In your controller
You can easily Add Serialization Groups using ```$this->addSerializationGroup($group)``` or set them by calling ```$this->serializationGroups($groups)```.
#### Using the API-Request Header
Clients can also set serialization Groups by setting the ```serializationGroups```header in the request.
The Header may be a simple string, comma delimited or an array of strings.

### Api-Key Authentication
In order to use api-tokens, you have to add a token to your User-Class:
```php
    protected $token;
    
    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param mixed $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }
```

And add your DB-Mapping (e.g. DoctrineORM):
```yaml 
fields:
    token:
        type: string
        nullable: true
```

### Module Access
This Bundle provides a Module-Access Annotation, which can be used to restrict the access of specific routes to certain Roles.
In contrast to the Symfony Voting system, this is based on api-endpoints and not on ressources.
Import the annotation:
```php
use BrauneDigital\ApiBaseBundle\Annotation\ModuleAccess;
```
Add your modules:
```php
@ModuleAccess({"products", "sales"})
```
Or if you only use a single module:

```php
@ModuleAccess("products")
```
If the user has access to one of the modules, access will be granted.
Define the Modules in your configuration:
```yaml
braune_digital_api_base:
  modules:
    products:
        roles: ['ROLE_ADMIN', 'ROLE_CLIENT']
    sales:
        roles: ['ROLE_SALESMAN']
```
Example:
```php
    /**
     * @ApiDoc(
     *  resource=false,
     *  section="Your Section",
     *  description="A nice description",
     *  requirements= {
     *      {"name": "_format", "description":"Response-Format", "requirement": "json|xml|html", "dataType": "string"}
     * }
     *)
     * @Rest\Get("/products")
     * @ModuleAccess({"products", "sales"})
     * @param Request $request
     * @return mixed
     */
    public function listAction(Request $request) {
        return parent::listAction($request);
    }
```

### Variable Configuration
You can set the *_braune_digital_api_base_config* attribute in your request to append your custom configuration (*braune_digital_api_base.configuration*) to your response:  
```php
//send configuration
$request->attributes->set('_braune_digital_api_base_config', true);
```
The configuration will be available under the key *configuration* in your response.
## Suggestions  
### Api-Documentation
We suggest the usage of [NelmioApiDocBundle](https://github.com/nelmio/NelmioApiDocBundle/blob/master/Resources/doc/index.rst) for a clean and easy to use api documentation.
### JMSSerializerBundle
