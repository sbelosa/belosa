# Production Support Write Lane

This is the controlled write path for support fixes that are already prepared as reviewed SQL files in `scripts/`.

## Goal

Keep production write access narrow and auditable:

- default support stays read-only via `/ops-readonly`
- write actions run only from explicit `.sql` files in `scripts/`
- every risky repair should include:
  - a backup table or snapshot step
  - a clear final verification query
  - a rollback script or template

## Local Setup

Create:

- `scripts/live_db_write.env`

Template:

- [live_db_write.env.example](/Users/stjepanbelosa/Documents/product/scripts/live_db_write.env.example)

Minimal content:

```bash
export FCC_LIVE_WRITE_DB_HOST="127.0.0.1"
export FCC_LIVE_WRITE_DB_PORT="3306"
export FCC_LIVE_WRITE_DB_NAME="app"
export FCC_LIVE_WRITE_DB_USER="fcc_codex_write"
export FCC_LIVE_WRITE_DB_PASSWORD="your-long-random-password"
```

## Runner

Use:

- [prod_db_apply.sh](/Users/stjepanbelosa/Documents/product/scripts/prod_db_apply.sh)

Preview a script without executing:

```bash
scripts/prod_db_apply.sh scripts/check_lejla_kovacevic_clicks_2026_04_10.sql
```

Apply a reviewed live repair:

```bash
scripts/prod_db_apply.sh --apply scripts/fix_lejla_kovacevic_main_app_and_ai_reset_2026_04_10.sql
```

## Permission Model

Recommended approval prefix for Codex:

- `scripts/prod_db_apply.sh`

That keeps future approvals narrow:

- only versioned SQL files inside `scripts/`
- preview mode available before execution
- one stable command path for support repairs

## What To Avoid

Do not grant:

- unrestricted shell write access on production
- unrestricted direct `mysql -e` command approval
- general-purpose production DB write commands without a reviewed file

## Good Pattern For Future Repairs

1. Create a dedicated SQL file in `scripts/`.
2. Add backup and rollback steps in the same PR.
3. Preview with `scripts/prod_db_apply.sh`.
4. Apply with `scripts/prod_db_apply.sh --apply ...`.
5. Save the returned `backup_key` in the support note.
