<?php

namespace Khalidmaquilang\SensorsPlus\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched for every gyroscope reading (rate of rotation), in rad/s.
 */
class GyroscopeChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public float $x,
        public float $y,
        public float $z,
        public float $timestamp,
    ) {}
}
