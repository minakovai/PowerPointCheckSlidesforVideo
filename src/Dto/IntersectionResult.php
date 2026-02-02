<?php

declare(strict_types=1);

namespace App\Dto;

class IntersectionResult
{
    public function __construct(
        public readonly float $area,
        public readonly float $percentOfTarget
    ) {
    }
}
