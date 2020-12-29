<?php
//
//namespace Icamys\SitemapGenerator;
//
//use phpmock\phpunit\PHPMock;
//use phpmock\spy\Spy;
//use PHPUnit\Framework\TestCase;
//
//class RuntimeTest extends TestCase
//{
//    use PHPMock;
//
//    /**
//     * @var Runtime
//     */
//    private $r;
//
//    /**
//     * @var Spy for extension_loaded function
//     */
//    private $extensionLoadedSpy;
//
//    /**
//     * @var Spy for is_writable function
//     */
//    private $isWritableSpy;
//
//    public function testExtensionLoadedCall() {
//        $this->r->extension_loaded('curl');
//        $this->assertCount(1, $this->extensionLoadedSpy->getInvocations());
//        $this->assertEquals('curl', $this->extensionLoadedSpy->getInvocations()[0]->getArguments()[0]);
//    }
//
//    public function testIsWritableCall() {
//        $this->r->is_writable('path');
//        $this->assertCount(1, $this->isWritableSpy->getInvocations());
//        $this->assertEquals('path', $this->isWritableSpy->getInvocations()[0]->getArguments()[0]);
//    }
//
//    /**
//     * @throws \phpmock\MockEnabledException
//     */
//    protected function setUp(): void
//    {
//        $this->r = new Runtime();
//        $this->extensionLoadedSpy = new Spy(__NAMESPACE__, "extension_loaded", function (){});
//        $this->extensionLoadedSpy->enable();
//        $this->isWritableSpy = new Spy(__NAMESPACE__, "is_writable", function (){});
//        $this->isWritableSpy->enable();
//    }
//
//    protected function tearDown(): void
//    {
//        unset($this->r);
//        $this->extensionLoadedSpy->disable();
//        $this->isWritableSpy->disable();
//    }
//}