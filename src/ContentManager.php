<?php

namespace App;

class ContentManager
{
    private string $contentDir;
    private MarkdownParser $parser;
    private ?string $lastHash = null;
    private array $excludeDirs;

    public function __construct(string $contentDir, array $excludeDirs = ['img'])
    {
        $this->contentDir = rtrim($contentDir, '/');
        $this->parser = new MarkdownParser();
        $this->excludeDirs = $excludeDirs;
    }

    public function getContentDir(): string
    {
        return $this->contentDir;
    }

    private function shouldExclude(string $path): bool
    {
        foreach ($this->excludeDirs as $dir) {
            if (str_contains($path, '/' . $dir . '/') || str_starts_with($path, $dir . '/')) {
                return true;
            }
        }
        return false;
    }

    public function getFileList(): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->contentDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;
            $relativePath = str_replace($this->contentDir . '/', '', $file->getPathname());
            if ($this->shouldExclude($relativePath)) continue;
            $files[] = [
                'path'  => $relativePath,
                'name'  => $file->getBasename('.' . $file->getExtension()),
                'mtime' => $file->getMTime(),
            ];
        }

        usort($files, fn($a, $b) => $a['path'] <=> $b['path']);
        return $files;
    }

    public function getFile(string $path): ?array
    {
        $fullPath = $this->contentDir . '/' . ltrim($path, '/');
        if (!file_exists($fullPath)) {
            return null;
        }

        $content = file_get_contents($fullPath);
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        if ($ext === 'md' || $ext === 'markdown') {
            $parsed = $this->parser->parse($content);
        } else {
            $parsed = [
                'metadata' => [],
                'body'     => $content,
                'html'     => '<pre style="white-space:pre-wrap;font-family:inherit;color:var(--text-secondary)">' . htmlspecialchars($content, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</pre>',
            ];
        }

        return [
            'path'     => $path,
            'name'     => pathinfo($fullPath, PATHINFO_FILENAME),
            'mtime'    => filemtime($fullPath),
            'metadata' => $parsed['metadata'],
            'body'     => $parsed['body'],
            'html'     => $parsed['html'],
        ];
    }

    public function getAllContent(): array
    {
        $files = $this->getFileList();
        $content = [];

        foreach ($files as $file) {
            $content[] = $this->getFile($file['path']);
        }

        return $content;
    }

    public function hasChanges(): bool
    {
        $files = $this->getFileList();
        $currentHash = $this->parser->getContentHash($files);

        if ($this->lastHash === null) {
            $this->lastHash = $currentHash;
            return true;
        }

        if ($this->lastHash !== $currentHash) {
            $this->lastHash = $currentHash;
            return true;
        }

        return false;
    }

    public function checkChanges(): array
    {
        $files = $this->getFileList();

        if ($this->lastHash === null) {
            $this->lastHash = $this->parser->getContentHash($files);
            return [
                'changed' => true,
                'files'   => $this->getAllContent(),
            ];
        }

        $currentHash = $this->parser->getContentHash($files);

        if ($this->lastHash !== $currentHash) {
            $this->lastHash = $currentHash;
            return [
                'changed' => true,
                'files'   => $this->getAllContent(),
            ];
        }

        return [
            'changed' => false,
        ];
    }
}
