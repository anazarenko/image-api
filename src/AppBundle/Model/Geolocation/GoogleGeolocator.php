<?php

namespace AppBundle\Model\Geolocation;

/**
 * Class Geolocator
 * @package AppBundle\Model\Geolocation
 */
class GoogleGeolocator extends Geolocator
{
    /**
     * Get address string by coordinates
     *
     * @param float $latitude
     * @param float $longitude
     * @return string
     */
    public function getAddress($latitude, $longitude) {
        $geolocation = $latitude.','.$longitude;
        $request = 'http://maps.googleapis.com/maps/api/geocode/json?latlng='.$geolocation.'&sensor=false';
        $file_contents = file_get_contents($request);
        $locationData = json_decode($file_contents);
        $address = null;

        if ($locationData->status === 'OK' && isset($locationData->results)) {
            $address = $locationData->results[0]->formatted_address;
        }

        return $address;
    }
}