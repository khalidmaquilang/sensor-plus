package com.khalidmaquilang.plugins.sensors_plus

import android.content.Context
import android.hardware.Sensor
import android.hardware.SensorEvent
import android.hardware.SensorEventListener
import android.hardware.SensorManager
import android.os.Handler
import android.os.Looper
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeError
import com.nativephp.mobile.bridge.BridgeFunction
import com.nativephp.mobile.bridge.BridgeResponse
import com.nativephp.mobile.utils.NativeActionCoordinator
import org.json.JSONObject

/**
 * Sensor types this plugin knows how to start/stop, keyed by the string
 * used on the PHP side (SensorsPlus::isAvailable('accelerometer'), etc.)
 * and mapped to the equivalent Android Sensor.TYPE_* constant.
 */
private val SENSOR_TYPES = mapOf(
    "accelerometer" to Sensor.TYPE_ACCELEROMETER,
    // sensors_plus' "user accelerometer" (gravity removed) maps to Android's
    // linear acceleration sensor.
    "userAccelerometer" to Sensor.TYPE_LINEAR_ACCELERATION,
    "gyroscope" to Sensor.TYPE_GYROSCOPE,
    "magnetometer" to Sensor.TYPE_MAGNETIC_FIELD,
    "barometer" to Sensor.TYPE_PRESSURE
)

/**
 * Holds the SensorManager and any active listeners so that the Start/Stop
 * bridge functions (each instantiated fresh per call by the NativePHP
 * bridge) can coordinate shared, long-lived state.
 */
private object SensorsPlusManager {

    private val listeners = mutableMapOf<Int, SensorEventListener>()

    fun sensorManager(context: Context): SensorManager =
        context.getSystemService(Context.SENSOR_SERVICE) as SensorManager

    fun isAvailable(context: Context, type: Int): Boolean =
        sensorManager(context).getDefaultSensor(type) != null

    /**
     * Register a listener for [type] and dispatch every reading back to PHP
     * as [eventClass], built from the raw SensorEvent by [payload].
     * Returns false if the device has no such sensor.
     */
    fun start(
        activity: FragmentActivity,
        type: Int,
        eventClass: String,
        intervalMs: Int,
        payload: (SensorEvent) -> JSONObject
    ): Boolean {
        val manager = sensorManager(activity)
        val sensor = manager.getDefaultSensor(type) ?: return false

        // Replace any existing listener for this sensor type.
        stop(activity, type)

        val listener = object : SensorEventListener {
            override fun onSensorChanged(event: SensorEvent) {
                val data = payload(event)
                Handler(Looper.getMainLooper()).post {
                    NativeActionCoordinator.dispatchEvent(activity, eventClass, data.toString())
                }
            }

            override fun onAccuracyChanged(sensor: Sensor?, accuracy: Int) {}
        }

        listeners[type] = listener

        val samplingPeriodUs = intervalMs.coerceAtLeast(0) * 1000

        return manager.registerListener(listener, sensor, samplingPeriodUs)
    }

    fun stop(context: Context, type: Int): Boolean {
        val listener = listeners.remove(type) ?: return true
        sensorManager(context).unregisterListener(listener)
        return true
    }
}

object SensorsPlusFunctions {

    private const val EVENT_NAMESPACE = "Khalidmaquilang\\SensorsPlus\\Events\\"

    private fun xyzPayload(event: SensorEvent): JSONObject = JSONObject().apply {
        put("x", event.values.getOrElse(0) { 0f })
        put("y", event.values.getOrElse(1) { 0f })
        put("z", event.values.getOrElse(2) { 0f })
        put("timestamp", event.timestamp)
    }

    private fun pressurePayload(event: SensorEvent): JSONObject = JSONObject().apply {
        // Sensor.TYPE_PRESSURE already reports hPa on Android.
        put("pressure", event.values.getOrElse(0) { 0f })
        put("timestamp", event.timestamp)
    }

    /**
     * Shared Start* body. Throws BridgeError.ExecutionFailed (rather than
     * returning BridgeResponse.error) so the bridge router builds the error
     * response for us -- BridgeResponse.error() has no single-message
     * overload, only error(code, message) / error(BridgeError).
     */
    private fun startSensor(
        activity: FragmentActivity,
        parameters: Map<String, Any>,
        type: Int,
        eventName: String,
        payload: (SensorEvent) -> JSONObject,
        notAvailableMessage: String
    ): Map<String, Any> {
        val interval = (parameters["interval"] as? Number)?.toInt() ?: 200
        val started = SensorsPlusManager.start(activity, type, EVENT_NAMESPACE + eventName, interval, payload)

        if (!started) {
            throw BridgeError.ExecutionFailed(notAvailableMessage)
        }

        return BridgeResponse.success(mapOf("started" to true))
    }

    private fun stopSensor(activity: FragmentActivity, type: Int): Map<String, Any> {
        SensorsPlusManager.stop(activity, type)
        return BridgeResponse.success(mapOf("stopped" to true))
    }

    class StartAccelerometer(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> =
            startSensor(
                activity, parameters, Sensor.TYPE_ACCELEROMETER, "AccelerometerChanged",
                ::xyzPayload, "Accelerometer not available on this device"
            )
    }

    class StopAccelerometer(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> =
            stopSensor(activity, Sensor.TYPE_ACCELEROMETER)
    }

    class StartUserAccelerometer(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> =
            startSensor(
                activity, parameters, Sensor.TYPE_LINEAR_ACCELERATION, "UserAccelerometerChanged",
                ::xyzPayload, "Linear acceleration sensor not available on this device"
            )
    }

    class StopUserAccelerometer(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> =
            stopSensor(activity, Sensor.TYPE_LINEAR_ACCELERATION)
    }

    class StartGyroscope(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> =
            startSensor(
                activity, parameters, Sensor.TYPE_GYROSCOPE, "GyroscopeChanged",
                ::xyzPayload, "Gyroscope not available on this device"
            )
    }

    class StopGyroscope(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> =
            stopSensor(activity, Sensor.TYPE_GYROSCOPE)
    }

    class StartMagnetometer(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> =
            startSensor(
                activity, parameters, Sensor.TYPE_MAGNETIC_FIELD, "MagnetometerChanged",
                ::xyzPayload, "Magnetometer not available on this device"
            )
    }

    class StopMagnetometer(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> =
            stopSensor(activity, Sensor.TYPE_MAGNETIC_FIELD)
    }

    class StartBarometer(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> =
            startSensor(
                activity, parameters, Sensor.TYPE_PRESSURE, "BarometerChanged",
                ::pressurePayload, "Barometer not available on this device"
            )
    }

    class StopBarometer(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> =
            stopSensor(activity, Sensor.TYPE_PRESSURE)
    }

    class IsAvailable(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val sensor = parameters["sensor"] as? String ?: ""
            val type = SENSOR_TYPES[sensor]
                ?: throw BridgeError.InvalidParameters("Unknown sensor: $sensor")

            return BridgeResponse.success(mapOf("available" to SensorsPlusManager.isAvailable(activity, type)))
        }
    }

    class GetStatus(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val status = SENSOR_TYPES.mapValues { (_, type) -> SensorsPlusManager.isAvailable(activity, type) }
            return BridgeResponse.success(status)
        }
    }
}
