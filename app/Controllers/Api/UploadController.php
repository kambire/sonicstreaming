<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;

final class UploadController extends Controller
{
    public function upload(Request $request): void
    {
        // Soporte CORS
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit(0);
        }

        // 1. Log request details for debugging
        $logFile = BASE_PATH . '/storage/logs/api_upload_debug.log';
        $logData = [
            'time' => date('Y-m-d H:i:s'),
            'headers' => getallheaders(),
            'post' => $_POST,
            'files' => $_FILES,
        ];
        @file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

        // 2. Find the first uploaded file in $_FILES
        if (empty($_FILES)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No files uploaded']);
            return;
        }

        $fileKey = array_key_first($_FILES);
        $file = $_FILES[$fileKey];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Upload error code: ' . $file['error']]);
            return;
        }

        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts, true)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid file extension: ' . $ext]);
            return;
        }

        $uploadDir = BASE_PATH . '/public/uploads/api';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $filename = 'img_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $uploadDir . '/' . $filename;

        if (@move_uploaded_file($file['tmp_name'], $dest) || @copy($file['tmp_name'], $dest)) {
            // Construir URL absoluta o relativa.
            // Para ser seguros con HTTP/HTTPS y puertos, usamos la URL base detectada.
            $reqHost = $_SERVER['HTTP_HOST'] ?? 'radiospp.geeks.com.py';
            $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
                || (($_SERVER['SERVER_PORT'] ?? null) == 443)
                || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
            
            $proto = $isHttps ? 'https://' : 'http://';
            $url = $proto . $reqHost . base_url() . '/uploads/api/' . $filename;

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'url' => $url,
                'path' => '/uploads/api/' . $filename
            ]);
            return;
        }

        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to save uploaded file']);
    }
}
