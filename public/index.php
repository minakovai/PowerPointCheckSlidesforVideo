<?php

declare(strict_types=1);

use App\Services\PowerPointAnalyzer;
use App\Services\PreviewGenerator;
use App\Services\UploadService;
use App\Utils\IntersectionCalculator;
use App\Utils\ResponseFormatter;

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/app.php';

$uploadService = new UploadService(
    $config['paths']['uploads'],
    $config['upload_max_bytes'],
    $config['allowed_mime_types']
);
$analyzer = new PowerPointAnalyzer(new IntersectionCalculator());
$responseFormatter = new ResponseFormatter();
$previewGenerator = new PreviewGenerator(
    $config['paths']['previews'],
    $config['preview']['max_width'],
    $config['preview']['max_height']
);

$results = [];
$previewMap = [];
$error = null;
$analysisMeta = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $filePath = $uploadService->handleUpload($_FILES['presentation'] ?? []);
        $analysis = $analyzer->analyze($filePath, $config['video_zone']);
        $results = $analysis['results'];
        $analysisMeta = [
            'slideWidth' => $analysis['slideWidth'],
            'slideHeight' => $analysis['slideHeight'],
            'videoZone' => $analysis['videoZone'],
        ];

        if ($config['preview']['enabled']) {
            $previewMap = $previewGenerator->generate(
                $results,
                $analysis['videoZone'],
                $analysis['slideWidth'],
                $analysis['slideHeight']
            );
        }

        unlink($filePath);
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$wantsJson = isset($_GET['format']) && $_GET['format'] === 'json'
    || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

if ($wantsJson && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    if ($error) {
        echo json_encode(['error' => $error], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    echo json_encode(
        $responseFormatter->toArray($results, $analysisMeta['videoZone'], $previewMap),
        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
    );
    exit;
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Проверка слайдов PowerPoint на перекрытие видео</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 32px; background: #f7f7f7; }
        .container { max-width: 1100px; margin: 0 auto; background: #fff; padding: 24px; border-radius: 8px; }
        .error { color: #b00020; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ddd; padding: 8px; vertical-align: top; }
        th { background: #f0f0f0; }
        .preview { max-width: 360px; border: 1px solid #ddd; margin-top: 8px; }
        .note { color: #666; font-size: 0.9rem; }
    </style>
</head>
<body>
<div class="container">
    <h1>Анализ презентации PowerPoint</h1>
    <p>Загрузите .pptx, чтобы проверить, перекрывает ли видео спикера текстовые блоки.</p>

    <?php if ($error): ?>
        <div class="error">Ошибка: <?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="file" name="presentation" accept=".pptx" required>
        <button type="submit">Проанализировать</button>
    </form>

    <?php if ($results !== []): ?>
        <h2>Результаты анализа</h2>
        <p class="note">
            Зона видео: x=<?= h((string) round($analysisMeta['videoZone']->x)) ?>,
            y=<?= h((string) round($analysisMeta['videoZone']->y)) ?>,
            ширина=<?= h((string) round($analysisMeta['videoZone']->width)) ?>,
            высота=<?= h((string) round($analysisMeta['videoZone']->height)) ?> (EMU).
        </p>
        <table>
            <thead>
            <tr>
                <th>Слайд</th>
                <th>Перекрытые текстовые блоки</th>
                <th>Превью</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($results as $result): ?>
                <?php if (!$result->hasIssues()) { continue; } ?>
                <tr>
                    <td><?= h((string) $result->slideNumber) ?></td>
                    <td>
                        <ul>
                            <?php foreach ($result->issues as $issue): ?>
                                <li>
                                    <strong><?= h($issue->textBlock->text) ?></strong><br>
                                    Пересечение: <?= h((string) round($issue->intersectionPercent, 2)) ?>%<br>
                                    Координаты: x=<?= h((string) round($issue->textBlock->x)) ?>,
                                    y=<?= h((string) round($issue->textBlock->y)) ?>,
                                    w=<?= h((string) round($issue->textBlock->width)) ?>,
                                    h=<?= h((string) round($issue->textBlock->height)) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </td>
                    <td>
                        <?php if (isset($previewMap[$result->slideNumber])): ?>
                            <img class="preview" src="/<?= h($previewMap[$result->slideNumber]) ?>" alt="Preview">
                        <?php else: ?>
                            <span class="note">Превью недоступно.</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
