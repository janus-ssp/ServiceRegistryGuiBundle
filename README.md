Put this in your config.yml

```yml
# Assetic Configuration
assetic:
    debug:          %kernel.debug%
    use_controller: false
    bundles:
      - JanusServiceRegistryGuiBundle
    filters:
        cssrewrite: ~

# Twig Configuration
twig:
    debug:            %kernel.debug%
    strict_variables: %kernel.debug%
    #@todo correct controller name
    exception_controller: 'FOS\RestBundle\Controller\ExceptionController::showAction'

```

Put this in your config_dev.yml
```yml
assetic:
    use_controller: false
    debug: true
```

