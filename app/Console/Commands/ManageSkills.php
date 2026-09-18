<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SkillExecutor;
use App\Services\SkillMatcher;
use App\Services\SkillRegistry;
use Illuminate\Console\Command;

class ManageSkills extends Command
{
    protected $signature = 'skill:manage
                                {action=list : The action to perform (list, find, run, info, execute)}
                                {argument? : The skill ID or task description}';

    protected $description = 'Manage and interact with the skill automation system';

    private SkillRegistry $registry;

    private SkillExecutor $executor;

    public function __construct(SkillRegistry $registry, SkillExecutor $executor)
    {
        parent::__construct();
        $this->registry = $registry;
        $this->executor = $executor;
    }

    public function handle(): int
    {
        $action = $this->argument('action') ?? 'list';
        $argument = $this->argument('argument');

        return match ($action) {
            'list' => $this->handleList(),
            'find' => $this->handleFind($argument ?? ''),
            'run' => $this->handleRun($argument ?? ''),
            'info' => $this->handleInfo($argument ?? ''),
            'execute' => $this->handleExecute($argument ?? ''),
            default => $this->invalidAction($action),
        };
    }

    private function handleList(): int
    {
        $skills = $this->registry->enabled();
        $count = count($skills);

        $this->info("Available Skills ({$count} total):");
        $this->newLine();

        foreach ($skills as $id => $skill) {
            $command = $skill['command'] ?? 'custom';
            $this->line(sprintf(
                '  <info>%-25s</info> %s <comment>(%s)</comment>',
                $id,
                $skill['name'],
                $command
            ));
            $this->line("    {$skill['description']}");
        }

        return Command::SUCCESS;
    }

    private function handleFind(string $taskDescription): int
    {
        if ($taskDescription === '') {
            $this->error('Please provide a task description to search for.');

            return Command::INVALID;
        }

        $matcher = new SkillMatcher($this->registry);
        $matches = $matcher->find($taskDescription);
        $summary = $matcher->summarize($matches);

        if ($summary === []) {
            $this->warn('No matching skills found for: '.$taskDescription);

            return Command::SUCCESS;
        }

        $this->info("Matching Skills for: \"{$taskDescription}\"");
        $this->newLine();

        foreach ($summary as $match) {
            $this->line(sprintf(
                '  <info>%s</info> (%s relevance)',
                $match['name'],
                $match['relevance']
            ));
            $this->line("    {$match['description']}");
            $this->line("    <comment>Triggers:</comment> {$match['triggers']}");
        }

        return Command::SUCCESS;
    }

    private function handleRun(string $skillId): int
    {
        if ($skillId === '') {
            $this->error('Please provide a skill ID to run.');

            return Command::INVALID;
        }

        $skill = $this->registry->get($skillId);

        if ($skill === null) {
            $this->error("Skill [{$skillId}] not found.");

            return Command::INVALID;
        }

        $this->info("Running skill: {$skill['name']}");

        $result = $this->executor->execute($skillId);

        if ($result['status'] === 'success') {
            $this->info('Status: Success');
            if ($result['output']) {
                $this->line($result['output']);
            }
        } else {
            $this->error("Status: {$result['status']}");
            if ($result['error']) {
                $this->error($result['error']);
            }
        }

        return Command::SUCCESS;
    }

    private function handleInfo(string $skillId): int
    {
        if ($skillId === '') {
            $this->error('Please provide a skill ID.');

            return Command::INVALID;
        }

        $skill = $this->registry->get($skillId);

        if ($skill === null) {
            $this->error("Skill [{$skillId}] not found.");

            return Command::INVALID;
        }

        $this->info("Skill: {$skill['name']}");
        $this->line("  <comment>ID:</comment> {$skillId}");
        $this->line("  <comment>Description:</comment> {$skill['description']}");
        $this->line('  <comment>Command:</comment> '.($skill['command'] ?? 'custom'));
        $this->line('  <comment>Enabled:</comment> '.($skill['enabled'] ? 'Yes' : 'No'));

        $this->newLine();
        $this->line('  <comment>Triggers:</comment>');

        foreach ($skill['triggers'] as $type => $values) {
            if ($values !== []) {
                $this->line("    {$type}: ".implode(', ', $values));
            }
        }

        if ($skill['dependencies'] !== []) {
            $this->newLine();
            $this->line('  <comment>Dependencies:</comment> '.implode(', ', $skill['dependencies']));
        }

        return Command::SUCCESS;
    }

    private function handleExecute(string $taskDescription): int
    {
        if ($taskDescription === '') {
            $this->error('Please provide a task description.');

            return Command::INVALID;
        }

        $this->info("Auto-executing skills for: \"{$taskDescription}\"");
        $this->newLine();

        $result = $this->executor->autoExecute($taskDescription);

        $this->info('Disclosure:');
        $this->line($result['disclosure']);
        $this->newLine();

        if ($result['results'] === []) {
            $this->warn('No skills were executed.');

            return Command::SUCCESS;
        }

        $this->info('Execution Results:');
        foreach ($result['results'] as $r) {
            $statusColor = $r['status'] === 'success' ? 'info' : 'error';
            $this->line(sprintf('  [%s] %s: %s', strtoupper($r['status']), $r['skill_id'], $r['error'] ?? 'OK'));
        }

        return Command::SUCCESS;
    }

    private function invalidAction(string $action): int
    {
        $this->error("Unknown action: {$action}");
        $this->line('Available actions: list, find, run, info, execute');

        return Command::INVALID;
    }
}
