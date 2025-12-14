<?php

namespace Flux\VerifactuBundle\Tests\Handler;

use Flux\VerifactuBundle\Handler\TestHandler;
use PHPUnit\Framework\TestCase;

final class TestHandlerTest extends TestCase
{
    public function testGetTestReturnsTest(): void
    {
        $handler = new TestHandler();
        $this->assertSame('test', $handler->getTest());
    }
}
