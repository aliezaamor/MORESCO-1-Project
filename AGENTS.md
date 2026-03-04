# Agents

## Rules

### ⛔ Prohibited Commands

- **`php artisan migrate:fresh`** is strictly prohibited.
  - This command drops all tables and re-runs migrations from scratch, causing **permanent data loss**.
  - Use `php artisan migrate` for new migrations, or `php artisan migrate:rollback` to undo the last migration batch.
  - If you need to reset a specific table's structure, write a targeted migration instead.
