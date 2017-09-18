<?php

namespace YahooWeather\Endpoint\Cache;

/**
 * Interface CacheInterface
 * @author Mateusz Bajda
 * @package YahooWeather\Endpoint\Cache
 */
interface CacheInterface
{

    /**
     * Returns cached value or NULL if value expired or no stored value found
     * @param string $query Query to be checked
     * @return array|null
     */
    public function getValue(string $query): ?array;

    /**
     * Sets value to be cached
     * @param string $query Query to be cached
     * @param array  $data  Data to be stored by cache
     */
    public function setValue(string $query, array $data): void;

}