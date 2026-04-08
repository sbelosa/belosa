# Production Ops Readonly

`ops-readonly` is a token-protected production diagnostics endpoint for FCC. It is designed for read-only support work from Codex or the terminal without granting write access to production.

## What It Exposes

- `health`: app/server feature flags, cron heartbeat, billing risk snapshot
- `overview`: collaborator totals, biolink/app totals, plan mix, recent billing events
- `plans`: plan catalog with collaborator counts and active subscription counts
- `billing`: billing risk dashboard plus recent billing events
- `collaborators`: quick collaborator search by name/email
- `collaborator`: detailed collaborator snapshot with plan, meta, apps, AI state, billing summary and billing events

## Security Model

- Endpoint path: `/ops-readonly`
- Access: shared secret only
- Permissions: read-only
- Sessions: disabled
- Indexing: disabled

The endpoint is disabled by default.

## Live Setup

Set these live environment values:

- `FCC_OPS_READONLY_ENABLED=1`
- `FCC_OPS_READONLY_KEY=<strong-random-secret>`

The app reads these from `getenv()` and also from `$_SERVER`, so Apache/cPanel `SetEnv` style configuration also works.

## Local Usage

Set these local shell vars before calling the helper script:

```bash
export FCC_OPS_BASE_URL="https://your-live-domain.com"
export FCC_OPS_READONLY_KEY="your-shared-secret"
```

Examples:

```bash
scripts/prod_ops_fetch.sh health pretty=1
scripts/prod_ops_fetch.sh overview pretty=1
scripts/prod_ops_fetch.sh collaborators query=stjepan limit=5 pretty=1
scripts/prod_ops_fetch.sh collaborator user_id=555 billing_events_limit=12 pretty=1
scripts/prod_ops_fetch.sh collaborator email=ana@example.com pretty=1
scripts/prod_ops_fetch.sh billing state=past_due_critical limit=10 pretty=1
scripts/prod_ops_fetch.sh plans pretty=1
```

## Collaborator Detail Payload

`scope=collaborator` returns a consolidated snapshot:

- base collaborator identity and activity
- current plan and subscription references
- FCC meta such as Forever ID, approval/card timestamps, address lines
- app footprint: main app, latest updated app, biolink totals, block totals
- AI state: access flags, latest weekly plan, latest app review, job status, mentor guidance preview
- billing state: Stripe customer id, billing risk summary, recent billing events

## Good Next Step

This endpoint covers application and business state from the live database. If we also want raw Apache/PHP logs or server process visibility, add separate read-only SSH/log access on top of this.
