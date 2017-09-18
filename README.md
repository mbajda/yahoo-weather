# Yahoo Weather
This library is a PHP implementation of Yahoo Weather API. It allows querying for many weather conditions for specified location on the world.
### Usage
First, you need to create instance of weather API endpoint. To do this, simply use following code:
```php
$cache = new \YahooWeather\Endpoint\Cache\FileCache('var\cache', 60*60*12);
$endpoint = new \YahooWeather\Endpoint($cache);
```
```YahooWeather\Endpoint``` is the main class of the library.

```YahooWeather\Endpoint\Cache\FileCache``` is the cache class to use with the endpoint. The constructor accepts up to 2 parameters. The first is the directory, in which the cache will be stored. The second is cache time-to-live - the time, for which the cache file will be used.

```php
$endpoint->getWOEIDs('Warsaw');
```
This line will allow you to get WOEID (location's ID) for a specified location.

```php
$endpoint->getData(523920);
```
```getData``` method allows you to get all the data for specified WOEID (which is the parameter for that and other methods).

### Additional information

The library utilizes connection with https://query.yahooapis.com - **Yahoo APIs**. There is a limit of queries made by day, which is equal to 2000.

Read more at 
https://developer.yahoo.com/weather