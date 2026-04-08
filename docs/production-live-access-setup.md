# Production Live Access Setup

This is the practical setup for giving Codex high-value visibility into FCC production while keeping the risky parts controlled.

## What Is Already Implemented In Code

- readonly live diagnostics endpoint: `/ops-readonly`
- scopes: `health`, `overview`, `plans`, `billing`, `collaborators`, `collaborator`
- local helper: `scripts/prod_ops_fetch.sh`
- local SSH log helper: `scripts/prod_ops_logs.sh`

## Exact Steps For You

1. Deploy the latest `app/` changes to live.

Your current GitHub workflow already does this on push to `main`:

- [deploy-cpanel-ftp.yml](/Users/stjepanbelosa/Documents/product/.github/workflows/deploy-cpanel-ftp.yml)

2. Preferred method: on the live server create this file:

- `public_html/ops-readonly-config.php`

Content:

```php
<?php

define('FCC_OPS_READONLY_ENABLED', true);
define('FCC_OPS_READONLY_KEY', 'your-long-random-secret');
```

Template:

- [ops-readonly-config.php.example](/Users/stjepanbelosa/Documents/product/scripts/ops-readonly-config.php.example)

3. Alternative method, if your hosting really supports Apache env vars correctly, set these values:

```apacheconf
SetEnv FCC_OPS_READONLY_ENABLED 1
SetEnv FCC_OPS_READONLY_KEY your-long-random-secret
```

If you use cPanel/Apache, the easiest place is usually the live `.htaccess` in `public_html/` or an Apache include if your hosting provides one.

4. In the local workspace create:

- `scripts/live_ops.env`

You can copy the template from:

- [live_ops.env.example](/Users/stjepanbelosa/Documents/product/scripts/live_ops.env.example)

Minimal content:

```bash
export FCC_OPS_BASE_URL="https://your-live-domain.com"
export FCC_OPS_READONLY_KEY="your-long-random-secret"
```

5. Verify from the local workspace:

```bash
scripts/prod_ops_fetch.sh health pretty=1
scripts/prod_ops_fetch.sh overview pretty=1
```

6. If you want log visibility too, create a read-only SSH account or key and add these lines to `scripts/live_ops.env`:

```bash
export FCC_LIVE_SSH_HOST="your-live-host"
export FCC_LIVE_SSH_USER="your-readonly-user"
export FCC_LIVE_SSH_PORT="22"
export FCC_LIVE_SSH_KEY_PATH="$HOME/.ssh/your-live-key"
```

Then:

```bash
scripts/prod_ops_logs.sh ls
scripts/prod_ops_logs.sh error 200
scripts/prod_ops_logs.sh access 200
```

7. If you want direct database read access in addition to the app endpoint, create a readonly MySQL user using:

- [live_readonly_mysql_user.sql.example](/Users/stjepanbelosa/Documents/product/scripts/live_readonly_mysql_user.sql.example)

Recommended privileges:

- `SELECT`
- `SHOW VIEW`

Avoid giving write privileges to that user.

## What I Will Then Be Able To Do

- inspect a live collaborator by `user_id` or email
- compare plan state, AI access, billing state and app footprint in one place
- see billing risk users and recent billing events
- read Apache/PHP logs if SSH is configured
- help explain strange production behaviour much faster

## Best Permission Tiers

### Tier 1: Strongly recommended

- readonly `/ops-readonly`
- readonly SSH for logs only
- readonly DB user (`SELECT`, `SHOW VIEW`)

This is enough for high-quality support and diagnostics.

### Tier 2: Good controlled automation

If you want me to do some safe operational actions too, the best next permissions are not full root or full DB write. Better is a narrow allowlist such as:

- deploy via existing GitHub workflow
- sync live snapshot back to GitHub
- clear app cache
- trigger a safe health/smoke check
- open Stripe Customer Portal links

### Tier 3: High-trust support actions

Only after we are comfortable with Tier 1 and Tier 2:

- rerun a specific AI plan/app review job
- resend a specific billing or onboarding email
- mark a support workflow flag
- run a very limited billing/admin repair action

Those should always be allowlisted and logged, not general write access.

## What I Do Not Recommend

- full production DB write access
- shell/root write access without restrictions
- unrestricted ability to modify live files directly

## Fastest Path

If you want the shortest route to value, do this first:

1. deploy `app/`
2. create `public_html/ops-readonly-config.php`
3. optionally add readonly SSH for logs

That alone will already make our support chats much faster and more concrete.
