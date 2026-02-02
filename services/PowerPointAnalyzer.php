<?php

declare(strict_types=1);

namespace App\Services;

use App\Dto\SlideAnalysisResult;
use App\Dto\SlideIssue;
use App\Dto\TextBlock;
use App\Utils\IntersectionCalculator;
use App\Utils\Rectangle;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Shape\RichText;

class PowerPointAnalyzer
{
    private const EMU_PER_PIXEL = 9525;

    public function __construct(
        private readonly IntersectionCalculator $intersectionCalculator
    ) {
    }

    /** @return array{results: SlideAnalysisResult[], slideWidth: float, slideHeight: float, videoZone: Rectangle} */
    public function analyze(string $filePath, array $zoneConfig): array
    {
        $presentation = IOFactory::load($filePath);
        $layout = $presentation->getLayout();
        $slideWidth = (float) $layout->getCX();
        $slideHeight = (float) $layout->getCY();
        $videoZone = $this->buildVideoZone($zoneConfig, $slideWidth, $slideHeight);

        $results = [];
        foreach ($presentation->getAllSlides() as $index => $slide) {
            $slideNumber = $index + 1;
            $result = new SlideAnalysisResult($slideNumber);
            $textBlocks = $this->extractTextBlocks($slide);

            foreach ($textBlocks as $textBlock) {
                $blockRect = new Rectangle($textBlock->x, $textBlock->y, $textBlock->width, $textBlock->height);
                $intersection = $this->intersectionCalculator->calculateIntersection($blockRect, $videoZone);

                if ($intersection->area > 0) {
                    $result->addIssue(new SlideIssue(
                        $slideNumber,
                        $textBlock,
                        $intersection->percentOfTarget
                    ));
                }
            }

            $results[] = $result;
        }

        return [
            'results' => $results,
            'slideWidth' => $slideWidth,
            'slideHeight' => $slideHeight,
            'videoZone' => $videoZone,
        ];
    }

    /** @return TextBlock[] */
    public function extractTextBlocks($slide): array
    {
        $blocks = [];
        foreach ($slide->getShapeCollection() as $shape) {
            if (!$shape instanceof RichText) {
                continue;
            }

            $text = trim($shape->getPlainText());
            if ($text === '') {
                continue;
            }

            $blocks[] = new TextBlock(
                (float) $shape->getOffsetX(),
                (float) $shape->getOffsetY(),
                (float) $shape->getWidth(),
                (float) $shape->getHeight(),
                $text
            );
        }

        return $blocks;
    }

    public function buildVideoZone(array $config, float $slideWidth, float $slideHeight): Rectangle
    {
        $unit = $config['unit'] ?? 'percent';
        $anchor = $config['anchor'] ?? 'bottom-right';
        $width = (float) ($config['width'] ?? 0);
        $height = (float) ($config['height'] ?? 0);
        $offsetX = (float) ($config['offset_x'] ?? 0);
        $offsetY = (float) ($config['offset_y'] ?? 0);

        if ($unit === 'percent') {
            $width = $slideWidth * ($width / 100);
            $height = $slideHeight * ($height / 100);
            $offsetX = $slideWidth * ($offsetX / 100);
            $offsetY = $slideHeight * ($offsetY / 100);
        }

        $x = $this->resolveAnchorX($anchor, $slideWidth, $width, $offsetX);
        $y = $this->resolveAnchorY($anchor, $slideHeight, $height, $offsetY);

        return new Rectangle($x, $y, $width, $height);
    }

    private function resolveAnchorX(string $anchor, float $slideWidth, float $zoneWidth, float $offsetX): float
    {
        return match ($anchor) {
            'top-left', 'bottom-left' => $offsetX,
            'top-right', 'bottom-right' => max(0.0, $slideWidth - $zoneWidth - $offsetX),
            default => max(0.0, $slideWidth - $zoneWidth - $offsetX),
        };
    }

    private function resolveAnchorY(string $anchor, float $slideHeight, float $zoneHeight, float $offsetY): float
    {
        return match ($anchor) {
            'top-left', 'top-right' => $offsetY,
            'bottom-left', 'bottom-right' => max(0.0, $slideHeight - $zoneHeight - $offsetY),
            default => max(0.0, $slideHeight - $zoneHeight - $offsetY),
        };
    }

    public function emuToPixels(float $emu): float
    {
        return $emu / self::EMU_PER_PIXEL;
    }
}
