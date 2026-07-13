<?php

class GoogleDriveStorageService
{
    public function rootFolderName(): string
    {
        $name = trim((string) (getenv('GOOGLE_DRIVE_ROOT_FOLDER_NAME') ?: 'FlowDesk'));
        return $this->sanitizeDriveName($name !== '' ? $name : 'FlowDesk');
    }

    public function clientFolderName(int $clienteId, string $clienteNome): string
    {
        $name = $this->sanitizeDriveName($clienteNome);
        if ($name === '') {
            $name = 'Cliente';
        }

        return sprintf('%s #%d', $name, $clienteId);
    }

    public function sanitizeDriveName(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        $name = preg_replace('/[\\\\\/:*?"<>|]+/u', '-', $name) ?? '';

        return trim(mb_substr($name, 0, 140));
    }

    public function isAllowedClientFile(string $mimeType, string $originalName): bool
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = [
            'jpg', 'jpeg', 'png', 'webp', 'gif', 'svg',
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv',
            'zip', 'rar', 'txt',
        ];

        if (in_array($extension, $allowedExtensions, true)) {
            return true;
        }

        return str_starts_with($mimeType, 'image/')
            || str_starts_with($mimeType, 'application/pdf');
    }
}
