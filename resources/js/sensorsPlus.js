/**
 * SensorsPlus Plugin for NativePHP Mobile
 *
 * Starts/stops native sensor listeners. Readings themselves are pushed back
 * to your PHP/Livewire components as events (see the plugin README), not
 * returned here -- these calls just control the native listeners.
 *
 * @example
 * import { sensorsPlus } from '@khalidmaquilang/sensors-plus';
 *
 * await sensorsPlus.startAccelerometer();
 * // ...
 * await sensorsPlus.stopAccelerometer();
 *
 * const { available } = await sensorsPlus.isAvailable('barometer');
 * const status = await sensorsPlus.getStatus();
 */

const baseUrl = '/_native/api/call';

export const SensorInterval = {
    NORMAL: 200,
    UI: 60,
    GAME: 20,
    FASTEST: 0,
};

/**
 * Internal bridge call function
 * @private
 */
async function bridgeCall(method, params = {}) {
    const response = await fetch(baseUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ method, params })
    });

    const result = await response.json();

    if (result.status === 'error') {
        throw new Error(result.message || 'Native call failed');
    }

    const nativeResponse = result.data;
    if (nativeResponse && nativeResponse.data !== undefined) {
        return nativeResponse.data;
    }

    return nativeResponse;
}

function startCall(method, intervalMs) {
    return bridgeCall(method, { interval: intervalMs });
}

/** Start the raw accelerometer (includes gravity). */
export function startAccelerometer(intervalMs = SensorInterval.NORMAL) {
    return startCall('SensorsPlus.StartAccelerometer', intervalMs);
}

export function stopAccelerometer() {
    return bridgeCall('SensorsPlus.StopAccelerometer');
}

/** Start acceleration with gravity removed. */
export function startUserAccelerometer(intervalMs = SensorInterval.NORMAL) {
    return startCall('SensorsPlus.StartUserAccelerometer', intervalMs);
}

export function stopUserAccelerometer() {
    return bridgeCall('SensorsPlus.StopUserAccelerometer');
}

/** Start the gyroscope. */
export function startGyroscope(intervalMs = SensorInterval.NORMAL) {
    return startCall('SensorsPlus.StartGyroscope', intervalMs);
}

export function stopGyroscope() {
    return bridgeCall('SensorsPlus.StopGyroscope');
}

/** Start the magnetometer. */
export function startMagnetometer(intervalMs = SensorInterval.NORMAL) {
    return startCall('SensorsPlus.StartMagnetometer', intervalMs);
}

export function stopMagnetometer() {
    return bridgeCall('SensorsPlus.StopMagnetometer');
}

/**
 * Start the barometer. Note: iOS does not allow apps to control the
 * barometer's sampling rate, so intervalMs is ignored there.
 */
export function startBarometer(intervalMs = SensorInterval.NORMAL) {
    return startCall('SensorsPlus.StartBarometer', intervalMs);
}

export function stopBarometer() {
    return bridgeCall('SensorsPlus.StopBarometer');
}

/**
 * Check whether a given sensor exists on this device.
 * @param {'accelerometer'|'userAccelerometer'|'gyroscope'|'magnetometer'|'barometer'} sensor
 */
export function isAvailable(sensor) {
    return bridgeCall('SensorsPlus.IsAvailable', { sensor });
}

/** Get availability of all sensors on this device at once. */
export function getStatus() {
    return bridgeCall('SensorsPlus.GetStatus');
}

/**
 * SensorsPlus namespace object
 */
export const sensorsPlus = {
    SensorInterval,
    startAccelerometer,
    stopAccelerometer,
    startUserAccelerometer,
    stopUserAccelerometer,
    startGyroscope,
    stopGyroscope,
    startMagnetometer,
    stopMagnetometer,
    startBarometer,
    stopBarometer,
    isAvailable,
    getStatus,
};

export default sensorsPlus;
