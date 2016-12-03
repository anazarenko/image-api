<?php

namespace AppBundle\Manager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WeatherManager
 * @package AppBundle\Manager
 */
class WeatherManager
{
    /** @var string Api Key */
    private $apiKey;

    /** @var ContainerInterface */
    private $container;

    /**
     * WeatherManager constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->apiKey = $this->container->getParameter('open_weather_api_key');
    }

    /**
     * @param $latitude
     * @param $longitude
     *
     * @return string
     */
    public function getWeatherByCoords($latitude, $longitude)
    {
        $weatherString = '';

        $weatherParams = 'lat='.$latitude.'&lon='.$longitude.'&appid='.$this->apiKey;
        try {
            $request = 'http://api.openweathermap.org/data/2.5/weather?' . $weatherParams;
            $file_contents = file_get_contents($request);
            $response = json_decode($file_contents);

            if (isset($response->weather[0])) {
                $weatherString = $response->weather[0]->main;
            }

        } catch(\Exception $e) {
            $weatherString = 'The weather is not available right now';
        }

        return $weatherString;
    }
}