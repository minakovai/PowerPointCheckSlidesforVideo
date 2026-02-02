<?php

declare(strict_types=1);

namespace App\Utils;

use App\Dto\SlideAnalysisResult;

class ResponseFormatter
{
    /** @param SlideAnalysisResult[] $results */
    public function toArray(array $results, Rectangle $videoZone, array $previewMap): array
    {
        return [
            'video_zone' => [
                'x' => $videoZone->x,
                'y' => $videoZone->y,
                'width' => $videoZone->width,
                'height' => $videoZone->height,
            ],
            'slides' => array_values(array_map(function (SlideAnalysisResult $result) use ($previewMap): array {
                return [
                    'slide' => $result->slideNumber,
                    'preview' => $previewMap[$result->slideNumber] ?? null,
                    'issues' => array_map(static function ($issue): array {
                        return [
                            'text' => $issue->textBlock->text,
                            'x' => $issue->textBlock->x,
                            'y' => $issue->textBlock->y,
                            'width' => $issue->textBlock->width,
                            'height' => $issue->textBlock->height,
                            'intersection_percent' => round($issue->intersectionPercent, 2),
                        ];
                    }, $result->issues),
                ];
            }, $results)),
        ];
    }
}
