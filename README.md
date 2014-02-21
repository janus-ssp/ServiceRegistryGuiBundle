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
```