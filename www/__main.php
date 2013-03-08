<?php
chdir(__DIR__ . '/../');

ini_set('error_log', 'logs/site_error.log');
ini_set('date.timezone', 'UTC');
ini_set('log_errors', 'On');
ini_set('display_errors', 'Off');
ini_set('display_startup_errors', 'Off');
ini_set('error_reporting', E_ALL);
mb_internal_encoding('UTF-8');

require 'vendor/autoload.php';

use \PhpUnitsOfMeasure\Length;
use \PhpUnitsOfMeasure\Temperature;
use \PhpUnitsOfMeasure\Pressure;

$app = new Silex\Application();

$app->get(
    '/api/v1/getBreathability/{latitude}/{longitude}',
    function ($latitude, $longitude) use ($app) {

        $latitude = $app->escape($latitude);
        $longitude = $app->escape($longitude);

        $altitude = getAltitude($latitude, $longitude);

        // TODO: it's not obvious what the temperature should be. Wunderground.com api call?
        $temperature = new Temperature(0, 'C');

        $atmospheric_pressure = getAtmosphericPressure($altitude, $temperature);

        $breathability = isBreathable($atmospheric_pressure);

        $data = array(
            'latitude'             => $latitude,
            'longitude'            => $longitude,
            'altitude'             => (string) $altitude,
            'temperature'          => (string) $temperature,
            'atmospheric_pressure' => (string) $atmospheric_pressure,
            'breathability'        => $breathability
        );

        $data['html'] = (new Mustache_Engine())->render(
            file_get_contents('www/resources/templates/yes.mustache'),
            $data
        );

        return json_encode($data);
    }
);

$app->run();



/**
 * Given the altitude and temperature, find the atmospheric pressure
 *
 * @param \PhpUnitsOfMeasure\Length      $altitude    height above sea level, in meters
 * @param \PhpUnitsOfMeasure\Temperature $temperature temperature, in Kelvin
 *
 * @return \PhpUnitsOfMeasure\Pressure   the atmospheric pressure, in mbar
 */
function getAtmosphericPressure(Length $altitude, Temperature $temperature)
{
    // Thanks, wikipedia: http://en.wikipedia.org/wiki/Vertical_pressure_variation#In_the_context_of_Earth.27s_atmosphere

    // Sea level pressure (Pa)
    $P0 = 101325;

    // Mass of 1 mol of air (kg)
    $M = 0.0289644;

    // Gravitational accelleration (m/s^2)
    $g = 9.80665;

    // Altitude above sea level (m)
    $z = $altitude->toUnit('m');

    // Gas Constant (J/kg/m)
    $R = 8.31447;

    // Temperature (K)
    $T = $temperature->toUnit('K');

    // Calculated atmospheric pressure at altitude and temperature
    $P = $P0 * exp(-$M * $g * $z / $R / $T);
    return new Pressure($P, 'Pa');
}

/**
 * Answer, as best as possible, the question "Is the
 * air breathable here?".
 *
 * @param  \PhpUnitsOfMeasure\Pressure $atmospheric_pressure Atmospheric pressure in mbar
 *
 * @return string Yes, Maybe, or Nope, depending
 */
function isBreathable(Pressure $atmospheric_pressure)
{
    if ($atmospheric_pressure->toUnit('Pa') > 80609 ) {
        // Roughly equivalent to 6000' and below
        return 'Yes';

    } else if ($atmospheric_pressure->toUnit('Pa') > 69208 ) {
        // roughly between 6000' and 10,000'
        return 'Maybe';

    } else {
        // more than about 10,000'
        return 'Nope';

    }
}

/**
 * Given latitude and longitude, determine the altitude at that point on Earth.
 *
 * We're using earthtools.org's altitude service to fetch these values.
 *
 * @param  float $latitude  Latitude in decimal degrees
 * @param  float $longitude Longitude in decimal degrees
 *
 * @return \PhpUnitsOfMeasure\Length altitude above sea level
 */
function getAltitude($latitude, $longitude)
{
    $ch = curl_init();
    if (!$ch) {
        throw new \Exception('Error initializing curl resource.');
    }

    curl_setopt($ch, CURLOPT_URL, 'http://www.earthtools.org/height/'.$latitude.'/'.$longitude);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $return = curl_exec($ch);
    if ($return === false) {
        throw new \Exception('Error performing curl request.');
    }

    if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {
        throw new \Exception('HTTP status code other than 200 returned.');
    }

    curl_close($ch);

    $doc = new DOMDocument();
    $doc->loadXML($return);
    if ($return === false) {
        throw new \Exception('Error performing curl request.');
    }

    $xpath = new \DOMXPath($doc);
    $entries = $xpath->query('/height/meters');

    if ($entries->length != 1) {
        throw new \Exception('One and only one /height/meters element may be returned.');
    }

    return new Length($entries->item(0)->nodeValue, 'm');
}
