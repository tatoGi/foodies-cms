---
name: restore-database-folder
description: Restore missing or broken Laravel database directory structure, including database, migrations, seeders, and factories folders with safe placeholder files. Use when a project is missing its database folder, when scaffolding was deleted, or when setup fails due to absent database paths.
---

# Restore Database Folder

Recreate Laravel's standard `database/` structure safely.

## Workflow

1. Run `scripts/restore_database_folder.ps1` from the project root.
2. Create the required directories if they do not exist:
   - `database/`
   - `database/factories/`
   - `database/migrations/`
   - `database/seeders/`
3. Add placeholder files only when needed:
   - `database/.gitignore`
   - `.gitkeep` in empty subdirectories
4. Detect sqlite usage from `.env` (`DB_CONNECTION=sqlite`) and create `database/database.sqlite` if missing.
5. Verify with `Get-ChildItem database -Recurse`.

## Rules

- Do not delete or overwrite existing migration, seeder, or factory files.
- Do not modify `.env` automatically.
- Keep changes limited to missing directory and placeholder restoration.

## References

Read `references/laravel-database-layout.md` when you need expected structure details.
