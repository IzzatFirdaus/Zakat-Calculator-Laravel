<?php

declare(strict_types=1);

namespace App\Services;

class SkillMatcher
{
    private SkillRegistry $registry;

    public function __construct(SkillRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Find skills matching a task description, ordered by relevance.
     *
     * @return array<int, array{skill: array{id: string, name: string, description: string, triggers: array{keywords: array<int, string>, file_types: array<int, string>, task_categories: array<int, string>, patterns: array<int, string>}, input: array<string, mixed>, output: array<string, mixed>, dependencies: array<int, string>, enabled: bool, command: string}, relevance: float, matched_triggers: array<int, string>}>
     */
    public function find(string $taskDescription, float $threshold = 0.60): array
    {
        $taskLower = strtolower($taskDescription);
        $matches = [];

        foreach ($this->registry->enabled() as $skill) {
            $score = 0.0;
            $triggers = [];

            foreach ($skill['triggers']['keywords'] ?? [] as $keyword) {
                if (str_contains($taskLower, strtolower($keyword))) {
                    $score += 0.3;
                    $triggers[] = "keyword:{$keyword}";
                }
            }

            foreach ($skill['triggers']['file_types'] ?? [] as $fileType) {
                if (str_contains($taskLower, $fileType)) {
                    $score += 0.4;
                    $triggers[] = "file_type:{$fileType}";
                }
            }

            foreach ($skill['triggers']['task_categories'] ?? [] as $category) {
                $humanCategory = str_replace('_', ' ', $category);
                if (str_contains($taskLower, $humanCategory)) {
                    $score += 0.3;
                    $triggers[] = "category:{$category}";
                }
            }

            foreach ($skill['triggers']['patterns'] ?? [] as $pattern) {
                if (preg_match('/'.$pattern.'/i', $taskDescription)) {
                    $score += 0.4;
                    $triggers[] = "pattern:{$pattern}";
                }
            }

            if ($score >= $threshold) {
                $matches[] = [
                    'skill' => $skill,
                    'relevance' => min($score, 1.0),
                    'matched_triggers' => $triggers,
                ];
            }
        }

        usort($matches, static fn (array $a, array $b) => $b['relevance'] <=> $a['relevance']);

        return $matches;
    }

    /**
     * Build a topologically-sorted execution plan respecting dependencies.
     *
     * @param  array<int, array{skill: array{id: string, name: string, description: string, triggers: array<string, mixed>, input: array<string, mixed>, output: array<string, mixed>, dependencies: array<int, string>, enabled: bool, command: string}, relevance: float, matched_triggers: array<int, string>}>  $matches
     * @return array<int, string> Skill IDs in execution order
     */
    public function plan(array $matches): array
    {
        $executed = [];
        $plan = [];

        $addWithDeps = function (string $skillId) use (&$addWithDeps, &$executed, &$plan): void {
            if (in_array($skillId, $executed, true)) {
                return;
            }

            $skill = $this->registry->get($skillId);

            if ($skill === null) {
                return;
            }

            foreach ($skill['dependencies'] ?? [] as $depId) {
                $addWithDeps($depId);
            }

            $plan[] = $skillId;
            $executed[] = $skillId;
        };

        foreach ($matches as $match) {
            $addWithDeps($match['skill']['id']);
        }

        return $plan;
    }

    /**
     * Summarize matches for user disclosure.
     *
     * @param  array<int, array{skill: array{id: string, name: string, description: string, triggers: array<string, mixed>, input: array<string, mixed>, output: array<string, mixed>, dependencies: array<int, string>, enabled: bool, command: string}, relevance: float, matched_triggers: array<int, string>}>  $matches
     * @return array<int, array{id: string, name: string, description: string, relevance: string, triggers: string}>
     */
    public function summarize(array $matches): array
    {
        return array_map(static fn (array $match) => [
            'id' => $match['skill']['id'],
            'name' => $match['skill']['name'],
            'description' => $match['skill']['description'],
            'relevance' => number_format($match['relevance'] * 100, 0).'%',
            'triggers' => implode(', ', $match['matched_triggers']),
        ], $matches);
    }
}
