# Copilot Development Instructions

## Skill Automation

Before responding to any task in this project:

1. **Load**: Check `.agentrules` and `.agents/skills.json`
2. **Match**: Find skills that apply to the user's task
3. **Disclose**: Show matched skills with relevance scores
4. **Execute**: Run skills automatically via `php artisan skill:manage execute`
5. **Report**: Show results - what was created, errors, next steps

## Examples

**User**: "Create a Post model"
**Your response**:
```
📋 Matched Skills:
- model_create (0.98) — Scaffolds Eloquent models

🔧 Executing: Model Creator
   Running: php artisan make:model Post

✅ Created: app/Models/Post.php
```

**User**: "Build an API for comments"
**Your response**:
```
📋 Matched Skills:
- controller_create (0.95)
- api_resource_create (0.92)
- migration_create (0.88)
- request_create (0.85)

🔧 Executing in order:
   1. migration_create → database/migrations/create_comments_table.php
   2. api_resource_create → app/Http/Resources/CommentResource.php
   3. controller_create → app/Http/Controllers/CommentController.php
   4. request_create → app/Http/Requests/StoreCommentRequest.php

✅ Complete: API resource scaffolded
```

## Rules

- ✅ DO auto-invoke skills for development tasks
- ❌ DON'T ask "Would you like me to use skills?"
- ❌ DON'T ask "Should I proceed?"
- ✅ DO show which skills you're using
- ✅ DO show skill execution output
- ❌ DON'T fall back to manual help without trying skills first

## Override Commands

- "Don't use skills for this task" → Bypass automation for that task
- "Use only [skill_name]" → Restrict to specific skill
- "Disable skill automation" → Turn off for current session only