<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class UploadService
{
    public function __construct(
        private readonly string $uploadDir,
        private readonly int $maxBytes,
        private readonly array $allowedMimeTypes
    ) {
    }

    public function handleUpload(array $file): string
    {
        if (!isset($file['error'], $file['tmp_name'], $file['name'], $file['size'])) {
            throw new RuntimeException('Файл не передан.');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Ошибка загрузки файла.');
        }

        if ($file['size'] > $this->maxBytes) {
            throw new RuntimeException('Файл превышает допустимый размер.');
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($extension !== 'pptx') {
            throw new RuntimeException('Поддерживаются только файлы .pptx.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!in_array($mimeType, $this->allowedMimeTypes, true)) {
            throw new RuntimeException('Недопустимый MIME-тип файла.');
        }

        if (!is_dir($this->uploadDir) && !mkdir($this->uploadDir, 0755, true) && !is_dir($this->uploadDir)) {
            throw new RuntimeException('Не удалось создать каталог для загрузки.');
        }

        $safeName = bin2hex(random_bytes(8)) . '.pptx';
        $targetPath = rtrim($this->uploadDir, '/') . '/' . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new RuntimeException('Не удалось сохранить файл.');
        }

        return $targetPath;
    }
}
