<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\File;

class SkillRegistry
{
    /** @var array<string, array{triggers: array<string, mixed>, dependencies: array<int, string>, enabled: bool, command: string, id: string, name: string, description: string, input: array<string, mixed>, output: array<string, mixed>}> */
    private array $skills = [];

    private array $config = [];

    private string $registryPath;

    public function __construct(?string $registryPath = null)
    {
        $this->registryPath = $registryPath ?? base_path('.agents/skills.json');
        $this->load();
    }

    private function load(): void
    {
        if (! File::exists($this->registryPath)) {
            $this->skills = [];
            $this->config = [];

            return;
        }

        $data = json_decode(File::get($this->registryPath), true, 512, JSON_THROW_ON_ERROR);
        $this->skills = array_column($data['skills'] ?? [], null, 'id');
        $this->config = $data ?? [];
    }

    /**
     * @return array<string, array{triggers: array<string, mixed>, dependencies: array<int, string>, enabled: bool, command: string, id: string, name: string, description: string, input: array<string, mixed>, output: array<string, mixed>}>
     */
    public function all(): array
    {
        return $this->skills;
    }

    /**
     * @return array<string, array{triggers: array<string, mixed>, dependencies: array<int, string>, enabled: bool, command: string, id: string, name: string, description: string, input: array<string, mixed>, output: array<string, mixed>}>
     */
    public function enabled(): array
    {
        return array_filter($this->skills, static fn (array $skill): bool => $skill['enabled'] ?? true);
    }

    public function get(string $id): ?array
    {
        return $this->skills[$id] ?? null;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->skills);
    }

    public function count(): int
    {
        return count($this->skills);
    }

    public function countEnabled(): int
    {
        return count($this->enabled());
    }

    public function registryPath(): string
    {
        return $this->registryPath;
    }

    /**
     * @return array{triggers: array<string, mixed>, dependencies: array<int, string>, enabled: bool, command: string, id: string, name: string, description: string, input: array<string, mixed>, output: array<string, mixed>}
     */
    public function require(string $id): array
    {
        $skill = $this->get($id);

        if ($skill === null) {
            throw new \InvalidArgumentException("Skill [{$id}] not found in registry.");
        }

        return $skill;
    }
}
