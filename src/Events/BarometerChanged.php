<?php

namespace Khalidmaquilang\SensorsPlus\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched for every barometer reading, in hPa.
 */
class BarometerChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public float $pressure,
        public float $timestamp,
    ) {}
}
