<?php

namespace Khalidmaquilang\SensorsPlus\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool startAccelerometer(int $intervalMs = 200)
 * @method static bool stopAccelerometer()
 * @method static bool startUserAccelerometer(int $intervalMs = 200)
 * @method static bool stopUserAccelerometer()
 * @method static bool startGyroscope(int $intervalMs = 200)
 * @method static bool stopGyroscope()
 * @method static bool startMagnetometer(int $intervalMs = 200)
 * @method static bool stopMagnetometer()
 * @method static bool startBarometer(int $intervalMs = 200)
 * @method static bool stopBarometer()
 * @method static bool isAvailable(\Khalidmaquilang\SensorsPlus\Sensor $sensor)
 * @method static object|null getStatus()
 *
 * @see \Khalidmaquilang\SensorsPlus\SensorsPlus
 */
class SensorsPlus extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Khalidmaquilang\SensorsPlus\SensorsPlus::class;
    }
}
