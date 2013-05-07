<?php
namespace CanIBreatheHere;

use \PhpUnitsOfMeasure\PhysicalQuantity\Length;
use \PhpUnitsOfMeasure\PhysicalQuantity\Temperature;
use \PhpUnitsOfMeasure\PhysicalQuantity\Pressure;

/**
 * This class takes a given latitude and longitude, and
 * returns various values about that location's breathable
 * air.
 */
class BreathableAir
{
    /**
     * Geographic latitude.
     *
     * @var float decimal degrees
     */
    protected $latitude;

    /**
     * Geographic longitude.
     *
     * @var float decimal degrees
     */
    protected $longitude;

    /**
     * The temperature at the given latitude and longitude.
     *
     * @var \PhpUnitsOfMeasure\PhysicalQuantity\Temperature
     */
    protected $temperature;

    /**
     * The altitude at the given latitude and longitude.
     *
     * @var \PhpUnitsOfMeasure\PhysicalQuantity\Length
     */
    protected $altitude;

    /**
     * Stored the passed position values.
     *
     * @param float $latitude  the latitude, in decimal degrees
     * @param float $longitude the longitude, in decimal degrees
     *
     * @return void
     */
    public function __construct($latitude, $longitude)
    {
        $this->latitude    = $latitude;
        $this->longitude   = $longitude;
    }

    /**
     * Get the temperature at the latitude and longitude given.
     *
     * @TODO this is obviously a kludge, since we're assuming the temperature is always freezing.
     * Maybe there's a weather API I can use to source this data?
     *
     * @return \PhpUnitsOfMeasure\PhysicalQuantity\Temperature The temperature
     */
    public function getTemperature()
    {
        if (!$this->temperature) {
            $this->temperature = new Temperature(0, 'C');
        }

        return $this->temperature;
    }

    /**
     * Given latitude and longitude, determine the altitude at that point on Earth.
     *
     * We're using earthtools.org's altitude service to fetch these values.
     *
     * Also, the altitude is cached after the first request, to cut down on redundant
     * API requests.
     *
     * @throws \Exception if the curl request fails
     * @throws \Exception if the curl request returns a status code other than 200
     * @throws \Exception if the curl response doesn't have parseable XML
     * @throws \Exception if the curl response has too many height elements in it
     *
     * @return \PhpUnitsOfMeasure\PhysicalQuantity\Length altitude above sea level
     */
    public function getAltitude()
    {
        if (!$this->altitude) {

            $ch = curl_init();
            if (!$ch) {
                throw new \Exception('Error initializing curl resource.');
            }

            curl_setopt($ch, CURLOPT_URL, 'http://www.earthtools.org/height/'.$this->latitude.'/'.$this->longitude);
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

            $doc = new \DOMDocument();
            $doc->loadXML($return);
            if ($doc === false) {
                throw new \Exception('Error parsing XML response.');
            }

            $xpath = new \DOMXPath($doc);
            $entries = $xpath->query('/height/meters');

            if ($entries->length != 1) {
                throw new \Exception('One and only one /height/meters element may be returned.');
            }

            $this->altitude = new Length($entries->item(0)->nodeValue, 'm');
        }

        return $this->altitude;
    }

    /**
     * Given the altitude and temperature, find the atmospheric pressure
     *
     * Thanks, wikipedia: http://en.wikipedia.org/wiki/Vertical_pressure_variation#In_the_context_of_Earth.27s_atmosphere
     *
     * @return \PhpUnitsOfMeasure\PhysicalQuantity\Pressure the atmospheric pressure
     */
    public function getAtmosphericPressure()
    {
        // Sea level pressure (Pa)
        $P0 = 101325;

        // Mass of 1 mol of air (kg)
        $M = 0.0289644;

        // Gravitational accelleration (m/s^2)
        $g = 9.80665;

        // Altitude above sea level (m)
        $z = $this->getAltitude()->toUnit('m');

        // Gas Constant (J/kg/m)
        $R = 8.31447;

        // Temperature (K)
        $T = $this->getTemperature()->toUnit('K');

        // Calculated atmospheric pressure at altitude and temperature
        $P = $P0 * exp(-$M * $g * $z / $R / $T);

        return new Pressure($P, 'Pa');
    }

    /**
     * Answer, as best as possible, the question "Is the
     * air breathable here?".
     *
     * @return string Yes, Maybe, or Nope, depending
     */
    public function isBreathable()
    {
        $atmospheric_pressure = $this->getAtmosphericPressure();

        if ($atmospheric_pressure->toUnit('Pa') > 80609) {
            // Roughly equivalent to 6000' and below
            return 'Yes';

        } else if ($atmospheric_pressure->toUnit('Pa') > 69208) {
            // roughly between 6000' and 10,000'
            return 'Maybe';

        } else {
            // more than about 10,000'
            return 'Nope';

        }
    }
}
