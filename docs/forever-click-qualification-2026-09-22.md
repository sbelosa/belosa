# Immutable Forever click qualification

The tracking accept decision and the 15+/50+ read model now use the same stored classification. Ordinary buttons to allowed Forever destinations qualify equally. Page visits and arbitrary UTM tags do not create new qualified clicks.

## Data and migration

`track_links.fcc_click_kind` stores app/blog/direct plus shop/registration, or none. `fcc_parent_link_id` and `fcc_integrity_accept_id` retain attribution and the acceptance association. Existing columns, UTM values, counts and suspicious attempts are retained.

A serialized, resumable first-use migration adds the columns/index and freezes historical classifications. Exact user, timestamp, nullable link/block IDs and byte-equal visitor ID match retained acceptance evidence. Only a matched allowed destination can promote a historical ordinary link. Where evidence has expired, the old classification is frozen, not guessed from a newly edited ordinary button. No rejected attempts are promoted. `fcc_click_kind` default `none` marks migration complete; a failure before finalization remains retryable.

All public qualification predicates and app/blog breakdowns read the stored kind. Coach growth aliases now match the public qualification signal; the old contact sum remains available as engagement_signal. Existing VIP funnel contributions and the seven-day network rule are unchanged.

Acceptance is serialized per collaborator using a MySQL advisory lock. Both acceptance and qualified track row commit in one transaction; failed writes roll back so a lost track record cannot consume the visitor slot. Deduplication checks only acceptance rows linked to a stored qualified track row.

## Deployment and verification

The existing production GitHub workflow runs release guards and real MariaDB integration tests before deployment. It installs backward-compatible destination helpers, then tracking/schema helpers, then app/controllers so FTP upload order cannot expose controllers before their dependencies. After deployment, verify the deployment SHA and collaborator diagnostics. `ai.public_signal_summary.qualification_version = 1` identifies the new read model. Compare Coach/public totals, app+blog components, and LOS chart for the same 30-day window. Do not generate synthetic clicks on real collaborators.

Integration tests cover matched historical ordinary links, preserved older records, strict domains, invalid destinations not consuming a slot, repeat destinations, orphan acceptance records, the unchanged network rule, app/blog totals, edits and deletion of buttons, spoofed UTM values, failed inserts with rollback, concurrent requests, and actual public/Coach/LOS chart queries.

## Rollback

Revert this release's code commit and redeploy the preceding code through the standard workflow. The migration changes only newly added snapshot columns and adds an index; original event data is retained. Leave new columns in place during rollback (old code ignores them). If old tracking runs after rollback, reconcile those unclassified events in a reviewed forward migration before re-enabling the new reader; do not blindly reset every historical snapshot or delete columns on the live database.

## Known boundaries

Historical clicks with expired or absent acceptance evidence cannot be safely reclassified as ordinary Forever links. Technical visitor IDs are not verified people. Network deduplication remains unchanged in this release as requested. SQL/transaction behavior assumes InnoDB as used by the application and tests. Deliberate analytics-data deletion is distinct from editing a button; retained snapshots do not override an explicit deletion of the underlying event.
