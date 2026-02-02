<?php

declare(strict_types=1);

namespace App\Dto;

class SlideAnalysisResult
{
    /** @var SlideIssue[] */
    public array $issues = [];

    public function __construct(
        public readonly int $slideNumber
    ) {
    }

    public function addIssue(SlideIssue $issue): void
    {
        $this->issues[] = $issue;
    }

    public function hasIssues(): bool
    {
        return $this->issues !== [];
    }
}
