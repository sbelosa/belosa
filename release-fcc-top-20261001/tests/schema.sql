CREATE TABLE IF NOT EXISTS fcc_top_profiles (
 user_id INT UNSIGNED PRIMARY KEY,
 visible TINYINT UNSIGNED NOT NULL DEFAULT 0,
 weekly_notice TINYINT UNSIGNED NOT NULL DEFAULT 0,
 milestone_notice TINYINT UNSIGNED NOT NULL DEFAULT 0,
 recommendations_goal SMALLINT UNSIGNED NOT NULL DEFAULT 3,
 invitations_goal SMALLINT UNSIGNED NOT NULL DEFAULT 5,
 education_goal SMALLINT UNSIGNED NOT NULL DEFAULT 3,
 version INT UNSIGNED NOT NULL DEFAULT 0,
 updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS fcc_top_positions (
 day DATE NOT NULL,
 category VARCHAR(24) NOT NULL,
 period_key VARCHAR(10) NOT NULL,
 entity_key CHAR(64) CHARACTER SET ascii NOT NULL,
 position INT UNSIGNED NOT NULL,
 PRIMARY KEY(day,category,period_key,entity_key),
 KEY prior_rank(category,period_key,entity_key,day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS fcc_top_state (
 user_id INT UNSIGNED PRIMARY KEY,
 checked_day DATE NOT NULL,
 month_key DATE NOT NULL,
 ranks_json TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
