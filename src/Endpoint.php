<?php

namespace YahooWeather;

use YahooWeather\Endpoint\ResponseException as EndpointResponseException;
use YahooWeather\Endpoint\Cache\CacheInterface;

/**
 * Basic Endpoint class for Yahoo Weather API
 * @author Mateusz Bajda
 * @package YahooWeather
 */
class Endpoint
{

    /**
     * Base URL for Yahoo! YQL query endpoint
     */
    const BASE_URL = 'http://query.yahooapis.com/v1/public/yql';

    /**
     * Metric units indicator
     */
    const UNITS_METRIC = 'c';

    /**
     * Imperial units indicator
     */
    const UNITS_IMPERIAL = 'f';

    /**
     * Condition constants
     * //Currently unused
     */
    const CONDITIONS = [
        '0' => 'tornado',
        '1' => 'tropical storm',
        '2' => 'hurricane',
        '3' => 'severe thunderstorms',
        '4' => 'thunderstorms',
        '5' => 'mixed rain and snow',
        '6' => 'mixed rain and sleet',
        '7' => 'mixed snow and sleet',
        '8' => 'freezing drizzle',
        '9' => 'drizzle',
        '10' => 'freezing rain',
        '11' => 'showers',
        '12' => 'showers',
        '13' => 'snow flurries',
        '14' => 'light snow showers',
        '15' => 'blowing snow',
        '16' => 'snow',
        '17' => 'hail',
        '18' => 'sleet',
        '19' => 'dust',
        '20' => 'foggy',
        '21' => 'haze',
        '22' => 'smoky',
        '23' => 'blustery',
        '24' => 'windy',
        '25' => 'cold',
        '26' => 'cloudy',
        '27' => 'mostly cloudy (night)',
        '28' => 'mostly cloudy (day)',
        '29' => 'partly cloudy (night)',
        '30' => 'partly cloudy (day)',
        '31' => 'clear (night)',
        '32' => 'sunny',
        '33' => 'fair (night)',
        '34' => 'fair (day)',
        '35' => 'mixed rain and hail',
        '36' => 'hot',
        '37' => 'isolated thunderstorms',
        '38' => 'scattered thunderstorms',
        '39' => 'scattered thunderstorms',
        '40' => 'scattered showers',
        '41' => 'heavy snow',
        '42' => 'scattered snow showers',
        '43' => 'heavy snow',
        '44' => 'partly cloudy',
        '45' => 'thundershowers',
        '46' => 'snow showers',
        '47' => 'isolated thundershowers',
        '3200' => 'not available',
    ];

    /**
     * Query scope
     */
    const SCOPE_ALL = '*';

    /**
     * Wind query scope
     */
    const SCOPE_WIND = 'wind';

    /**
     * Atmosphere query scope
     */
    const SCOPE_ATMOSPHERE = 'atmosphere';

    /**
     * Astronomy query scope
     */
    const SCOPE_ASTRONOMY = 'astronomy';

    /**
     * Forecast query scope
     */
    const SCOPE_FORECAST = 'item.forecast';

    /**
     * Conditions query scope
     */
    const SCOPE_CONDITIONS = 'item.condition';

    /**
     * Cache implementation instance
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Endpoint constructor.
     * @param CacheInterface $cache Cache implementation instance
     */
    public function __construct(CacheInterface $cache)
    {
        $this->setCache($cache);
    }

    /**
     * Returns cache implementation instance
     * @return CacheInterface
     */
    protected function getCache(): CacheInterface
    {
        return $this->cache;
    }

    /**
     * Sets cache implementation instance
     * @param CacheInterface $cache
     */
    protected function setCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    /**
     * Returns condition string based on condition code
     * @param string $code Condition code in string format
     * @return string
     */
    public function translateConditionCode(string $code): string
    {
        return self::CONDITIONS[$code];
    }

    /**
     * Provides WOEIDs based on location's text
     * @param string $location Location string
     * @return array
     */
    public function getWOEIDs(string $location): array
    {
        $query = 'select woeid, country.content, admin1.content, locality1.content from geo.places where text="' . $location . '"';
        return $this->query($query);
    }

    /**
     * Returns all data for specific WOEID
     * @param int $woeid Location WOEID
     * @param string $units Measurement units
     * @return array
     */
    public function getData(int $woeid, string $units = self::UNITS_METRIC): array
    {
        return $this->get(self::SCOPE_ALL, $woeid, $units);
    }

    /**
     * Returns wind data for specific WOEID
     * @param int $woeid Location WOEID
     * @param string $units Measurement units
     * @return array
     */
    public function getWind(int $woeid, string $units = self::UNITS_METRIC): array
    {
        return $this->get(self::SCOPE_WIND, $woeid, $units);
    }

    /**
     * Returns atmosphere data for specific WOEID
     * @param int $woeid Location WOEID
     * @param string $units Measurement units
     * @return array
     */
    public function getAtmosphere(int $woeid, string $units = self::UNITS_METRIC): array
    {
        return $this->get(self::SCOPE_ATMOSPHERE, $woeid, $units);
    }

    /**
     * Returns astronomy data for specific WOEID
     * @param int $woeid Location WOEID
     * @param string $units Measurement units
     * @return array
     */
    public function getAstronomy(int $woeid, string $units = self::UNITS_METRIC): array
    {
        return $this->get(self::SCOPE_ASTRONOMY, $woeid, $units);
    }

    /**
     * Returns forecast data for specific WOEID
     * @param int $woeid Location WOEID
     * @param string $units Measurement units
     * @return array
     */
    public function getForecast(int $woeid, string $units = self::UNITS_METRIC): array
    {
        return $this->get(self::SCOPE_FORECAST, $woeid, $units);
    }

    /**
     * Returns conditions data for specific WOEID
     * @param int $woeid Location WOEID
     * @param string $units Measurement units
     * @return array
     */
    public function getConditions(int $woeid, string $units = self::UNITS_METRIC): array
    {
        return $this->get(self::SCOPE_CONDITIONS, $woeid, $units);
    }

    /**
     * Returns scoped results for a specific WOEID
     * @param string $scope Scope of query
     * @param int $woeid Location WOEID
     * @param string $units Measurement units
     * @return array
     */
    private function get(string $scope, int $woeid, string $units = self::UNITS_METRIC): array
    {
        $query = 'select ' . $scope . ' from weather.forecast where woeid = ' . $woeid;
        if (strtolower($units) == self::UNITS_METRIC) {
            $query .= ' and u=\'' . self::UNITS_METRIC . '\'';
        }

        return $this->query($query);
    }

    /**
     * Performs a query
     * @param string $query to send with base url
     * @return array
     * @throws EndpointResponseException
     */
    private function query(string $query): array
    {
        $queryUrl = self::BASE_URL . '?q=' . urlencode($query) . '&format=json';
        $cacheVal = $this->getCache()->getValue($query);
        if ($cacheVal !== null) {
            return $cacheVal;
        }

        $result = @file_get_contents($queryUrl); // Silencing possible error

        $json = null;
        try {
            $json = json_decode($result, true);
            if ($json === null) {
                throw new EndpointResponseException('Invalid endpoint response.');
            } elseif ($json['query']['results'] === null) {
                throw new EndpointResponseException('No Yahoo Weather API response. Check if the API is reachable.');
            }
        } catch (EndpointResponseException | \Exception  $e) {
            throw new EndpointResponseException($e->getMessage());
        }

        $result = $json + [
                'lightLogo' => '<a href="https://www.yahoo.com/?ilc=401" target="_blank"> <img src="https://poweredby.yahoo.com/purple.png" width="134" height="29"/> </a>',
                'darkLogo' => '<a href="https://www.yahoo.com/?ilc=401" target="_blank"> <img src="https://poweredby.yahoo.com/white.png" width="134" height="29"/> </a>'
            ];

        $this->getCache()->setValue($query, $result);

        return $result;
    }

}
