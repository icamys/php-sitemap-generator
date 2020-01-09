<?php

namespace Icamys\SitemapGenerator;

use phpmock\phpunit\PHPMock;
use phpmock\spy\Spy;
use PHPUnit\Framework\TestCase;

class RuntimeTest extends TestCase
{
    use PHPMock;

    /**
     * @var Runtime
     */
    private $r;

    /**
     * @var Spy for extension_loaded function
     */
    private $extensionLoadedSpy;

    public function testExtensionLoadedCall() {
        $this->r->extension_loaded('curl');
        $this->assertCount(1, $this->extensionLoadedSpy->getInvocations());
        $this->assertEquals('curl', $this->extensionLoadedSpy->getInvocations()[0]->getArguments()[0]);
    }

    /**
     * @throws \phpmock\MockEnabledException
     */
    protected function setUp(): void
    {
        $this->r = new Runtime();
        $this->extensionLoadedSpy = new Spy(__NAMESPACE__, "extension_loaded", function (){});
        $this->extensionLoadedSpy->enable();
    }

    protected function tearDown(): void
    {
        unset($this->r);
        $this->extensionLoadedSpy->disable();
        $this->extensionLoadedSpy->disable();
    }
}