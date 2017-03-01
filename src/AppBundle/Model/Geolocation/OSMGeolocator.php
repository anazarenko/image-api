<?php

namespace AppBundle\Model\Geolocation;

/**
 * Open Street Maps
 * Class OSMGeolocator
 * @package AppBundle\Model\Geolocation
 */
class OSMGeolocator extends Geolocator
{
    /**
     * Get address string by coordinates
     *
     * @param $latitude
     * @param $longitude
     * @return string
     */
    public function getAddress($latitude, $longitude) {
        $address = '';

//        $osm = new \Services_OpenStreetMap(['server' => 'http://open.mapquestapi.com/nominatim/v1/']);
//        $osm->setServer('mapquest');
//        $xml = $osm->setFormat('xml')->reverseGeocode("53.434343", "-6.4343343");

        return $address;
    }
}