# Production Support Write Lane

This is the controlled write path for support fixes from Codex or the terminal.

## Goal

Keep production write access narrow and auditable:

- default support stays read-only via `/ops-readonly`
- write actions can run either through direct DB access or through `/ops-write`
- every risky repair should include:
  - a backup table or snapshot step
  - a clear final verification query
  - a rollback script or template

## Local Setup

Create or update:

- `scripts/live_db_write.env`
- `scripts/live_ops.env`

Template:

- [live_db_write.env.example](/Users/stjepanbelosa/Documents/product/scripts/live_db_write.env.example)

Recommended content when hosting does not offer SSH:

```bash
export FCC_LIVE_WRITE_TRANSPORT="http"
export FCC_OPS_BASE_URL="https://your-live-domain.com"
export FCC_OPS_WRITE_KEY="your-separate-long-random-write-secret"
```

## Runner

Use:

- [prod_db_apply.sh](/Users/stjepanbelosa/Documents/product/scripts/prod_db_apply.sh)
- [prod_db_query.sh](/Users/stjepanbelosa/Documents/product/scripts/prod_db_query.sh)

Preview a script without executing:

```bash
scripts/prod_db_apply.sh scripts/check_lejla_kovacevic_clicks_2026_04_10.sql
```

Apply a reviewed live repair:

```bash
scripts/prod_db_apply.sh --apply scripts/fix_lejla_kovacevic_main_app_and_ai_reset_2026_04_10.sql
```

Run a direct live query without a separate SQL file:

```bash
scripts/prod_db_query.sh --sql "SELECT COUNT(*) AS users_total FROM users"
```

Run a direct controlled write:

```bash
scripts/prod_db_query.sh --sql "UPDATE users SET status = 1 WHERE user_id = 555 LIMIT 1"
```

## Permission Model

Recommended approval prefix for Codex:

- `scripts/prod_db_query.sh`
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

`/ops-write` is intentionally narrower than raw SSH:

- one SQL statement per request
- token-protected
- audited to `uploads/main/fcc_ops_write_audit.log`
- limited to `SELECT/SHOW/DESCRIBE/EXPLAIN` and `INSERT/UPDATE/DELETE/REPLACE`
- blocks dangerous DDL and admin statements

## Good Pattern For Future Repairs

1. Create a dedicated SQL file in `scripts/`.
2. Add backup and rollback steps in the same PR.
3. Preview with `scripts/prod_db_apply.sh`.
4. Apply with `scripts/prod_db_apply.sh --apply ...`.
5. Save the returned `backup_key` in the support note.
