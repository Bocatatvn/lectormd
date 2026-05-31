<?php

namespace App;

class ProjectManager
{
    private array $projects;

    public function __construct(string $configPath)
    {
        $raw = json_decode(file_get_contents($configPath), true);
        $this->projects = [];

        foreach ($raw as $p) {
            $rawExclude = $p['exclude'] ?? ['img'];
            if (is_string($rawExclude)) $rawExclude = [$rawExclude];
            $exclude = [];
            foreach ($rawExclude as $e) {
                foreach (explode(',', $e) as $part) {
                    $exclude[] = trim($part);
                }
            }
            $this->projects[$p['id']] = [
                'name'    => $p['name'],
                'dir'     => $p['dir'],
                'token'   => $p['token'] ?? null,
                'exclude' => $exclude,
            ];
        }
    }

    public function getProjectList(): array
    {
        $list = [];
        foreach ($this->projects as $id => $p) {
            $list[] = [
                'id'     => $id,
                'name'   => $p['name'],
                'locked' => $p['token'] !== null,
            ];
        }
        return $list;
    }

    public function isValidToken(string $projectId, string $token): bool
    {
        $p = $this->projects[$projectId] ?? null;
        if (!$p) return false;
        if ($p['token'] === null) return true;
        return $p['token'] === $token;
    }

    public function getContentManager(string $projectId, ?string $token = null): ?ContentManager
    {
        $p = $this->projects[$projectId] ?? null;
        if (!$p) return null;
        if ($p['token'] !== null && $p['token'] !== $token) return null;

        return new ContentManager(__DIR__ . '/../' . $p['dir'], $p['exclude']);
    }
}
