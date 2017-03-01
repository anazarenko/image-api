<?php

namespace AppBundle\Model\Geolocation;

interface GeolocatorInterface
{
    /**
     * @param float $latitude
     * @param float $longitude
     * @return mixed
     */
    public function getAddress($latitude, $longitude);
}