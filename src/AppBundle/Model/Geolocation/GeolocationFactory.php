<?php

namespace AppBundle\Model\Geolocation;

final class GeolocationFactory
{
    const TYPE_GOOGLE = 1;
    const TYPE_OPEN_STREET_MAP = 2;

    /**
     * @param int $type
     * @return GeolocatorInterface
     */
    public static function create($type)
    {
        if ($type == self::TYPE_GOOGLE) {
            return new GoogleGeolocator();
        }

        if ($type == self::TYPE_OPEN_STREET_MAP) {
            return new OSMGeolocator();
        }

        throw new \InvalidArgumentException('Unknown type given');
    }
}