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

$app = new Silex\Application();

$app->get(
    '/api/v1/getBreathability/{latitude}/{longitude}',
    function ($latitude, $longitude) use ($app) {

        $latitude = $app->escape($latitude);
        $longitude = $app->escape($longitude);

        $altitude = getAltitude($latitude, $longitude);

        return json_encode(
            array(
                'latitude'        => $latitude,
                'longitude'       => $longitude,
                'altitude_meters' => $altitude,
                'oxygen_percent'  => 100,                                // TODO this value needs to be calculated
                'breathability'   => 'Yes',                              // TODO this value needs to be generated - Something like (Yes, No, Maybe)
                'html'            => "You are at $altitude meters",      // TODO this content needs to be generated in a better way (not to mention expanded on).
            )
        );
    }
);

$app->run();


/**
 * Given latitude and longitude, determine the altitude at that point on Earth.
 *
 * We're using earthtools.org's altitude service to fetch these values.
 *
 * @param  integer $latitude  Latitude in decimal degrees
 * @param  integer $longitude Longitude in decimal degrees
 *
 * @return integer altitude above sea level in meters
 */
function getAltitude($latitude, $longitude)
{
    $ch = curl_init();
    if (!$ch) {
        throw new Exception('Error initializing curl resource.');
    }

    curl_setopt($ch, CURLOPT_URL, 'http://www.earthtools.org/height/'.$latitude.'/'.$longitude);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $return = curl_exec($ch);
    if ($return === false) {
        throw new Exception('Error performing curl request.');
    }

    if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {
        throw new Exception('HTTP status code other than 200 returned.');
    }

    curl_close($ch);

    $doc = new DOMDocument();
    $doc->loadXML($return);
    if ($return === false) {
        throw new Exception('Error performing curl request.');
    }

    $xpath = new DOMXPath($doc);
    $entries = $xpath->query('/height/meters');

    if ($entries->length != 1) {
        throw new Exception('One and only one /height/meters element may be returned.');
    }

    return $entries->item(0)->nodeValue;
}
