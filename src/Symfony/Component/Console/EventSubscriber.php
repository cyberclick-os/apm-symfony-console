<?php
declare(strict_types=1);

namespace PcComponentes\ElasticAPM\Symfony\Component\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;
use ZoiloMora\ElasticAPM\ElasticApmTracer;

final class EventSubscriber implements EventSubscriberInterface
{
    private ElasticApmTracer $elasticApmTracer;

    private array $transactions;
    private array $spans;

    public function __construct(ElasticApmTracer $elasticApmTracer)
    {
        $this->elasticApmTracer = $elasticApmTracer;
        $this->transactions = [];
        $this->spans = [];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => ['onConsoleCommandEvent'],
            ConsoleEvents::TERMINATE => ['onConsoleTerminateEvent'],
            ConsoleEvents::ERROR => ['onConsoleErrorEvent'],
        ];
    }

    public function onConsoleCommandEvent(ConsoleCommandEvent $event): void
    {
        if (false === $this->elasticApmTracer->active()) {
            return;
        }

        $command = $event->getCommand();

        if(str_contains($command->getName(), 'rabbitmq:consume')){
            return;
        }

        $input = $event->getInput();
        $queueArgument = '';
        try {
            $queueArgument = ' '.$input->getArgument('queue');
        } catch (Throwable){

        }
        $key = $this->transactionKey($command);

        if (0 !== \count($this->transactions)) {
            $this->spans[$key] = $this->elasticApmTracer->startSpan(
                $command->getName(). $queueArgument,
                'console',
            );
        }

        $this->transactions[$key] = $this->elasticApmTracer->startTransaction(
            $command->getName(). $queueArgument,
            'console',
        );
    }

    public function onConsoleTerminateEvent(ConsoleTerminateEvent $event): void
    {
        if (false === $this->elasticApmTracer->active()) {
            return;
        }

        $command = $event->getCommand();

        if(str_contains($command->getName(), 'rabbitmq:consume')){
            return;
        }

        $key = $this->transactionKey(
            $event->getCommand(),
        );

        $this->transactions[$key]->stop(
            (string) $event->getExitCode(),
        );

        if (true === \array_key_exists($key, $this->spans)) {
            $this->spans[$key]->stop();
        }

        if (\array_key_first($this->transactions) !== $key) {
            return;
        }

        unset($this->transactions[$key]);

        $this->elasticApmTracer->flush();
    }

    public function onConsoleErrorEvent(ConsoleErrorEvent $event): void
    {
        if (false === $this->elasticApmTracer->active()) {
            return;
        }
        
        if(str_contains($event->getCommand()->getName(), 'rabbitmq:consume')){
            return;
        }


        $this->elasticApmTracer->captureException(
            $event->getError(),
        );
    }

    private function transactionKey(Command $command): int
    {
        return \spl_object_id($command);
    }
}
