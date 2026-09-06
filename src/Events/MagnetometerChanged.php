<?php

namespace Khalidmaquilang\SensorsPlus\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched for every magnetometer reading (ambient magnetic field), in uT.
 */
class MagnetometerChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public float $x,
        public float $y,
        public float $z,
        public float $timestamp,
    ) {}
}
