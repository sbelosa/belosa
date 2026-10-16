/* Custom code: FC-2026-10-16: widen settings before publishing the 50000 character HTML limit. */
/* Apply only after a verified private table backup and schema identity check. */
/* MariaDB 11.2 or later, InnoDB, existing utf8mb4_general_ci nullable settings. */
ALTER TABLE biolinks_blocks WAIT 5
    MODIFY COLUMN settings MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
    ALGORITHM=COPY, LOCK=SHARED;
/* On application rollback retain MEDIUMTEXT to preserve newly saved larger blocks. */
/* Do not shrink to TEXT unless all current rows fit and a fresh backup is verified. */
/* /Custom code: FC-2026-10-16 */
