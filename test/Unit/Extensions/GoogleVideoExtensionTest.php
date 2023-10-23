<?php

namespace Unit\Extensions;

use Icamys\SitemapGenerator\Extensions\GoogleVideoExtension;
use PHPUnit\Framework\TestCase;
use \InvalidArgumentException;

class GoogleVideoExtensionTest extends TestCase
{
    public function testMissingFieldsException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required fields: title, description');

        $fields = [
            'thumbnail_loc' => 'test',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testMissingAtLeastOneFieldException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one of the following values are required but missing');

        $fields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testInvalidPlatformRelationshipValue() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid platform.relationship value. Allowed values are allow or deny.');

        $fields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'content_loc' => 'test',
            'platform' => [
                'relationship' => 'test'
            ]
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testMissingPlatformValueValue() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value platform.value is required.');

        $fields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'content_loc' => 'test',
            'platform' => []
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testInvalidPlatformValueValue() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid platform.relationship value. '
            .'Expecting a list of space-delimited platform types: web, mobile, tv.');

        $fields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'content_loc' => 'test',
            'platform' => [
                'value' => 'test'
            ]
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testTooLongDescriptionException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The field description must be less than or equal to a 2048');

        $fields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => str_repeat('a', 2100),
            'content_loc' => 'test',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testContentLocSameAsLocException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The field content_loc must not be the same as');

        $fields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'content_loc' => 'http://e.com',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testPlayerLocSameAsLocException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The field player_loc must not be the same as');

        $fields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'http://e.com',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testInvalidDurationException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The duration value should be between 1 and 28800');

        $fields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'test',
            'duration' => 30000,
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testExpirationDateException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid expiration_date value');

        $fields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'test',
            'expiration_date' => '10.10.2020',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testInvalidRatingException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid rating value');

        $fields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'test',
            'rating' => '5.5',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testPublicationDateException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid publication_date value');

        $fields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'test',
            'publication_date' => '10.10.2020',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testFamlilyFriendlyException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid family_friendly value');

        $fields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'test',
            'family_friendly' => 'yep',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testVideoRestrictionRelationshipException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid restriction.relationship value. Allowed values are allow or deny.');

        $fields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'test',
            'restriction' => [
                'relationship' => 'permit',
                'value' => 'UK',
            ],
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testVideoRestrictionValueException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value restriction.value is required');

        $fields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'test',
            'restriction' => [
                'relationship' => 'allow',
            ],
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testMissingPriceCurrency()
    {
        $loc = 'http://example.com';
        $extFields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'test',
            'price' => [
                [
                    'value' => 10.5
                ]
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value price.currency is required');

        GoogleVideoExtension::validate($loc, $extFields);
    }

    public function testMissingPriceValue()
    {
        $loc = 'http://example.com';
        $extFields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'test',
            'price' => [
                [
                    'currency' => 'USD'
                ]
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value price.value is required');

        GoogleVideoExtension::validate($loc, $extFields);
    }

    public function testInvalidPriceValue()
    {
        $loc = 'http://example.com';
        $extFields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'test',
            'price' => [
                [
                    'currency' => 'USD',
                    'value' => -5.0
                ]
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value price.value should be a float value more than 0');

        GoogleVideoExtension::validate($loc, $extFields);
    }

    public function testInvalidPriceType()
    {
        $loc = 'http://example.com';
        $extFields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'test',
            'price' => [
                [
                    'currency' => 'USD',
                    'value' => 10.5,
                    'type' => 'invalid'
                ]
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid price.type value. Allowed values are rent or own.');

        GoogleVideoExtension::validate($loc, $extFields);
    }

    public function testInvalidPriceResolution()
    {
        $loc = 'http://example.com';
        $extFields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'test',
            'price' => [
                [
                    'currency' => 'USD',
                    'value' => 10.5,
                    'resolution' => 'invalid'
                ]
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid price.resolution value. Allowed values are hd or sd.');

        GoogleVideoExtension::validate($loc, $extFields);
    }

    public function testInvalidRequiresSubscriptionValue()
    {
        $loc = 'http://example.com';
        $extFields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'test',
            'requires_subscription' => 'invalid'
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid requires_subscription value. Allowed values are yes or no.');

        GoogleVideoExtension::validate($loc, $extFields);
    }

    public function testUploaderValueTooLarge()
    {
        $loc = 'http://example.com';
        $extFields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'test',
            'uploader' => [
                'value' => str_repeat('a', 256)
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value uploader.value is too large, max 255 characters.');

        GoogleVideoExtension::validate($loc, $extFields);
    }

    public function testUploaderInfoDomainMismatch()
    {
        $loc = 'http://example.com';
        $extFields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'test',
            'uploader' => [
                'value' => 'UploaderName',
                'info' => 'http://different-domain.com/uploader-info'
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The uploader.info must be in the same domain as the <loc> tag.');

        GoogleVideoExtension::validate($loc, $extFields);
    }

    public function testInvalidLiveValue()
    {
        $loc = 'http://example.com';
        $extFields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'test',
            'live' => 'invalid'
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid live value. Allowed values are yes or no.');

        GoogleVideoExtension::validate($loc, $extFields);
    }

    public function testTooManyTags()
    {
        $loc = 'http://example.com';
        $extFields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'test',
            'tag' => array_fill(0, 33, 'tag')
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The array tag is too large, max 32 tags.');

        GoogleVideoExtension::validate($loc, $extFields);
    }

    public function testCategoryValueTooLarge()
    {
        $loc = 'http://example.com';
        $extFields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'test',
            'category' => str_repeat('a', 257)
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value category is too large, max 256 characters.');

        GoogleVideoExtension::validate($loc, $extFields);
    }
}
