# BrauneDigitalApiBaseBundle

##  FOSRest Configuration
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


## NelmioCors Configuration
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


## Security.yml Configuration 
```
providers:
    braune_digital_api_base:
            id: braune_digital_api_base.security.apikey_user_provider
    firewalls:
        api_doc:
            pattern: ^/api/doc
            anonymous: true
            security: false
        api_login:
            pattern: ^/api/v1/login$
            anonymous: true
        api_password_reset:
            pattern: ^/api/v1/password-((request$)|(reset$))
            anonymous: true
        api:
            pattern: ^/api
            stateless: true
            simple_preauth:
                authenticator: braune_digital_api_base.security.apikey_authenticator
            provider: braune_digital_api_base
```
