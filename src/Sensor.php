<?php

namespace Khalidmaquilang\SensorsPlus;

enum Sensor: string
{
    case Accelerometer = 'accelerometer';
    case UserAccelerometer = 'userAccelerometer';
    case Gyroscope = 'gyroscope';
    case Magnetometer = 'magnetometer';
    case Barometer = 'barometer';
}
