<?php

namespace Icamys\SitemapGenerator;

use CurlHandle;
use phpmock\phpunit\PHPMock;
use phpmock\spy\Spy;
use PHPUnit\Framework\TestCase;

class RuntimeTest extends TestCase
{
    use PHPMock;

    /**
     * @var CurlHandle
     */
    private CurlHandle $curlHandle;

    /**
     * @var Runtime
     */
    private Runtime $r;

    /**
     * @var Spy for extension_loaded function
     */
    private Spy $extensionLoadedSpy;

    /**
     * @var Spy for curl_init function
     */
    private Spy $curlInitSpy;

    /**
     * @var Spy for curl_setopt function
     */
    private Spy $curlSetoptSpy;

    /**
     * @var Spy for curl_exec function
     */
    private Spy $curlExecSpy;

    /**
     * @var Spy for curl_getinfo function
     */
    private Spy $curlGetinfoSpy;


    /**
     * @var Spy for curl_error function
     */
    private $curlErrorSpy;

    public function testExtensionLoadedCall() {
        $_ = $this->r->extension_loaded('curl');
        $this->assertCount(1, $this->extensionLoadedSpy->getInvocations());
        $this->assertEquals('curl', $this->extensionLoadedSpy->getInvocations()[0]->getArguments()[0]);
    }

    public function testCurlInitCall() {
        $this->r->curl_init('init');
        $this->assertCount(1, $this->curlInitSpy->getInvocations());
        $this->assertEquals('init', $this->curlInitSpy->getInvocations()[0]->getArguments()[0]);
    }

    public function testCurlSetoptCall() {
        $this->r->curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, true);
        $this->assertCount(1, $this->curlSetoptSpy->getInvocations());
        $this->assertEquals($this->curlHandle, $this->curlSetoptSpy->getInvocations()[0]->getArguments()[0]);
        $this->assertEquals(CURLOPT_RETURNTRANSFER, $this->curlSetoptSpy->getInvocations()[0]->getArguments()[1]);
        $this->assertTrue($this->curlSetoptSpy->getInvocations()[0]->getArguments()[2]);
    }

    public function testCurlExecCall() {
        $this->r->curl_exec($this->curlHandle);
        $this->assertCount(1, $this->curlExecSpy->getInvocations());
        $this->assertEquals($this->curlHandle, $this->curlExecSpy->getInvocations()[0]->getArguments()[0]);
    }

    public function testCurlGetinfoCall() {
        $this->r->curl_getinfo($this->curlHandle);
        $this->assertCount(1, $this->curlGetinfoSpy->getInvocations());
        $this->assertEquals($this->curlHandle, $this->curlGetinfoSpy->getInvocations()[0]->getArguments()[0]);
    }

    public function testCurlErrorCall() {
        $this->r->curl_error($this->curlHandle);
        $this->assertCount(1, $this->curlErrorSpy->getInvocations());
        $this->assertEquals($this->curlHandle, $this->curlErrorSpy->getInvocations()[0]->getArguments()[0]);
    }

    /**
     * @throws \phpmock\MockEnabledException
     */
    protected function setUp(): void
    {
        $this->curlHandle = curl_init();
        $this->r = new Runtime();
        $this->extensionLoadedSpy = new Spy(__NAMESPACE__, "extension_loaded", function (){
            return true;
        });
        $this->extensionLoadedSpy->enable();
        $this->curlInitSpy = new Spy(__NAMESPACE__, "curl_init", function (){
            return $this->curlHandle;
        });
        $this->curlInitSpy->enable();
        $this->curlSetoptSpy = new Spy(__NAMESPACE__, "curl_setopt", function (){
            return true;
        });
        $this->curlSetoptSpy->enable();
        $this->curlExecSpy = new Spy(__NAMESPACE__, "curl_exec", function (){
            return "";
        });
        $this->curlExecSpy->enable();
        $this->curlGetinfoSpy = new Spy(__NAMESPACE__, "curl_getinfo", function (){});
        $this->curlGetinfoSpy->enable();
        $this->curlErrorSpy = new Spy(__NAMESPACE__, "curl_error", function (){
            return "";
        });
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