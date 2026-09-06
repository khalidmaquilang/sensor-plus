<?php

namespace Khalidmaquilang\SensorsPlus;

class SensorsPlus
{
    /** Roughly 200ms between updates (Android SENSOR_DELAY_NORMAL). */
    public const INTERVAL_NORMAL = 200;

    /** Roughly 60ms between updates, suitable for UI-driven use cases. */
    public const INTERVAL_UI = 60;

    /** Roughly 20ms between updates (Android SENSOR_DELAY_GAME). */
    public const INTERVAL_GAME = 20;

    /** As fast as the platform will deliver updates. */
    public const INTERVAL_FASTEST = 0;

    /**
     * Start listening to the raw accelerometer (includes gravity), in m/s^2.
     * Readings are dispatched as \Khalidmaquilang\SensorsPlus\Events\AccelerometerChanged.
     */
    public function startAccelerometer(int $intervalMs = self::INTERVAL_NORMAL): bool
    {
        return $this->start('SensorsPlus.StartAccelerometer', $intervalMs);
    }

    public function stopAccelerometer(): bool
    {
        return $this->stop('SensorsPlus.StopAccelerometer');
    }

    /**
     * Start listening to acceleration with gravity removed, in m/s^2.
     * Readings are dispatched as \Khalidmaquilang\SensorsPlus\Events\UserAccelerometerChanged.
     */
    public function startUserAccelerometer(int $intervalMs = self::INTERVAL_NORMAL): bool
    {
        return $this->start('SensorsPlus.StartUserAccelerometer', $intervalMs);
    }

    public function stopUserAccelerometer(): bool
    {
        return $this->stop('SensorsPlus.StopUserAccelerometer');
    }

    /**
     * Start listening to the gyroscope (rate of rotation, rad/s).
     * Readings are dispatched as \Khalidmaquilang\SensorsPlus\Events\GyroscopeChanged.
     */
    public function startGyroscope(int $intervalMs = self::INTERVAL_NORMAL): bool
    {
        return $this->start('SensorsPlus.StartGyroscope', $intervalMs);
    }

    public function stopGyroscope(): bool
    {
        return $this->stop('SensorsPlus.StopGyroscope');
    }

    /**
     * Start listening to the magnetometer (ambient magnetic field, uT).
     * Readings are dispatched as \Khalidmaquilang\SensorsPlus\Events\MagnetometerChanged.
     */
    public function startMagnetometer(int $intervalMs = self::INTERVAL_NORMAL): bool
    {
        return $this->start('SensorsPlus.StartMagnetometer', $intervalMs);
    }

    public function stopMagnetometer(): bool
    {
        return $this->stop('SensorsPlus.StopMagnetometer');
    }

    /**
     * Start listening to the barometer (atmospheric pressure, hPa).
     * Note: iOS does not allow apps to control the barometer's sampling rate,
     * so $intervalMs is ignored on iOS.
     * Readings are dispatched as \Khalidmaquilang\SensorsPlus\Events\BarometerChanged.
     */
    public function startBarometer(int $intervalMs = self::INTERVAL_NORMAL): bool
    {
        return $this->start('SensorsPlus.StartBarometer', $intervalMs);
    }

    public function stopBarometer(): bool
    {
        return $this->stop('SensorsPlus.StopBarometer');
    }

    /**
     * Check whether the device has a given sensor.
     */
    public function isAvailable(Sensor $sensor): bool
    {
        $result = $this->call('SensorsPlus.IsAvailable', ['sensor' => $sensor->value]);

        return (bool) ($result->available ?? false);
    }

    /**
     * Get availability of all sensors on this device at once.
     */
    public function getStatus(): ?object
    {
        return $this->call('SensorsPlus.GetStatus');
    }

    private function start(string $bridgeMethod, int $intervalMs): bool
    {
        $result = $this->call($bridgeMethod, ['interval' => $intervalMs]);

        return (bool) ($result->started ?? false);
    }

    private function stop(string $bridgeMethod): bool
    {
        $result = $this->call($bridgeMethod);

        return (bool) ($result->stopped ?? false);
    }

    /**
     * Call a bridge function and return its result data.
     *
     * The native bridge (BridgeRouter on Android/iOS) returns a SUCCESS
     * response as the function's data flat, with no wrapper -- there is no
     * "data" key to unwrap. Only an ERROR response is wrapped, shaped like
     * {"status": "error", "code": ..., "message": ..., "data": {...}}.
     * Errors are treated as a plain null/false result here; the message is
     * discarded to keep this facade's API simple.
     */
    private function call(string $method, array $params = []): ?object
    {
        if (! function_exists('nativephp_call')) {
            return null;
        }

        $result = nativephp_call($method, json_encode($params));

        if (! $result) {
            return null;
        }

        $decoded = json_decode($result);

        if (! is_object($decoded)) {
            return null;
        }

        if (($decoded->status ?? null) === 'error') {
            return null;
        }

        return $decoded;
    }
}
