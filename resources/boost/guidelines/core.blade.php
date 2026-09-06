## khalidmaquilang/sensors-plus

A NativePHP Mobile plugin that exposes the accelerometer, user-accelerometer (gravity removed), gyroscope, magnetometer and barometer sensors -- the PHP equivalent of Flutter's sensors_plus.

### Installation

```bash
composer require khalidmaquilang/sensors-plus
```

### PHP Usage (Livewire/Blade)

Sensor data is asynchronous: `start*()` just confirms the native listener came up, readings arrive as events (see below).

@verbatim
<code-snippet name="Starting and stopping sensors" lang="php">
use Khalidmaquilang\SensorsPlus\Facades\SensorsPlus;
use Khalidmaquilang\SensorsPlus\Sensor;

SensorsPlus::startAccelerometer();      // ~200ms default interval
SensorsPlus::startGyroscope(SensorsPlus::INTERVAL_GAME);
SensorsPlus::startMagnetometer();
SensorsPlus::startUserAccelerometer();  // gravity removed
SensorsPlus::startBarometer();          // iOS ignores the interval here

SensorsPlus::stopAccelerometer();

$available = SensorsPlus::isAvailable(Sensor::Gyroscope); // bool
$status = SensorsPlus::getStatus();                       // object, one bool per sensor
</code-snippet>
@endverbatim

### Available Methods

- `SensorsPlus::startAccelerometer(int $intervalMs = 200)` / `stopAccelerometer()`
- `SensorsPlus::startUserAccelerometer(int $intervalMs = 200)` / `stopUserAccelerometer()`
- `SensorsPlus::startGyroscope(int $intervalMs = 200)` / `stopGyroscope()`
- `SensorsPlus::startMagnetometer(int $intervalMs = 200)` / `stopMagnetometer()`
- `SensorsPlus::startBarometer(int $intervalMs = 200)` / `stopBarometer()`
- `SensorsPlus::isAvailable(Sensor $sensor)`: `Sensor::Accelerometer`, `UserAccelerometer`, `Gyroscope`, `Magnetometer`, or `Barometer`
- `SensorsPlus::getStatus()`: availability of every sensor at once

### Events

Every reading is pushed as its own event: `AccelerometerChanged`, `UserAccelerometerChanged`, `GyroscopeChanged`, `MagnetometerChanged` (all with `x`, `y`, `z`, `timestamp`), and `BarometerChanged` (with `pressure`, `timestamp`).

@verbatim
<code-snippet name="Listening for SensorsPlus Events" lang="php">
use Native\Mobile\Attributes\On;
use Khalidmaquilang\SensorsPlus\Events\AccelerometerChanged;

#[On(AccelerometerChanged::class)]
public function handleAccelerometerChanged($x, $y, $z, $timestamp)
{
    // Handle the reading
}
</code-snippet>
@endverbatim

### JavaScript Usage (Vue/React/Inertia)

@verbatim
<code-snippet name="Using SensorsPlus in JavaScript" lang="javascript">
import { sensorsPlus, SensorInterval } from '@khalidmaquilang/sensors-plus';

await sensorsPlus.startAccelerometer(SensorInterval.NORMAL);
await sensorsPlus.stopAccelerometer();

const { available } = await sensorsPlus.isAvailable('barometer');
const status = await sensorsPlus.getStatus();
</code-snippet>
@endverbatim

Readings still arrive via the PHP-side events above, not through this JS call.
