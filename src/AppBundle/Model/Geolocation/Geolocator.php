<?php

namespace AppBundle\Model\Geolocation;

/**
 * Class Geolocator
 * @package AppBundle\Model\Geolocation
 */
abstract class Geolocator implements GeolocatorInterface
{
    /**
     * Get address string
     * @param float $latitude
     * @param float $longitude
     * @return mixed
     */
    abstract function getAddress($latitude, $longitude);
}