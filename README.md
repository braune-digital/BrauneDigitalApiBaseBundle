# BrauneDigitalApiBaseBundle

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
* JMSSerializerBundle
## Installation

Download using composer:
```bash
composer require braune-digital/api-base-bundle
```  
And enable the Bundle in your AppKernel.  
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
```

###  FOSRest Configuration  
Use of the 
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

## Api-Key Authentication
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

## Module Access
This Bundle provides a Module-Access Annotation, which can be used to restrict the access of specific routes to certain Roles.

## Variable Configuration
You can set the *_braune_digital_api_base_config* attribute in your request to append your custom configuration to your response:  
```php
//send configuration
$request->attributes->set('_braune_digital_api_base_config', true);
```
The configuration will be available under *configuration*
## Suggestions  
### Api-Documentation
We suggest the usage of [NelmioApiDocBundle](https://github.com/nelmio/NelmioApiDocBundle/blob/master/Resources/doc/index.rst) for a clean and easy to use api documentation.
