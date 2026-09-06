<?php

namespace Khalidmaquilang\SensorsPlus\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched for every accelerometer reading with gravity removed, in m/s^2.
 */
class UserAccelerometerChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public float $x,
        public float $y,
        public float $z,
        public float $timestamp,
    ) {}
}
