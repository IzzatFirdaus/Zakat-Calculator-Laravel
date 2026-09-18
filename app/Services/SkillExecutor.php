<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process as SymfonyProcess;

class SkillExecutor
{
    private SkillRegistry $registry;

    private array $executionLog = [];

    private array $config;

    public function __construct(SkillRegistry $registry, ?array $config = null)
    {
        $this->registry = $registry;
        $this->config = $config ?? $this->loadConfig();
    }

    /**
     * Execute a single skill by ID with parameters.
     *
     * @param  array<string, mixed>  $parameters
     * @return array{status: string, output: mixed, error: string|null}
     */
    public function execute(string $skillId, array $parameters = []): array
    {
        $skill = $this->registry->get($skillId);

        if ($skill === null) {
            $this->log('skill_error', $skillId, 0, 'Skill not found');

            return ['status' => 'error', 'output' => null, 'error' => "Skill [{$skillId}] not found."];
        }

        if (! ($skill['enabled'] ?? true)) {
            $this->log('skill_skip', $skillId, 0, 'Skill disabled');

            return ['status' => 'skipped', 'output' => null, 'error' => 'Skill is disabled.'];
        }

        $command = $skill['command'] ?? null;

        if ($command === null) {
            $this->log('skill_skip', $skillId, 0, 'No command defined');

            return ['status' => 'skipped', 'output' => null, 'error' => 'No command defined for this skill.'];
        }

        $parameters = array_merge($this->defaults($skill), $parameters);
        $parameters = $this->trimOptions($skill, $parameters);

        $startMs = microtime(true);
        $result = $this->runCommand($command, $parameters, $skill['runner'] ?? 'artisan');
        $durationMs = (int) round((microtime(true) - $startMs) * 1000);

        $this->log('skill_executed', $skillId, $durationMs, $result['status'], $result['error']);

        return $result;
    }

    /**
     * Execute multiple skills in plan order (respecting dependencies).
     *
     * @param  array<int, string>  $skillPlan  Ordered skill IDs
     * @param  array<string, array<string, mixed>>  $parameters  Map of skillId → params
     * @return array<int, array{skill_id: string, status: string, output: mixed, error: string|null}>
     */
    public function executePlan(array $skillPlan, array $parameters = []): array
    {
        $results = [];
        $outputs = [];

        foreach ($skillPlan as $skillId) {
            $skillParams = $parameters[$skillId] ?? [];

            $result = $this->execute($skillId, $skillParams);

            $results[] = [
                'skill_id' => $skillId,
                'status' => $result['status'],
                'output' => $result['output'],
                'error' => $result['error'],
            ];

            $outputs[$skillId] = $result['output'];
        }

        return $results;
    }

    /**
     * Auto-discover and execute skills for a task description.
     *
     * @return array{disclosure: string, matches: array<int, array{id: string, name: string, description: string, relevance: string, triggers: string}>, plan: array<int, string>, results: array<int, array{skill_id: string, status: string, output: mixed, error: string|null}>}
     */
    public function autoExecute(string $taskDescription): array
    {
        $matcher = new SkillMatcher($this->registry);
        $matches = $matcher->find($taskDescription);

        $disclosure = $this->buildDisclosure($matches);

        $plan = $matcher->plan($matches);
        $results = $plan !== [] ? $this->executePlan($plan) : [];

        return [
            'disclosure' => $disclosure,
            'matches' => $matcher->summarize($matches),
            'plan' => $plan,
            'results' => $results,
        ];
    }

    /**
     * @return array<int, array{timestamp: string, event: string, skill_id: string, duration_ms: int, status: string, error: string|null}>
     */
    public function getLog(): array
    {
        return $this->executionLog;
    }

    /**
     * @param  array<string, mixed>  $skill
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function defaults(array $skill): array
    {
        $defaults = [];

        foreach ($skill['input'] ?? [] as $key => $definition) {
            if (array_key_exists('default', $definition)) {
                $defaults[$key] = $definition['default'];
            }
        }

        return $defaults;
    }

    /**
     * @param  array<string, mixed>  $skill
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function trimOptions(array $skill, array $params): array
    {
        $allowed = $skill['command_options'] ?? null;

        if (! is_array($allowed) || $allowed === []) {
            return $params;
        }

        $allowedMap = array_flip($allowed);

        return array_intersect_key($params, $allowedMap);
    }

    /**
     * Run a skill command. The command string may contain {param} placeholders,
     * filled positionally. Tokens whose placeholders have no value are dropped.
     *
     * @param  array<string, mixed>  $params
     */
    private function runCommand(string $command, array $params, string $runner): array
    {
        $tokens = [];
        $consumed = [];

        foreach (preg_split('/\s+/', trim($command)) as $token) {
            $resolved = $this->resolveToken($token, $params, $consumed);

            if ($resolved !== null) {
                $tokens[] = $resolved;
            }
        }

        $prefix = match ($runner) {
            'php-bin' => [PHP_BINARY],
            default => [PHP_BINARY, base_path('artisan')],
        };

        array_push($tokens, ...$this->buildOptions($params, $consumed));

        $process = new SymfonyProcess([...$prefix, ...$tokens]);
        $process->setTimeout((int) ($this->config['skill_automation']['timeout_seconds'] ?? 300));
        $process->setWorkingDirectory(base_path());

        try {
            $process->run();

            $output = $process->getOutput();
            $error = $process->getErrorOutput();

            if ($process->isSuccessful()) {
                return ['status' => 'success', 'output' => $output, 'error' => null];
            }

            return ['status' => 'error', 'output' => $output, 'error' => $error ?: 'Process failed with exit code '.$process->getExitCode()];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'output' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Replace {param} placeholders in a command token with positional values.
     * Returns null (token dropped) if any placeholder has no value.
     * Consumed param keys are tracked so they are not re-passed as options.
     *
     * @param  array<string, mixed>  $params
     * @param  array<string, bool>  $consumed
     */
    private function resolveToken(string $token, array $params, array &$consumed): ?string
    {
        if (! str_contains($token, '{')) {
            return $token;
        }

        if (! preg_match_all('/\{(\w+)\}/', $token, $matches)) {
            return $token;
        }

        $resolved = $token;

        foreach ($matches[1] as $key) {
            if (! array_key_exists($key, $params) || $params[$key] === null) {
                return null;
            }

            $resolved = str_replace('{'.$key.'}', (string) $params[$key], $resolved);
            $consumed[$key] = true;
        }

        return $resolved;
    }

    /**
     * Convert remaining params to --key value options. Booleans become flags.
     * Params consumed by placeholders are skipped.
     *
     * @param  array<string, mixed>  $params
     * @param  array<string, bool>  $consumed
     * @return array<int, string>
     */
    private function buildOptions(array $params, array $consumed = []): array
    {
        $args = [];

        foreach ($params as $key => $value) {
            if (isset($consumed[$key])) {
                continue;
            }

            if ($value === null) {
                continue;
            }

            if (is_bool($value)) {
                if ($value) {
                    $args[] = "--{$key}";
                }

                continue;
            }

            $args[] = "--{$key}";
            $args[] = (string) $value;
        }

        return $args;
    }

    private function log(string $event, string $skillId, int $durationMs = 0, string $status = 'success', ?string $error = null): void
    {
        $entry = [
            'timestamp' => now()->toIso8601String(),
            'event' => $event,
            'skill_id' => $skillId,
            'duration_ms' => $durationMs,
            'status' => $status,
            'error' => $error,
        ];

        $this->executionLog[] = $entry;

        if ($this->config['logging']['enabled'] ?? true) {
            Log::channel('skill_automation')->info('Skill automation event', $entry);
        }
    }

    /**
     * @param  array<int, array{skill: array{id: string, name: string, description: string, triggers: array<string, mixed>, input: array<string, mixed>, output: array<string, mixed>, dependencies: array<int, string>, enabled: bool, command: string}, relevance: float, matched_triggers: array<int, string>}>  $matches
     */
    private function buildDisclosure(array $matches): string
    {
        if ($matches === []) {
            return 'No matching skills found for this task.';
        }

        $lines = ['Skills detected for this task:'];

        foreach ($matches as $match) {
            $relevance = number_format($match['relevance'] * 100, 0);
            $lines[] = sprintf(
                '  - %s (%s%% relevance) — %s',
                $match['skill']['name'],
                $relevance,
                $match['skill']['description']
            );
        }

        $lines[] = '';
        $lines[] = 'Proceeding with execution...';

        return implode("\n", $lines);
    }

    private function loadConfig(): array
    {
        $configPath = base_path('.agents/config.json');

        if (! file_exists($configPath)) {
            return ['skill_automation' => [], 'logging' => []];
        }

        return json_decode(file_get_contents($configPath), true, 512, JSON_THROW_ON_ERROR);
    }
}
