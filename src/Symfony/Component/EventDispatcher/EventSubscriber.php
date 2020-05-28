<?php
declare(strict_types=1);

namespace PcComponentes\ElasticAPM\Symfony\Component\EventDispatcher;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use ZoiloMora\ElasticAPM\ElasticApmTracer;
use ZoiloMora\ElasticAPM\Events\Transaction\Transaction;

final class EventSubscriber implements EventSubscriberInterface
{
    private ElasticApmTracer $elasticApmTracer;

    private Transaction $transaction;

    public function __construct(ElasticApmTracer $elasticApmTracer)
    {
        $this->elasticApmTracer = $elasticApmTracer;
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
        $command = $event->getCommand();

        $this->transaction = $this->elasticApmTracer->startTransaction(
            $command->getName(),
            'console'
        );
    }

    public function onConsoleTerminateEvent(ConsoleTerminateEvent $event): void
    {
        $this->transaction->stop(
            (string) $event->getExitCode()
        );

        $this->elasticApmTracer->flush();
    }

    public function onConsoleErrorEvent(ConsoleErrorEvent $event): void
    {
        $this->elasticApmTracer->captureException(
            $event->getError()
        );

        $this->transaction->stop(
            (string) $event->getExitCode()
        );
    }
}
