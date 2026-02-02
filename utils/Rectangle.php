<?php

declare(strict_types=1);

namespace App\Utils;

class Rectangle
{
    public function __construct(
        public readonly float $x,
        public readonly float $y,
        public readonly float $width,
        public readonly float $height
    ) {
    }

    public function right(): float
    {
        return $this->x + $this->width;
    }

    public function bottom(): float
    {
        return $this->y + $this->height;
    }

    public function area(): float
    {
        return max(0.0, $this->width) * max(0.0, $this->height);
    }
}
