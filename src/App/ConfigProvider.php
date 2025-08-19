<?php

declare(strict_types=1);

namespace Queue\App;

use Dot\DependencyInjection\Factory\AttributedServiceFactory;
use Netglue\PsrContainer\Messenger\Container\MessageBusStaticFactory;
use Netglue\PsrContainer\Messenger\Container\Middleware\BusNameStampMiddlewareStaticFactory;
use Netglue\PsrContainer\Messenger\Container\Middleware\MessageHandlerMiddlewareStaticFactory;
use Netglue\PsrContainer\Messenger\Container\Middleware\MessageSenderMiddlewareStaticFactory;
use Netglue\PsrContainer\Messenger\HandlerLocator\OneToManyFqcnContainerHandlerLocator;
use Queue\App\Message\Message;
use Queue\App\Message\MessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            "dependencies" => $this->getDependencies(),
            'symfony'      => [
                'messenger' => [
                    'buses' => $this->busConfig(),
                ],
            ],
            'templates'    => $this->getTemplates(),
        ];
    }

    private function getDependencies(): array
    {
        return [
            "factories" => [
                "message_bus"                    => [MessageBusStaticFactory::class, "message_bus"],
                "message_bus_stamp_middleware"   => [BusNameStampMiddlewareStaticFactory::class, "message_bus"],
                "message_bus_sender_middleware"  => [MessageSenderMiddlewareStaticFactory::class, "message_bus"],
                "message_bus_handler_middleware" => [MessageHandlerMiddlewareStaticFactory::class, "message_bus"],
                MessageHandler::class            => AttributedServiceFactory::class,
            ],
            "aliases"   => [
                MessageBusInterface::class => "message_bus",
            ],
        ];
    }

    public function getTemplates(): array
    {
        return [
            'paths' => [
                'notification-email' => [__DIR__ . '/templates'],
            ],
        ];
    }

    private function busConfig(): array
    {
        return [
            "message_bus" => [
                // Means that it's an error if no handlers are defined for a given message
                'allows_zero_handlers' => false,

                /**
                 * Each bus needs middleware to do anything useful.
                 *
                 * Below is a minimal configuration to handle messages
                 */
                'middleware' => [
                    // … Middleware that inspects the message before it has been sent to a transport would go here.
                    "message_bus_stamp_middleware",
                    'message_bus_sender_middleware', // Sends messages via a transport if configured.
                    'message_bus_handler_middleware', // Executes the handlers configured for the message
                ],

                /**
                 * Map messages to one or more handlers:
                 *
                 * Two locators are shipped, 1 message type to 1 handler and 1 message type to many handlers.
                 * Both locators operate on the basis that handlers are available in the container.
                 */
                'handler_locator' => OneToManyFqcnContainerHandlerLocator::class,
                'handlers'        => [
                    Message::class => [MessageHandler::class],
                ],

                /**
                 * Routes define which transport(s) that messages dispatched on this bus should be sent with.
                 *
                 * The * wildcard applies to all messages.
                 * The transport for each route must be an array of one or more transport identifiers. Each transport
                 * is retrieved from the DI container by this value.
                 *
                 * An empty routes definition would mean that messages would be handled immediately and synchronously,
                 * i.e. no transport would be used.
                 *
                 * Route specific messages to specific transports by using the message name as the key.
                 */
                'routes' => [
                    Message::class => ["redis_transport"],
                ],
            ],
        ];
    }
}
