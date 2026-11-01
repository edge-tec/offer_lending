<?php
declare(strict_types=1);

namespace App;

/**
 * Media Library & Asset Manager
 */
class Media
{
    private \PDO $db;
    private string $uploadDir;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->uploadDir = __DIR__ . '/../public/uploads/';
        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0775, true);
        }
    }

    /**
     * Get all media files
     */
    public function getAll(string $folder = 'all'): array
    {
        $where = $folder !== 'all' ? "WHERE folder = " . $this->db->quote($folder) : "";
        $sql = "SELECT * FROM media_library {$where} ORDER BY id DESC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Upload an image file
     */
    public function upload(array $file, string $folder = 'general'): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Upload error code: ' . $file['error']];
        }

        if (!is_dir($this->uploadDir) && !@mkdir($this->uploadDir, 0775, true)) {
            return ['success' => false, 'message' => 'Upload directory does not exist and could not be created. Please verify server permissions.'];
        }

        if (!is_writable($this->uploadDir)) {
            return ['success' => false, 'message' => 'Upload directory is not writable. Please set write permissions (chmod -R 775 public/uploads).'];
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!in_array($mime, $allowedTypes, true)) {
            return ['success' => false, 'message' => 'Invalid file format. Only JPG, PNG, WEBP, and GIF are allowed.'];
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $cleanName = preg_replace('/[^a-z0-9_-]/i', '_', pathinfo($file['name'], PATHINFO_FILENAME));
        $newFilename = $cleanName . '_' . time() . '.' . strtolower($ext);
        $destination = $this->uploadDir . $newFilename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => false, 'message' => 'Failed to save uploaded file.'];
        }

        $fileUrl = '/uploads/' . $newFilename;
        $fileSize = filesize($destination);
        $imageSize = @getimagesize($destination);
        $width = $imageSize[0] ?? null;
        $height = $imageSize[1] ?? null;

        $stmt = $this->db->prepare("
            INSERT INTO media_library (file_name, file_path, file_url, file_type, file_size, width, height, folder, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$file['name'], $destination, $fileUrl, $mime, $fileSize, $width, $height, $folder]);

        return [
            'success' => true,
            'message' => 'File uploaded successfully.',
            'url' => $fileUrl,
            'id' => (int)$this->db->lastInsertId()
        ];
    }

    /**
     * Delete media file
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT file_path FROM media_library WHERE id = ?");
        $stmt->execute([$id]);
        $path = $stmt->fetchColumn();

        if ($path && file_exists((string)$path)) {
            @unlink((string)$path);
        }

        $del = $this->db->prepare("DELETE FROM media_library WHERE id = ?");
        return $del->execute([$id]);
    }
}

