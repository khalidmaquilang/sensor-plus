import Foundation
import CoreMotion

/// Sensor types this plugin knows how to start/stop, keyed by the string
/// used on the PHP side (SensorsPlus::isAvailable('accelerometer'), etc.)
private let knownSensors = [
    "accelerometer",
    "userAccelerometer",
    "gyroscope",
    "magnetometer",
    "barometer",
]

/// Holds Core Motion state so that the Start/Stop bridge function classes
/// (each instantiated fresh per call by the NativePHP bridge) can
/// coordinate shared, long-lived sensor sessions.
final class SensorsPlusManager {
    static let shared = SensorsPlusManager()

    private let motionManager = CMMotionManager()
    private let altimeter = CMAltimeter()
    private let queue: OperationQueue = {
        let queue = OperationQueue()
        queue.qualityOfService = .userInitiated
        return queue
    }()

    private init() {}

    // MARK: - Availability

    var isAccelerometerAvailable: Bool { motionManager.isAccelerometerAvailable }
    // sensors_plus' "user accelerometer" (gravity removed) is sourced from
    // device motion's userAcceleration field.
    var isUserAccelerometerAvailable: Bool { motionManager.isDeviceMotionAvailable }
    var isGyroscopeAvailable: Bool { motionManager.isGyroAvailable }
    var isMagnetometerAvailable: Bool { motionManager.isMagnetometerAvailable }
    var isBarometerAvailable: Bool { CMAltimeter.isRelativeAltitudeAvailable() }

    func isAvailable(_ sensor: String) -> Bool? {
        switch sensor {
        case "accelerometer": return isAccelerometerAvailable
        case "userAccelerometer": return isUserAccelerometerAvailable
        case "gyroscope": return isGyroscopeAvailable
        case "magnetometer": return isMagnetometerAvailable
        case "barometer": return isBarometerAvailable
        default: return nil
        }
    }

    private func send(_ eventName: String, _ payload: [String: Any]) {
        DispatchQueue.main.async {
            LaravelBridge.shared.send?("Khalidmaquilang\\SensorsPlus\\Events\\" + eventName, payload)
        }
    }

    // MARK: - Accelerometer (raw, includes gravity)

    func startAccelerometer(intervalMs: Int) -> Bool {
        guard motionManager.isAccelerometerAvailable else { return false }

        motionManager.accelerometerUpdateInterval = Self.seconds(from: intervalMs)
        motionManager.startAccelerometerUpdates(to: queue) { [weak self] data, _ in
            guard let data = data else { return }
            self?.send("AccelerometerChanged", [
                "x": data.acceleration.x,
                "y": data.acceleration.y,
                "z": data.acceleration.z,
                "timestamp": data.timestamp,
            ])
        }
        return true
    }

    func stopAccelerometer() {
        motionManager.stopAccelerometerUpdates()
    }

    // MARK: - User accelerometer (gravity removed, via device motion)

    func startUserAccelerometer(intervalMs: Int) -> Bool {
        guard motionManager.isDeviceMotionAvailable else { return false }

        motionManager.deviceMotionUpdateInterval = Self.seconds(from: intervalMs)
        motionManager.startDeviceMotionUpdates(to: queue) { [weak self] data, _ in
            guard let data = data else { return }
            let acceleration = data.userAcceleration
            self?.send("UserAccelerometerChanged", [
                "x": acceleration.x,
                "y": acceleration.y,
                "z": acceleration.z,
                "timestamp": data.timestamp,
            ])
        }
        return true
    }

    func stopUserAccelerometer() {
        motionManager.stopDeviceMotionUpdates()
    }

    // MARK: - Gyroscope

    func startGyroscope(intervalMs: Int) -> Bool {
        guard motionManager.isGyroAvailable else { return false }

        motionManager.gyroUpdateInterval = Self.seconds(from: intervalMs)
        motionManager.startGyroUpdates(to: queue) { [weak self] data, _ in
            guard let data = data else { return }
            self?.send("GyroscopeChanged", [
                "x": data.rotationRate.x,
                "y": data.rotationRate.y,
                "z": data.rotationRate.z,
                "timestamp": data.timestamp,
            ])
        }
        return true
    }

    func stopGyroscope() {
        motionManager.stopGyroUpdates()
    }

    // MARK: - Magnetometer

    func startMagnetometer(intervalMs: Int) -> Bool {
        guard motionManager.isMagnetometerAvailable else { return false }

        motionManager.magnetometerUpdateInterval = Self.seconds(from: intervalMs)
        motionManager.startMagnetometerUpdates(to: queue) { [weak self] data, _ in
            guard let data = data else { return }
            self?.send("MagnetometerChanged", [
                "x": data.magneticField.x,
                "y": data.magneticField.y,
                "z": data.magneticField.z,
                "timestamp": data.timestamp,
            ])
        }
        return true
    }

    func stopMagnetometer() {
        motionManager.stopMagnetometerUpdates()
    }

    // MARK: - Barometer
    // Note: iOS does not let apps control the barometer's sampling rate,
    // so intervalMs is accepted for API symmetry but otherwise unused here.

    func startBarometer(intervalMs: Int) -> Bool {
        guard CMAltimeter.isRelativeAltitudeAvailable() else { return false }

        altimeter.startRelativeAltitudeUpdates(to: queue) { [weak self] data, _ in
            guard let data = data else { return }
            self?.send("BarometerChanged", [
                // CMAltitudeData.pressure is in kPa; convert to hPa to match
                // Android's Sensor.TYPE_PRESSURE units.
                "pressure": data.pressure.doubleValue * 10.0,
                "timestamp": Date().timeIntervalSince1970,
            ])
        }
        return true
    }

    func stopBarometer() {
        altimeter.stopRelativeAltitudeUpdates()
    }

    private static func seconds(from intervalMs: Int) -> TimeInterval {
        TimeInterval(max(intervalMs, 1)) / 1000.0
    }
}

enum SensorsPlusFunctions {

    class StartAccelerometer: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let interval = parameters["interval"] as? Int ?? 200
            guard SensorsPlusManager.shared.startAccelerometer(intervalMs: interval) else {
                throw BridgeError.executionFailed("Accelerometer not available on this device")
            }
            return BridgeResponse.success(data: ["started": true])
        }
    }

    class StopAccelerometer: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            SensorsPlusManager.shared.stopAccelerometer()
            return BridgeResponse.success(data: ["stopped": true])
        }
    }

    class StartUserAccelerometer: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let interval = parameters["interval"] as? Int ?? 200
            guard SensorsPlusManager.shared.startUserAccelerometer(intervalMs: interval) else {
                throw BridgeError.executionFailed("Device motion not available on this device")
            }
            return BridgeResponse.success(data: ["started": true])
        }
    }

    class StopUserAccelerometer: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            SensorsPlusManager.shared.stopUserAccelerometer()
            return BridgeResponse.success(data: ["stopped": true])
        }
    }

    class StartGyroscope: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let interval = parameters["interval"] as? Int ?? 200
            guard SensorsPlusManager.shared.startGyroscope(intervalMs: interval) else {
                throw BridgeError.executionFailed("Gyroscope not available on this device")
            }
            return BridgeResponse.success(data: ["started": true])
        }
    }

    class StopGyroscope: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            SensorsPlusManager.shared.stopGyroscope()
            return BridgeResponse.success(data: ["stopped": true])
        }
    }

    class StartMagnetometer: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let interval = parameters["interval"] as? Int ?? 200
            guard SensorsPlusManager.shared.startMagnetometer(intervalMs: interval) else {
                throw BridgeError.executionFailed("Magnetometer not available on this device")
            }
            return BridgeResponse.success(data: ["started": true])
        }
    }

    class StopMagnetometer: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            SensorsPlusManager.shared.stopMagnetometer()
            return BridgeResponse.success(data: ["stopped": true])
        }
    }

    class StartBarometer: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let interval = parameters["interval"] as? Int ?? 200
            guard SensorsPlusManager.shared.startBarometer(intervalMs: interval) else {
                throw BridgeError.executionFailed("Barometer not available on this device")
            }
            return BridgeResponse.success(data: ["started": true])
        }
    }

    class StopBarometer: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            SensorsPlusManager.shared.stopBarometer()
            return BridgeResponse.success(data: ["stopped": true])
        }
    }

    class IsAvailable: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let sensor = parameters["sensor"] as? String ?? ""
            guard let available = SensorsPlusManager.shared.isAvailable(sensor) else {
                throw BridgeError.invalidParameters("Unknown sensor: \(sensor)")
            }
            return BridgeResponse.success(data: ["available": available])
        }
    }

    class GetStatus: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            var status: [String: Any] = [:]
            for sensor in knownSensors {
                status[sensor] = SensorsPlusManager.shared.isAvailable(sensor) ?? false
            }
            return BridgeResponse.success(data: status)
        }
    }
}
