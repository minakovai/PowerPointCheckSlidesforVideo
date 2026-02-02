<?php

declare(strict_types=1);

namespace App\Dto;

class TextBlock
{
    public function __construct(
        public readonly float $x,
        public readonly float $y,
        public readonly float $width,
        public readonly float $height,
        public readonly string $text
    ) {
    }
}
