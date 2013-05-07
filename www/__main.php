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

use \CanIBreatheHere\BreathableAir;
use \PhpUnitsOfMeasure\PhysicalQuantity\Temperature;

$app = new Silex\Application();

$app->get(
    '/api/v1/getBreathability/{latitude}/{longitude}',
    function ($latitude, $longitude) use ($app) {

        $latitude = $app->escape($latitude);
        $longitude = $app->escape($longitude);

        $breathable_air = new BreathableAir($latitude, $longitude);

        $data = array(
            'latitude'             => $latitude,
            'longitude'            => $longitude,
            'temperature'          => (string) $breathable_air->getTemperature(),
            'altitude'             => (string) $breathable_air->getAltitude(),
            'atmospheric_pressure' => (string) $breathable_air->getAtmosphericPressure(),
            'breathability'        => $breathable_air->isBreathable()
        );

        $data['html'] = (new Mustache_Engine())->render(
            file_get_contents('www/resources/templates/yes.mustache'),
            $data
        );

        return json_encode($data);
    }
);

$app->run();
