<?php

namespace YahooWeather\Endpoint\Cache;

use YahooWeather\Endpoint\Cache\Exception as CacheException;

/**
 * FileCache implementation
 * @author Mateusz Bajda
 * @package YahooWeather\Endpoint
 */
class FileCache implements CacheInterface
{

    /**
     * SHA256 hashing algorithm
     */
    const ALGO_SHA256 = 'sha256';

    /**
     * Default cache TTL
     */
    const DEFAULT_TTL = 3600;

    /**
     * Cache directory used for storing files
     * @var string
     */
    protected $cacheDir = '';

    /**
     * Cache time to live
     * @var int
     */
    protected $cacheTTL = 0;

    /**
     * Returns cache time to live value
     * @return int
     */
    protected function getCacheTTL(): int
    {
        return $this->cacheTTL;
    }

    /**
     * Sets cache time to live
     * @param int $cacheTTL
     */
    protected function setCacheTTL(int $cacheTTL): void
    {
        $this->cacheTTL = $cacheTTL;
    }

    /**
     * Returns current cache directory
     * @return string
     */
    protected function getCacheDir(): string
    {
        return $this->cacheDir;
    }

    /**
     * Sets cache directory
     * @param string $cacheDir
     */
    protected function setCacheDir(string $cacheDir): void
    {
        $this->cacheDir = $cacheDir;
    }

    /**
     * FileCache constructor.
     * @param string $cacheDir Cache directory without leading and trailing directory separators
     * @param int $cacheTTL Cache time to live
     * @throws Exception
     */
    public function __construct(string $cacheDir, int $cacheTTL = self::DEFAULT_TTL)
    {
        $fullCacheDir = getcwd() . DIRECTORY_SEPARATOR . $cacheDir;

        if (!is_readable($fullCacheDir)) {
            throw new CacheException('Cache directory is not readable.');
        }

        if (!is_writable($fullCacheDir)) {
            throw new CacheException('Cache directory is not writable.');
        }

        $this->setCacheDir($fullCacheDir . DIRECTORY_SEPARATOR);
        $this->setCacheTTL($cacheTTL);
    }

    /**
     * Returns cached value or NULL if value expired or no stored value found
     * @param string $query Query to be checked
     * @return array|null
     */
    public function getValue(string $query): ?array
    {
        $hash = hash(self::ALGO_SHA256, $query);
        $filename = sprintf('%s%s', $this->getCacheDir(), $hash);
        if (!file_exists($filename)) {
            return null;
        } else {
            $fileContents = json_decode(file_get_contents($filename), true);
            if (!isset($fileContents['time'])) {
                return null;
            } elseif (($fileContents['time'] + $this->getCacheTTL()) < time()) {
                return null;
            }

            return $fileContents['data'];
        }

        return null;
    }

    /**
     * Sets value to be cached
     * @param string $query Query to be cached
     * @param array $data Data to be stored by cache
     */
    public function setValue(string $query, array $data): void
    {
        $hash = hash(self::ALGO_SHA256, $query);
        $filename = sprintf('%s%s', $this->getCacheDir(), $hash);
        file_put_contents($filename, json_encode([
            'time' => time(),
            'data' => $data,
        ]));
    }
}