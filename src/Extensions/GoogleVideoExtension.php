<?php


namespace Icamys\SitemapGenerator\Extensions;


use DateTime;
use InvalidArgumentException;
use XMLWriter;


class GoogleVideoExtension
{
    private static $requiredFields = [
        'thumbnail_loc',
        'title',
        'description',
    ];

    private static $requiredEitherFields = [
        'content_loc',
        'player_loc',
    ];

    private static $platforms = [
        'web',
        'mobile',
        'tv',
    ];

    public static function writeVideoTag(XMLWriter $xmlWriter, string $loc, array $extFields)
    {
        self::validate($loc, $extFields);

        $xmlWriter->startElement('video:video');
        $xmlWriter->writeElement('video:thumbnail_loc', $extFields['thumbnail_loc']);
        $xmlWriter->writeElement('video:title', htmlentities($extFields['title'], ENT_QUOTES));
        $xmlWriter->writeElement('video:description', htmlentities($extFields['description'], ENT_QUOTES));

        if (isset($extFields['content_loc'])) {
            $xmlWriter->writeElement('video:content_loc', $extFields['content_loc']);
        }
        if (isset($extFields['player_loc'])) {
            $xmlWriter->writeElement('video:content_loc', $extFields['player_loc']);
        }
        if (isset($extFields['duration'])) {
            $xmlWriter->writeElement('video:duration', $extFields['duration']);
        }
        if (isset($extFields['expiration_date'])) {
            $xmlWriter->writeElement('video:expiration_date', $extFields['expiration_date']);
        }
        if (isset($extFields['rating'])) {
            $xmlWriter->writeElement('video:rating', $extFields['rating']);
        }
        if (isset($extFields['view_count'])) {
            $xmlWriter->writeElement('video:view_count', $extFields['view_count']);
        }
        if (isset($extFields['publication_date'])) {
            $xmlWriter->writeElement('video:publication_date', $extFields['publication_date']);
        }
        if (isset($extFields['family_friendly'])) {
            $xmlWriter->writeElement('video:family_friendly', $extFields['family_friendly']);
        }
        if (isset($extFields['restriction'])) {
            $xmlWriter->startElement('video:restriction');
            if (isset($extFields['restriction']['relationship'])) {
                $xmlWriter->writeAttribute('relationship', $extFields['restriction']['relationship']);
            }
            $xmlWriter->writeRaw($extFields['restriction']['value']);
            $xmlWriter->endElement();
        }
        if (isset($extFields['platform'])) {
            $xmlWriter->startElement('video:platform');
            if (isset($extFields['platform']['relationship'])) {
                $xmlWriter->writeAttribute('relationship', $extFields['platform']['relationship']);
            }
            $xmlWriter->writeRaw($extFields['platform']['value']);
            $xmlWriter->endElement();
        }
        if (isset($extFields['price'])) {
            foreach ($extFields['price'] as $price) {
                $xmlWriter->startElement('video:price');
                $xmlWriter->writeAttribute('currency', $price['currency']);
                if (isset($price['type'])) {
                    $xmlWriter->writeAttribute('type', $price['type']);
                }
                if (isset($price['resolution'])) {
                    $xmlWriter->writeAttribute('resolution', $price['resolution']);
                }
                $xmlWriter->writeRaw($price['value']);
                $xmlWriter->endElement();
            }
        }
        if (isset($extFields['requires_subscription'])) {
            $xmlWriter->writeElement('video:requires_subscription', $extFields['requires_subscription']);
        }
        if (isset($extFields['uploader'])) {
            $xmlWriter->startElement('video:uploader');
            if (isset($extFields['uploader']['info'])) {
                $xmlWriter->writeAttribute('info', $extFields['uploader']['info']);
            }
            $xmlWriter->writeRaw($extFields['uploader']['value']);
            $xmlWriter->endElement();
        }
        if (isset($extFields['live'])) {
            $xmlWriter->writeElement('video:live', $extFields['live']);
        }
        if (isset($extFields['tag'])) {
            foreach ($extFields['tag'] as $tag) {
                $xmlWriter->writeElement('video:tag', $tag);
            }
        }
        if (isset($extFields['category'])) {
            $xmlWriter->writeElement('video:category', $extFields['category']);
        }

        $xmlWriter->endElement();
    }

    public static function validate($loc, $extFields)
    {
        $extFieldNames = array_keys($extFields);

        if (count(array_intersect(self::$requiredFields, $extFieldNames)) !== count(self::$requiredFields)) {
            throw new InvalidArgumentException(
                sprintf("Missing required fields: %s", implode(', ', array_diff(self::$requiredFields, $extFieldNames)))
            );
        }
        if (count(array_intersect(self::$requiredEitherFields, $extFieldNames)) < 1) {
            throw new InvalidArgumentException(
                sprintf("At least one of the following values are required but missing: %s",
                    implode(', ', self::$requiredEitherFields)
                )
            );
        }
        if (mb_strlen($extFields['description']) > 2048) {
            throw new InvalidArgumentException('The field description must be less than or equal to a 2048');
        }
        if (isset($extFields['content_loc']) && $extFields['content_loc'] === $loc) {
            throw new InvalidArgumentException('The field content_loc must not be the same as the <loc> URL.');
        }
        if (isset($extFields['player_loc']) && $extFields['player_loc'] === $loc) {
            throw new InvalidArgumentException('The field player_loc must not be the same as the <loc> URL.');
        }
        if (isset($extFields['duration']) && !(1 <= $extFields['duration'] && $extFields['duration'] <= 28800)) {
            throw new InvalidArgumentException('The duration value should be between 1 and 28800');
        }
        if (isset($extFields['expiration_date'])
            && DateTime::createFromFormat(DateTime::ATOM, $extFields['expiration_date']) === false
            && DateTime::createFromFormat('Y-m-d', $extFields['expiration_date']) === false
        ) {
            throw new InvalidArgumentException('Invalid expiration_date value. ' .
                'Supported values are complete date (YYYY-MM-DD) or complete date plus hours, ' .
                'minutes and seconds, and timezone (YYYY-MM-DDThh:mm:ss+TZD)');
        }
        if (isset($extFields['rating']) && !in_array($extFields['rating'], range(0.0, 5.0, 0.1))) {
            throw new InvalidArgumentException(
                'Invalid rating value. ' .
                'Supported values are float numbers in the range 0.0 (low) to 5.0 (high), inclusive.'
            );
        }
        if (isset($extFields['publication_date'])
            && DateTime::createFromFormat(DateTime::ATOM, $extFields['publication_date']) === false
            && DateTime::createFromFormat('Y-m-d', $extFields['publication_date']) === false
        ) {
            throw new InvalidArgumentException('Invalid publication_date value. ' .
                'Supported values are complete date (YYYY-MM-DD) or complete date plus hours, ' .
                'minutes and seconds, and timezone (YYYY-MM-DDThh:mm:ss+TZD)');
        }
        if (isset($extFields['family_friendly']) && !in_array($extFields['family_friendly'], ['yes', 'no'])) {
            throw new InvalidArgumentException('Invalid family_friendly value. ' .
                'yes (or omitted) if the video can be available with SafeSearch on. ' .
                'no if the video should be available only with SafeSearch off.');
        }
        if (isset($extFields['restriction'])) {
            if (isset($extFields['restriction']['relationship'])
                && !in_array($extFields['restriction']['relationship'], ['allow', 'deny'])) {
                throw new InvalidArgumentException('Invalid restriction.relationship value. Allowed values are allow or deny.');
            }
            if (!isset($extFields['restriction']['value'])) { // todo: country codes in ISO 3166 format
                throw new InvalidArgumentException('Value restriction.value is required');
            }
        }
        if (isset($extFields['platform'])) {
            if (isset($extFields['platform']['relationship'])
                && !in_array($extFields['platform']['relationship'], ['allow', 'deny'])) {
                throw new InvalidArgumentException('Invalid platform.relationship value. Allowed values are allow or deny.');
            }
            if (!isset($extFields['platform']['value'])) {
                throw new InvalidArgumentException('Value platform.value is required');
            }

            $platformValues = explode(' ', $extFields['platform']['value']);

            if (count(array_diff($platformValues, static::$platforms)) > 0) {
                throw new InvalidArgumentException(
                    'Invalid platform.relationship value. ' .
                    'Expecting a list of space-delimited platform types: ' .
                    implode(', ', self::$platforms)
                );
            }
        }
        if (isset($extFields['price'])) {
            foreach ($extFields['price'] as $price) {
                if (!isset($price['currency'])) {
                    throw new InvalidArgumentException('Value price.currency is required');
                }
                if (!isset($price['value'])) {
                    throw new InvalidArgumentException('Value price.value is required');
                }
                if ($price['value'] <= 0 || is_float($price['value']) === false) {
                    throw new InvalidArgumentException('Value price.value should be a float value more than 0');
                }
                if (isset($price['type']) && !in_array($price['type'], ['rent', 'own'])) {
                    throw new InvalidArgumentException(
                        'Invalid price.type value. Allowed values are rent or own.'
                    );
                }
                if (isset($price['resolution']) && !in_array($price['resolution'], ['hd', 'sd'])) {
                    throw new InvalidArgumentException(
                        'Invalid price.resolution value. Allowed values are hd or sd.'
                    );
                }
            }
        }
        if (isset($extFields['requires_subscription']) && !in_array($extFields['requires_subscription'], ['yes', 'no'])) {
            throw new InvalidArgumentException(
                'Invalid requires_subscription value. Allowed values are yes or no.'
            );
        }
        if (isset($extFields['uploader'])) {
            if (mb_strlen($extFields['uploader']['value']) > 255) {
                throw new InvalidArgumentException(
                    'Value uploader.value is too large, max 255 characters.'
                );
            }
            if (isset($extFields['uploader']['info'])) {
                $locDomain = parse_url($loc, PHP_URL_HOST);
                $infoDomain = parse_url($extFields['uploader']['info'], PHP_URL_HOST);
                if ($locDomain !== $infoDomain) {
                    throw new InvalidArgumentException(
                        'The uploader.info must be in the same domain as the <loc> tag.'
                    );
                }
            }
        }
        if (isset($extFields['live']) && !in_array($extFields['live'], ['yes', 'no'])) {
            throw new InvalidArgumentException(
                'Invalid live value. Allowed values are yes or no.'
            );
        }
        if (isset($extFields['tag'])) {
            if (count($extFields['tag']) > 32) {
                throw new InvalidArgumentException(
                    'The array tag is too large, max 32 tags.'
                );
            }
        }
        if (isset($extFields['category']) && mb_strlen($extFields['category']) > 256) {
            throw new InvalidArgumentException(
                'Value category is too large, max 256 characters.'
            );
        }
    }
}