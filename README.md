# Elastic APM for Symfony Console

This library supports Span traces of [Symfony Console](https://github.com/symfony/console) commands.

## Installation

1) Install via [composer](https://getcomposer.org/)

    ```shell script
    composer require pccomponentes/apm-symfony-console
    ```

## Usage

In all cases, an already created instance of [ElasticApmTracer](https://github.com/zoilomora/elastic-apm-agent-php) is assumed.

### Service Container (Symfony)

```yaml
PcComponentes\ElasticAPM\Symfony\Component\Console\EventSubscriber:
  class: PcComponentes\ElasticAPM\Symfony\Component\Console\EventSubscriber
  autoconfigure: true
  arguments:
    $elasticApmTracer: '@apm.tracer' # \ZoiloMora\ElasticAPM\ElasticApmTracer instance.
```

## License
Licensed under the [MIT license](http://opensource.org/licenses/MIT)

Read [LICENSE](LICENSE) for more information
