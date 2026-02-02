<?php

declare(strict_types=1);

namespace App\Utils;

use App\Dto\IntersectionResult;

class IntersectionCalculator
{
    public function calculateIntersection(Rectangle $target, Rectangle $zone): IntersectionResult
    {
        $xLeft = max($target->x, $zone->x);
        $yTop = max($target->y, $zone->y);
        $xRight = min($target->right(), $zone->right());
        $yBottom = min($target->bottom(), $zone->bottom());

        if ($xRight <= $xLeft || $yBottom <= $yTop) {
            return new IntersectionResult(0.0, 0.0);
        }

        $intersectionArea = ($xRight - $xLeft) * ($yBottom - $yTop);
        $percent = $target->area() > 0 ? ($intersectionArea / $target->area()) * 100 : 0.0;

        return new IntersectionResult($intersectionArea, $percent);
    }
}
