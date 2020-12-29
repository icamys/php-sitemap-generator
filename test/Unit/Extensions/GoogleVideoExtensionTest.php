<?php

use Icamys\SitemapGenerator\Extensions\GoogleVideoExtension;
use PHPUnit\Framework\TestCase;

class GoogleVideoExtensionTest extends TestCase
{
    public function testMissingFieldsException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required fields: video:title, video:description');

        $fields = [
            'video:thumbnail_loc' => 'test',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testMissingAtLeastOneFieldException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one of the following values are required but missing');

        $fields = [
            'video:thumbnail_loc' => 'test',
            'video:title' => 'test',
            'video:description' => 'test',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testTooLongDescriptionException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The field video:description must be less than or equal to a 2048');

        $fields = [
            'video:thumbnail_loc' => 'test',
            'video:title' => 'test',
            'video:description' => str_repeat('a', 2100),
            'video:content_loc' => 'test',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testContentLocSameAsLocException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The field video:content_loc must not be the same as');

        $fields = [
            'video:thumbnail_loc' => 'test',
            'video:title' => 'test',
            'video:description' => 'test',
            'video:content_loc' => 'http://e.com',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testPlayerLocSameAsLocException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The field video:player_loc must not be the same as');

        $fields = [
            'video:thumbnail_loc' => 'test',
            'video:title' => 'test',
            'video:description' => 'test',
            'video:player_loc' => 'http://e.com',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testInvalidDurationException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The video:duration value should be between 1 and 28800');

        $fields = [
            'video:thumbnail_loc' => 'test',
            'video:title' => 'test',
            'video:description' => 'test',
            'video:player_loc' => 'test',
            'video:duration' => 30000,
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testExpirationDateException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid video:expiration_date value');

        $fields = [
            'video:thumbnail_loc' => 'test',
            'video:title' => 'test',
            'video:description' => 'test',
            'video:player_loc' => 'test',
            'video:expiration_date' => '10.10.2020',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testInvalidRatingException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid video:rating value');

        $fields = [
            'video:thumbnail_loc' => 'test',
            'video:title' => 'test',
            'video:description' => 'test',
            'video:player_loc' => 'test',
            'video:rating' => '5.5',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testPublicationDateException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid video:publication_date value');

        $fields = [
            'video:thumbnail_loc' => 'test',
            'video:title' => 'test',
            'video:description' => 'test',
            'video:player_loc' => 'test',
            'video:publication_date' => '10.10.2020',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testFamlilyFriendlyException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid video:family_friendly value');

        $fields = [
            'video:thumbnail_loc' => 'test',
            'video:title' => 'test',
            'video:description' => 'test',
            'video:player_loc' => 'test',
            'video:family_friendly' => 'yep',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testVideoRestrictionRelationshipException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid video:restriction.relationship value. Allowed values are allow or deny.');

        $fields = [
            'video:thumbnail_loc' => 'test',
            'video:title' => 'test',
            'video:description' => 'test',
            'video:player_loc' => 'test',
            'video:restriction' => [
                'relationship' => 'permit',
                'value' => 'UK',
            ],
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testVideoRestrictionValueException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value video:restriction.value is required');

        $fields = [
            'video:thumbnail_loc' => 'test',
            'video:title' => 'test',
            'video:description' => 'test',
            'video:player_loc' => 'test',
            'video:restriction' => [
                'relationship' => 'allow',
            ],
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }
}