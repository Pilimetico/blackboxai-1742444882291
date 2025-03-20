<?php
class FileManager {
    private $baseUploadPath;
    private $allowedTypes;
    private $maxFileSize;

    public function __construct($baseUploadPath = '../uploads') {
        $this->baseUploadPath = rtrim($baseUploadPath, '/');
        $this->allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg'
        ];
        $this->maxFileSize = 5 * 1024 * 1024; // 5MB
    }

    public function listFiles($directory = '') {
        $path = $this->baseUploadPath . '/' . trim($directory, '/');
        $files = [];

        if (!is_dir($path)) {
            return $files;
        }

        $items = scandir($path);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . '/' . $item;
            $relativePath = trim($directory . '/' . $item, '/');

            if (is_file($fullPath)) {
                $files[] = [
                    'name' => $item,
                    'path' => $relativePath,
                    'size' => filesize($fullPath),
                    'modified' => filemtime($fullPath),
                    'type' => mime_content_type($fullPath),
                    'is_dir' => false
                ];
            } else if (is_dir($fullPath)) {
                $files[] = [
                    'name' => $item,
                    'path' => $relativePath,
                    'modified' => filemtime($fullPath),
                    'is_dir' => true
                ];
            }
        }

        usort($files, function($a, $b) {
            if ($a['is_dir'] && !$b['is_dir']) return -1;
            if (!$a['is_dir'] && $b['is_dir']) return 1;
            return strcasecmp($a['name'], $b['name']);
        });

        return $files;
    }

    public function createDirectory($path) {
        $fullPath = $this->baseUploadPath . '/' . trim($path, '/');
        
        if (!is_dir($fullPath)) {
            return mkdir($fullPath, 0755, true);
        }
        
        return true;
    }

    public function uploadFile($file, $directory = '') {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception('Invalid file parameter');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception($this->getUploadErrorMessage($file['error']));
        }

        if ($file['size'] > $this->maxFileSize) {
            throw new Exception('File exceeds maximum size limit');
        }

        $fileType = mime_content_type($file['tmp_name']);
        if (!isset($this->allowedTypes[$fileType])) {
            throw new Exception('File type not allowed');
        }

        $extension = $this->allowedTypes[$fileType];
        $fileName = sprintf('%s.%s', uniqid(), $extension);
        $uploadPath = $this->baseUploadPath . '/' . trim($directory, '/');

        if (!is_dir($uploadPath)) {
            if (!mkdir($uploadPath, 0755, true)) {
                throw new Exception('Failed to create upload directory');
            }
        }

        $destination = $uploadPath . '/' . $fileName;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Failed to move uploaded file');
        }

        return [
            'name' => $fileName,
            'path' => trim($directory . '/' . $fileName, '/'),
            'size' => filesize($destination),
            'type' => $fileType
        ];
    }

    public function deleteFile($path) {
        $fullPath = $this->baseUploadPath . '/' . trim($path, '/');
        
        if (!file_exists($fullPath)) {
            throw new Exception('File not found');
        }

        if (is_dir($fullPath)) {
            if (!$this->deleteDirectory($fullPath)) {
                throw new Exception('Failed to delete directory');
            }
        } else {
            if (!unlink($fullPath)) {
                throw new Exception('Failed to delete file');
            }
        }

        return true;
    }

    private function deleteDirectory($dir) {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($dir);
    }

    private function getUploadErrorMessage($error) {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload';
            default:
                return 'Unknown upload error';
        }
    }

    public function formatSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
?>