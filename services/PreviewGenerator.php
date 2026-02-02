<?php

declare(strict_types=1);

namespace App\Services;

use App\Dto\SlideAnalysisResult;
use App\Utils\Rectangle;
use RuntimeException;

class PreviewGenerator
{
    public function __construct(
        private readonly string $previewDir,
        private readonly int $maxWidth,
        private readonly int $maxHeight
    ) {
    }

    /** @param SlideAnalysisResult[] $results */
    public function generate(array $results, Rectangle $videoZone, float $slideWidth, float $slideHeight): array
    {
        if (!extension_loaded('gd')) {
            return [];
        }

        if (!is_dir($this->previewDir) && !mkdir($this->previewDir, 0755, true) && !is_dir($this->previewDir)) {
            throw new RuntimeException('Не удалось создать каталог превью.');
        }

        [$width, $height, $scale] = $this->calculateCanvas($slideWidth, $slideHeight);
        $previewMap = [];

        foreach ($results as $result) {
            $image = imagecreatetruecolor($width, $height);
            $white = imagecolorallocate($image, 255, 255, 255);
            $red = imagecolorallocate($image, 220, 20, 60);
            $yellow = imagecolorallocate($image, 255, 215, 0);
            $gray = imagecolorallocate($image, 200, 200, 200);

            imagefilledrectangle($image, 0, 0, $width, $height, $white);
            imagerectangle($image, 0, 0, $width - 1, $height - 1, $gray);

            $this->drawRectangle($image, $videoZone, $scale, $red, 3);

            foreach ($result->issues as $issue) {
                $blockRect = new Rectangle(
                    $issue->textBlock->x,
                    $issue->textBlock->y,
                    $issue->textBlock->width,
                    $issue->textBlock->height
                );
                $this->drawRectangle($image, $blockRect, $scale, $yellow, 2);
            }

            $filename = sprintf('slide-%d-%s.png', $result->slideNumber, bin2hex(random_bytes(4)));
            $path = rtrim($this->previewDir, '/') . '/' . $filename;
            imagepng($image, $path);
            imagedestroy($image);

            $previewMap[$result->slideNumber] = 'storage/previews/' . $filename;
        }

        return $previewMap;
    }

    private function calculateCanvas(float $slideWidth, float $slideHeight): array
    {
        $scale = min(
            $this->maxWidth / $slideWidth,
            $this->maxHeight / $slideHeight,
            1
        );

        return [
            (int) round($slideWidth * $scale),
            (int) round($slideHeight * $scale),
            $scale,
        ];
    }

    private function drawRectangle($image, Rectangle $rectangle, float $scale, int $color, int $thickness): void
    {
        imagesetthickness($image, $thickness);
        $x1 = (int) round($rectangle->x * $scale);
        $y1 = (int) round($rectangle->y * $scale);
        $x2 = (int) round(($rectangle->x + $rectangle->width) * $scale);
        $y2 = (int) round(($rectangle->y + $rectangle->height) * $scale);
        imagerectangle($image, $x1, $y1, $x2, $y2, $color);
    }
}
