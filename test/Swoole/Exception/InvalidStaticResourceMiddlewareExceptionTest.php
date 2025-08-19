<?php

declare(strict_types=1);

namespace QueueTest\Swoole\Exception;

use PHPUnit\Framework\TestCase;
use Queue\Swoole\Exception\InvalidStaticResourceMiddlewareException;

use function get_debug_type;
use function sprintf;

class InvalidStaticResourceMiddlewareExceptionTest extends TestCase
{
    public function testForMiddlewareAtPositionReturnsExpectedException(): void
    {
        $middleware = new \stdClass();
        $position   = 2;

        $exception = InvalidStaticResourceMiddlewareException::forMiddlewareAtPosition($middleware, $position);

        $this->assertContainsOnlyInstancesOf(InvalidStaticResourceMiddlewareException::class, [$exception]);

        $expectedMessage = sprintf(
            'Static resource middleware must be callable; received middleware of type "%s" in position %s',
            get_debug_type($middleware),
            $position
        );

        $this->assertSame($expectedMessage, $exception->getMessage());
    }
}
