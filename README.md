# SensorsPlus Plugin for NativePHP Mobile

A NativePHP Mobile plugin that exposes the accelerometer, user-accelerometer (gravity removed), gyroscope, magnetometer and barometer sensors to your Laravel/Livewire mobile app.

## Installation

```bash
composer require khalidmaquilang/sensors-plus
```

## Usage

Start a sensor, then listen for its readings as they arrive. Sensor data is asynchronous, so `start*()` only confirms the native listener came up -- the actual values are pushed to your app as events.

```php
use Khalidmaquilang\SensorsPlus\Facades\SensorsPlus;
use Khalidmaquilang\SensorsPlus\Sensor;

SensorsPlus::startAccelerometer();      // uses SensorsPlus::INTERVAL_NORMAL (~200ms) by default
SensorsPlus::startGyroscope(SensorsPlus::INTERVAL_GAME);
SensorsPlus::startMagnetometer();
SensorsPlus::startUserAccelerometer();  // acceleration with gravity removed
SensorsPlus::startBarometer();          // note: iOS ignores the interval for the barometer

// ... later
SensorsPlus::stopAccelerometer();

// Availability
$available = SensorsPlus::isAvailable(Sensor::Gyroscope); // bool
$status = SensorsPlus::getStatus();                       // object with a bool per sensor
```

Available update-interval constants: `SensorsPlus::INTERVAL_FASTEST`, `INTERVAL_GAME`, `INTERVAL_UI`, `INTERVAL_NORMAL` (all in milliseconds).

## Listening for Events

Each sensor dispatches its own event with every reading:

| Sensor | Event | Payload |
|---|---|---|
| Accelerometer | `AccelerometerChanged` | `x`, `y`, `z` (m/s^2, includes gravity), `timestamp` |
| User accelerometer | `UserAccelerometerChanged` | `x`, `y`, `z` (m/s^2, gravity removed), `timestamp` |
| Gyroscope | `GyroscopeChanged` | `x`, `y`, `z` (rad/s), `timestamp` |
| Magnetometer | `MagnetometerChanged` | `x`, `y`, `z` (uT), `timestamp` |
| Barometer | `BarometerChanged` | `pressure` (hPa), `timestamp` |

```php
use Native\Mobile\Attributes\On;
use Khalidmaquilang\SensorsPlus\Events\AccelerometerChanged;

#[On(AccelerometerChanged::class)]
public function handleAccelerometerChanged($x, $y, $z, $timestamp)
{
    // Handle the reading
}
```

`timestamp` is a device-uptime clock (not wall-clock time) and is only meaningful for measuring intervals between readings on the same device.

## JavaScript Usage (Vue/React/Inertia)

```js
import { sensorsPlus, SensorInterval } from '@khalidmaquilang/sensors-plus';

await sensorsPlus.startAccelerometer(SensorInterval.NORMAL);
await sensorsPlus.stopAccelerometer();

const { available } = await sensorsPlus.isAvailable('barometer');
const status = await sensorsPlus.getStatus();
```

Readings still arrive via the PHP-side events above, not through this JS call -- use it only to start/stop the native listeners.

## Notes

- iOS requires the `NSMotionUsageDescription` Info.plist entry (declared for you in `nativephp.json`) -- without it, motion APIs will crash the app.
- Sampling rate is a best-effort request on both platforms; iOS in particular never allows apps to control the barometer's rate.
- Not every device has every sensor (e.g. barometer). Always check `isAvailable()` / `getStatus()` before relying on a reading.

## License

MIT
