# Skill Automation Guide

## What Is Skill Automation?

This project uses an automated skill discovery system that:
- Understands Laravel development tasks
- Automatically matches them to 24 pre-built skills
- Executes skills without manual prompting
- Reports results clearly

## For Users / Developers

You don't need to do anything special. Just ask your agent to do something:

```
"Create a blog model with title, content, author_id fields"
```

The agent will:
1. Show: "📋 Found 2 skills: model_create (0.98), migration_create (0.92)"
2. Execute: Run the skills automatically
3. Report: Show what was created

## For Setting Up New Agents

If adding a new agent to this project:

1. **Check if `.agentrules` exists** (it should auto-load)
2. **For Copilot**: Add reference to `.copilot/instructions.md`
3. **For Windsurf**: Add reference to `.windsurf/rules.json`
4. **For Claude**: Add custom instruction (see README)
5. **Test**: Give a simple task, verify skills are invoked

## Troubleshooting

**Q: Agent isn't using skills**
A: Ensure agent has loaded `.agentrules` or corresponding config file

**Q: Want to bypass skills for a task?**
A: Say "Do this manually" or "Don't use skills"

**Q: How to add new skills?**
A: Edit `.agents/skills.json` using the template in `.agents/templates/skill_template.json`

## Available Skills (24 Total)

| Skill | Purpose |
|-------|---------|
| `migration_create` | Create database migrations |
| `model_create` | Create Eloquent models |
| `controller_create` | Generate resource controllers |
| `api_resource_create` | Scaffold API resources |
| `seeder_create` | Create database seeders |
| `factory_create` | Create model factories |
| `test_feature_create` | Generate Pest feature tests |
| `test_run` | Run Pest/PHPUnit tests |
| `request_create` | Create form requests |
| `policy_create` | Create authorization policies |
| `middleware_create` | Create middleware classes |
| `job_create` | Create queued jobs |
| `event_create` | Create events and listeners |
| `mail_create` | Create mailable classes |
| `notification_create` | Create notifications |
| `class_create` | Create generic PHP classes |
| `enum_create` | Create PHP enums |
| `route_list` | List and inspect routes |
| `code_format` | Format code with Pint |
| `analysis_phpstan` | Run PHPStan static analysis |
| `db_seed` | Run database seeders |
| `config_cache` | Cache configuration files |
| `queue_work` | Start a queue worker |
| `storage_link` | Create storage symlink |

See `.agents/skills.json` for complete list.

## Architecture

```
User Task
  → SkillMatcher (keyword/file_type/category/regex scoring)
  → SkillRegistry (loads .agents/skills.json)
  → SkillExecutor (runs php artisan or vendor binaries)
  → storage/logs/skill_automation.log
```

## Override Commands

- "Don't use skills for this task" → Bypass automation for that task
- "Use only [skill_name]" → Restrict to specific skill
- "Show me the skill [skill_name]" → Display skill definition
- "Disable skill automation" → Turn off for current session only