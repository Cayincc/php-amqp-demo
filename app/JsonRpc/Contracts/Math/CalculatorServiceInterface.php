<?php

namespace App\JsonRpc\Contracts\Math;

interface CalculatorServiceInterface {
    public function add(float $a, float $b): float;
}