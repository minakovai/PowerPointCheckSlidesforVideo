<?php

declare(strict_types=1);

namespace App\Dto;

class SlideIssue
{
    public function __construct(
        public readonly int $slideNumber,
        public readonly TextBlock $textBlock,
        public readonly float $intersectionPercent
    ) {
    }
}
