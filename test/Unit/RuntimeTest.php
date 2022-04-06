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

    /**
     * @var Spy for curl_init function
     */
    private $curlInitSpy;

    /**
     * @var Spy for curl_setopt function
     */
    private $curlSetoptSpy;

    /**
     * @var Spy for curl_exec function
     */
    private $curlExecSpy;

    /**
     * @var Spy for curl_getinfo function
     */
    private $curlGetinfoSpy;


    /**
     * @var Spy for curl_error function
     */
    private $curlErrorSpy;

    public function testExtensionLoadedCall() {
        $this->r->extension_loaded('curl');
        $this->assertCount(1, $this->extensionLoadedSpy->getInvocations());
        $this->assertEquals('curl', $this->extensionLoadedSpy->getInvocations()[0]->getArguments()[0]);
    }

    public function testCurlInitCall() {
        $this->r->curl_init('init');
        $this->assertCount(1, $this->curlInitSpy->getInvocations());
        $this->assertEquals('init', $this->curlInitSpy->getInvocations()[0]->getArguments()[0]);
    }

    public function testCurlSetoptCall() {
        $this->r->curl_setopt(null, CURLOPT_RETURNTRANSFER, true);
        $this->assertCount(1, $this->curlSetoptSpy->getInvocations());
        $this->assertEquals(null, $this->curlSetoptSpy->getInvocations()[0]->getArguments()[0]);
        $this->assertEquals(CURLOPT_RETURNTRANSFER, $this->curlSetoptSpy->getInvocations()[0]->getArguments()[1]);
        $this->assertEquals(true, $this->curlSetoptSpy->getInvocations()[0]->getArguments()[2]);
    }

    public function testCurlExecCall() {
        $this->r->curl_exec(null);
        $this->assertCount(1, $this->curlExecSpy->getInvocations());
        $this->assertEquals(null, $this->curlExecSpy->getInvocations()[0]->getArguments()[0]);
    }

    public function testCurlGetinfoCall() {
        $this->r->curl_getinfo(null);
        $this->assertCount(1, $this->curlGetinfoSpy->getInvocations());
        $this->assertEquals(null, $this->curlGetinfoSpy->getInvocations()[0]->getArguments()[0]);
    }

    public function testCurlErrorCall() {
        $this->r->curl_error(null);
        $this->assertCount(1, $this->curlErrorSpy->getInvocations());
        $this->assertEquals(null, $this->curlErrorSpy->getInvocations()[0]->getArguments()[0]);
    }

    /**
     * @throws \phpmock\MockEnabledException
     */
    protected function setUp(): void
    {
        $this->r = new Runtime();
        $this->extensionLoadedSpy = new Spy(__NAMESPACE__, "extension_loaded", function (){});
        $this->extensionLoadedSpy->enable();
        $this->curlInitSpy = new Spy(__NAMESPACE__, "curl_init", function (){});
        $this->curlInitSpy->enable();
        $this->curlSetoptSpy = new Spy(__NAMESPACE__, "curl_setopt", function (){});
        $this->curlSetoptSpy->enable();
        $this->curlExecSpy = new Spy(__NAMESPACE__, "curl_exec", function (){});
        $this->curlExecSpy->enable();
        $this->curlGetinfoSpy = new Spy(__NAMESPACE__, "curl_getinfo", function (){});
        $this->curlGetinfoSpy->enable();
        $this->curlErrorSpy = new Spy(__NAMESPACE__, "curl_error", function (){});
        $this->curlErrorSpy->enable();
    }

    protected function tearDown(): void
    {
        unset($this->r);
        $this->extensionLoadedSpy->disable();
        $this->curlInitSpy->disable();
        $this->curlSetoptSpy->disable();
        $this->curlExecSpy->disable();
        $this->curlGetinfoSpy->disable();
        $this->curlErrorSpy->disable();
    }
}