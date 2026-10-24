<?php

namespace Unit\Extensions;

use DOMDocument;
use DOMXPath;
use Icamys\SitemapGenerator\Extensions\GoogleVideoExtension;
use PHPUnit\Framework\TestCase;
use XMLWriter;

class GoogleVideoUploaderTest extends TestCase {

	/**
	 * @dataProvider uploaderNames
	 */
	//   Preserve uploader names as XML text and retain optional information links.
	//   Used by PHPUnit\Framework\TestCase\runBare.
	public function testUploaderTextRoundTrips(string $name, bool $withInfo): void {
		// Serialize a video inside a namespace-aware XML document.
		$info='https://example.com/uploader?a=1&b=2';
		$fields=[
			'thumbnail_loc' => 'https://example.com/thumbnail.jpg',
			'title'         => 'Video',
			'description'   => 'Description',
			'content_loc'   => 'https://example.com/video.mp4',
			'uploader'      => ['value' => $name],
		];
		if ($withInfo) {
			$fields['uploader']['info']=$info;
		}
		$writer=new XMLWriter();
		$writer->openMemory();
		$writer->startElement('url');
		$writer->writeAttribute('xmlns:video', 'http://www.google.com/schemas/sitemap-video/1.1');
		GoogleVideoExtension::writeVideoTag($writer, 'https://example.com/watch', $fields);
		$writer->endElement();

		// Parse the output to detect malformed XML, injected elements and double escaping.
		$document=new DOMDocument();
		$this->assertTrue(@$document->loadXML($writer->outputMemory()));
		$xpath=new DOMXPath($document);
		$xpath->registerNamespace('video', 'http://www.google.com/schemas/sitemap-video/1.1');
		$uploaders=$xpath->query('/url/video:video/video:uploader');
		$this->assertSame(1, $uploaders->length);
		$uploader=$uploaders->item(0);
		$this->assertSame($name, $uploader->textContent);
		$this->assertSame(0, $uploader->childElementCount);
		$this->assertSame($withInfo, $uploader->hasAttribute('info'));
		if ($withInfo) {
			$this->assertSame($info, $uploader->getAttribute('info'));
		}
	}


	//   Cover ordinary names, XML metacharacters, literal entities and Unicode.
	//   Used by GoogleVideoUploaderTest\testUploaderTextRoundTrips.
	public static function uploaderNames(): array {
		// Exercise names with and without optional uploader information.
		return [
			'plain'             => ['Example uploader', false],
			'ampersand'         => ['A & B', false],
			'ampersand with URL' => ['A & B', true],
			'markup'            => ['<tag>Uploader</tag>', true],
			'quotes'            => ['"Uploader\'s name"', false],
			'Unicode'           => ['Müller 東京', true],
			'literal entity'    => ['A &amp; B', true],
			'empty'             => ['', false],
		];
	}
}