<?php

class ImageDownloader {
    private $inputFilePath;
    private $outputDirectory;
    private $logLevel;
    private $logFilePath;

    public function __construct($inputFilePath, $outputDirectory, $logLevel = 1) {
        $this->inputFilePath = $inputFilePath;
        $this->outputDirectory = $outputDirectory;
        $this->logLevel = $logLevel;
        $this->logFilePath = __DIR__ . '/download_log_' . date('Y-m-d_H-i-s') . '.log';

        $this->checkAndCreateDirectory();
    }

    private function checkAndCreateDirectory() {
        if (!is_dir($this->outputDirectory)) {
            mkdir($this->outputDirectory, 0755, true);
        }
    }

    public function downloadImages() {
        $startTime = date('Y-m-d H:i:s');
        $fileContent = file($this->inputFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $totalLinks = count($fileContent);
        $successCount = 0;
        $skipCount = 0;
        $errorCount = 0;

        $this->log("Начало работы скрипта: $startTime");
        $this->log("Количество ссылок для скачивания: $totalLinks");

        foreach ($fileContent as $url) {
            $this->processLink($url, $successCount, $skipCount, $errorCount);
        }

        $endTime = date('Y-m-d H:i:s');
        $this->log("Количество успешно скачанных файлов: $successCount");
        $this->log("Количество пропущенных файлов: $skipCount");
        $this->log("Количество ссылок с ошибками: $errorCount");
        $this->log("Окончание работы скрипта: $endTime");
    }

    private function processLink($url, &$successCount, &$skipCount, &$errorCount) {
        $filename = basename($url);
        $filePath = $this->outputDirectory . '/' . $filename;

        if (file_exists($filePath)) {
            $skipCount++;
            $this->log("Пропущено: $url (уже скачан)", 2);
            return;
        }

        $headers = @get_headers($url);

        if (!$headers || strpos($headers[0], '200') === false) {
            $errorCount++;
            $this->log("Ошибка: $url не доступен (HTTP код: " . ($headers ? $headers[0] : 'неизвестно') . ")", 2);
            return;
        }

        $imageContent = @file_get_contents($url);
        
        if ($imageContent === false) {
            $errorCount++;
            $this->log("Ошибка: не удалось скачать $url", 2);
            return;
        }

        file_put_contents($filePath, $imageContent);
        $successCount++;
        
        $fileSize = filesize($filePath);
        $humanReadableSize = $this->formatSize($fileSize);

        $this->log("Скачано: $url (размер: $humanReadableSize)", 3);
    }

    private function formatSize($size) {
        $units = ['Б', 'КБ', 'МБ', 'ГБ'];
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }
        
        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    private function log($message, $level = 1) {
        if ($level <= $this->logLevel) {
            $timestamp = date('Y-m-d H:i:s');
            file_put_contents($this->logFilePath, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
        }
    }
}

// Настройки скрипта
$inputFilePath = 'images.txt'; // Путь к текстовому файлу
$outputDirectory = 'downloaded_images'; // Директория для сохранения изображений
$logLevel = 3; // Уровень логирования (1, 2 или 3)

$imageDownloader = new ImageDownloader($inputFilePath, $outputDirectory, $logLevel);
$imageDownloader->downloadImages();
