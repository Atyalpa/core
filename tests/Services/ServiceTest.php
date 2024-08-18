<?php

use Atyalpa\Core\Services\Service;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ServiceTest extends TestCase
{
    #[Test]
    public function it_has_load_method()
    {
        $serviceMock = $this->createMock(Service::class);
        $serviceMock->expects($this->any())
             ->method('load')
             ->willReturn(true);

        $this->assertTrue($serviceMock->load());
    }
}
