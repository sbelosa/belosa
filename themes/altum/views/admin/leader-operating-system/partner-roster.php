<?php defined('ALTUMCODE') || die() ?>
<!-- Same LOS data and actions, presented as readable cards at every width. -->
<div class="fp-los-roster">
                    <?php foreach($data->overview['rows'] as $row): ?>
                        <?php $growth_percent = $row['growth_percent']; ?>
                        <?php $growth_class = $growth_percent === null ? '' : ($growth_percent >= 0 ? 'leader-os-growth-positive' : 'leader-os-growth-negative'); ?>
                        <?php
                        $ai_access_panel_class = match((string) ($row['ai_access_tier_key'] ?? 'locked')) {
                            'pro' => 'is-start',
                            'qualified' => 'is-active',
                            'top' => 'is-vip',
                            default => 'is-locked',
                        };
                        $mentor_panel_class = match((string) ($row['ai_mentor_stage_key'] ?? 'foundation')) {
                            'top_momentum', 'serious_focus' => 'is-success',
                            'blocked_setup', 'scattered_focus' => 'is-warning',
                            'starter_cycle', 'building_signal', 'pro_upgrade_ready' => 'is-info',
                            default => 'is-dark',
                        };
                        $ai_signal_total = (int) ($row['ai_access_growth_signal_30d'] ?? 0);
                        $ai_signal_reference = $ai_signal_total >= 15 ? 50 : 15;
                        $ai_signal_progress = min(100, max(0, ($ai_signal_total / max(1, $ai_signal_reference)) * 100));
                        ?>
<article class="fp-los-person">
 <header class="fp-los-person-header"><div class="fp-los-identity">
                                <div class="d-flex flex-column">
                                    <div class="font-weight-bold"><?= $row['name'] ?></div>
                                    <div class="text-muted small"><?= $row['email'] ?></div>
                                    <div class="text-muted small"><?= l('admin_index.biolink_qualified_watch.forever_id') ?>: <?= $row['forever_id'] ?></div>
                                    <!-- Custom code: FC-2026-03-31: LOS overview AI usage badges -->
                                    <div class="leader-os-ai-usage">
                                        <span class="leader-os-status-badge leader-os-ai-usage-main <?= $row['ai_usage_stage_class'] ?>"><?= htmlspecialchars((string) ($row['ai_usage_stage_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="leader-os-status-badge leader-os-anomaly-badge <?= $row['anomaly_stage_class'] ?>"><?= htmlspecialchars((string) ($row['anomaly_stage_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if(!empty($row['fraud_badge_label'])): ?>
                                            <span class="leader-os-status-badge <?= htmlspecialchars((string) ($row['fraud_badge_class'] ?? 'status-dark'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($row['fraud_badge_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif ?>
                                        <?php foreach(($row['ai_usage_badges'] ?? []) as $ai_usage_badge): ?>
                                            <span class="leader-os-status-badge leader-os-ai-usage-badge <?= $ai_usage_badge['class'] ?>"><?= htmlspecialchars((string) ($ai_usage_badge['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endforeach ?>
                                    </div>
                                    <?php if(!empty($row['top_reason_title'])): ?>
                                        <div class="text-muted small mt-2"><?= htmlspecialchars(fcc_t('Fraud signal:'), ENT_QUOTES, 'UTF-8') ?> <strong class="text-white"><?= htmlspecialchars((string) ($row['top_reason_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong> <?= htmlspecialchars(fcc_t('· blocked'), ENT_QUOTES, 'UTF-8') ?> <strong class="text-white"><?= nr((int) ($row['blocked_attempts_total'] ?? 0)) ?></strong></div>
                                    <?php endif ?>
                                    <!-- /Custom code: FC-2026-03-31 -->
                                </div>
                            </div><div class="fp-los-person-status">
                                <div class="mb-1"><span class="leader-os-status-badge status-<?= $row['status_class'] ?>"><?= $row['status_label'] ?></span></div>
                                <?php if($row['qualified']): ?>
                                    <div class="text-muted small"><?= l('admin_leader_operating_system.qualified_hint') ?></div>
                                <?php endif ?>
                            </div></header>
 <dl class="fp-los-metrics"><div><dt><?= htmlspecialchars(fcc_t('LOS rezultat'), ENT_QUOTES, 'UTF-8') ?></dt><dd>
                                <div class="font-weight-bold"><?= $row['leader_os_score'] ?></div>
                                <div class="text-muted small"><?= l('admin_leader_operating_system.table.risk_score') ?>: <?= $row['risk_score'] ?></div>
                            </dd></div>
<div><dt><?= htmlspecialchars(fcc_t('Webshop klikovi'), ENT_QUOTES, 'UTF-8') ?></dt><dd>
                                <div class="font-weight-bold"><?= nr($row['forever_shop_clicks_period']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars(fcc_t('90d:'), ENT_QUOTES, 'UTF-8') ?> <?= nr($row['forever_shop_clicks_90d']) ?></div>
                            </dd></div>
<div><dt><?= htmlspecialchars(fcc_t('Kvaliteta kartice'), ENT_QUOTES, 'UTF-8') ?></dt><dd>
                                <div class="font-weight-bold"><?= nr((int) ($row['app_quality_score'] ?? 0)) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars((string) ($row['app_quality_stage_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> · <?= nr((int) ($row['app_signal_score'] ?? 0)) ?></div>
                            </dd></div>
<div><dt><?= htmlspecialchars(fcc_t('Klikovi prema učlanjenju'), ENT_QUOTES, 'UTF-8') ?></dt><dd>
                                <div class="font-weight-bold"><?= nr($row['forever_registration_clicks_period']) ?></div>
                                <div class="text-muted small"><?= nr($row['registration_rate_percent']) ?>%</div>
                            </dd></div>
</dl><details class="fp-los-person-details"><summary><?= htmlspecialchars(fcc_t('AI, mentor i dodatni pokazatelji'), ENT_QUOTES, 'UTF-8') ?></summary><dl class="fp-los-extra"><div><dt><?= htmlspecialchars(fcc_t('Blokirani pokušaji'), ENT_QUOTES, 'UTF-8') ?></dt><dd>
                                <div class="font-weight-bold"><?= nr((int) ($row['blocked_attempts_total'] ?? 0)) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars((string) (($row['top_reason_title'] ?? '') ?: (($row['fraud_badge_label'] ?? '') ?: fcc_t('Clean'))), ENT_QUOTES, 'UTF-8') ?></div>
                            </dd></div>
<div><dt><?= htmlspecialchars(fcc_t('Rast'), ENT_QUOTES, 'UTF-8') ?></dt><dd>
                                <div class="font-weight-bold <?= $growth_class ?>">
                                    <?= $growth_percent === null ? l('admin_index.biolink_qualified_watch.growth_new') : ($growth_percent > 0 ? '+' : '') . nr($growth_percent) . '%' ?>
                                </div>
                                <div class="text-muted small"><?= l('admin_leader_operating_system.table.delta') ?>: <?= ($row['growth_difference'] > 0 ? '+' : '') . nr($row['growth_difference']) ?></div>
                            </dd></div>
<div><dt><?= htmlspecialchars(fcc_t('Top zemlja'), ENT_QUOTES, 'UTF-8') ?></dt><dd><?= $row['strongest_country'] ?></dd></div>
<div><dt><?= htmlspecialchars(fcc_t('Top izvor'), ENT_QUOTES, 'UTF-8') ?></dt><dd><?= $row['top_source_label'] ?></dd></div>
<div><dt><?= htmlspecialchars(fcc_t('Zadnji klik'), ENT_QUOTES, 'UTF-8') ?></dt><dd><?= $row['last_click_at'] ? \Altum\Date::get($row['last_click_at'], 2) : '-' ?></dd></div>
</dl><div class="fp-los-guidance">                                    <div class="leader-os-ai-access-panel <?= $ai_access_panel_class ?>">
                                        <div class="leader-os-ai-access-meta">
                                            <div class="text-muted small"><?= htmlspecialchars(fcc_t('AI pristup:'), ENT_QUOTES, 'UTF-8') ?> <span class="leader-os-status-badge <?= htmlspecialchars((string) ($row['ai_access_tier_class'] ?? 'status-dark'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($row['ai_access_tier_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></div>
                                            <div class="leader-os-signal-meter">
                                                <div class="leader-os-signal-bar"><span style="width: <?= nr($ai_signal_progress) ?>%"></span></div>
                                                <strong class="text-white"><?= nr((int) ($row['ai_access_growth_signal_30d'] ?? 0)) ?></strong>
                                            </div>
                                            <div class="leader-os-signal-thresholds"><?= htmlspecialchars(fcc_t('15 / 30d = Featured Apps + AI ciklus · 50 / 30d = naslovnica · 15 / 7d = provjera'), ENT_QUOTES, 'UTF-8') ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars(fcc_t('30d: Shop'), ENT_QUOTES, 'UTF-8') ?> <?= nr((int) ($row['ai_access_shop_clicks_30d'] ?? 0)) ?> <?= htmlspecialchars(fcc_t('· Funnel'), ENT_QUOTES, 'UTF-8') ?> <?= nr((int) ($row['ai_access_funnel_registrations_30d'] ?? 0)) ?> <?= htmlspecialchars(fcc_t('· AI chat'), ENT_QUOTES, 'UTF-8') ?> <?= nr((int) ($row['ai_access_ai_chat_leads_30d'] ?? 0)) ?> <?= htmlspecialchars(fcc_t('· WhatsApp'), ENT_QUOTES, 'UTF-8') ?> <?= nr((int) ($row['ai_access_whatsapp_contacts_30d'] ?? 0)) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars(fcc_t('7d provjera:'), ENT_QUOTES, 'UTF-8') ?> <?= nr((int) ($row['ai_access_growth_signal_7d'] ?? 0)) ?> <?= htmlspecialchars(fcc_t('· Coach:'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) ($row['ai_access_coach_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) ($row['ai_access_source_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php if(!empty($row['ai_access_is_pro']) && !empty($row['ai_access_intro_cycle_available'])): ?>
                                                <div class="text-muted small"><?= htmlspecialchars(fcc_t('Početni PRO unlock: analiza'), ENT_QUOTES, 'UTF-8') ?> <?= !empty($row['ai_access_starter_app_review_available']) ? 'spremna' : fcc_t('iskorištena') ?> <?= htmlspecialchars(fcc_t('· plan'), ENT_QUOTES, 'UTF-8') ?> <?= !empty($row['ai_access_starter_weekly_plan_available']) ? 'spreman' : fcc_t('iskorišten') ?></div>
                                            <?php endif ?>
                                            <?php if(!empty($row['ai_access_manual_expires_at'])): ?>
                                                <div class="text-muted small"><?= htmlspecialchars(fcc_t('Ručni status traje do:'), ENT_QUOTES, 'UTF-8') ?> <?= \Altum\Date::get($row['ai_access_manual_expires_at'], 2) ?></div>
                                            <?php endif ?>
                                        </div>
                                    </div>
                                    <div class="leader-os-mentor-panel <?= $mentor_panel_class ?>">
                                        <div class="leader-os-mentor-meta">
                                            <div class="leader-os-mentor-badges">
                                                <span class="leader-os-status-badge <?= htmlspecialchars((string) ($row['ai_mentor_stage_class'] ?? 'status-dark'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($row['ai_mentor_stage_label'] ?? 'Mentor signal'), ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php if(!empty($row['ai_mentor_priority_label'])): ?>
                                                    <span class="leader-os-status-badge status-info"><?= htmlspecialchars((string) ($row['ai_mentor_priority_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php endif ?>
                                            </div>
                                            <div class="text-muted small"><?= htmlspecialchars(fcc_t('Sales link:'), ENT_QUOTES, 'UTF-8') ?> <strong class="text-white"><?= htmlspecialchars((string) ($row['ai_mentor_sales_link_status_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong> <?= htmlspecialchars(fcc_t('· Portfelj:'), ENT_QUOTES, 'UTF-8') ?> <strong class="text-white"><?= htmlspecialchars((string) ($row['ai_mentor_portfolio_health_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                                            <div class="text-muted small"><?= htmlspecialchars(fcc_t('Fokus aplikacije:'), ENT_QUOTES, 'UTF-8') ?> <strong class="text-white"><?= nr((int) ($row['ai_mentor_focus_app_count'] ?? 0)) ?></strong><?php if(!empty($row['ai_mentor_focus_app_names'])): ?> · <?= htmlspecialchars((string) ($row['ai_mentor_focus_app_names'] ?? ''), ENT_QUOTES, 'UTF-8') ?><?php endif ?><?php if((int) ($row['ai_mentor_archive_candidate_count'] ?? 0) > 0): ?> <?= htmlspecialchars(fcc_t('· Cleanup:'), ENT_QUOTES, 'UTF-8') ?> <strong class="text-white"><?= nr((int) ($row['ai_mentor_archive_candidate_count'] ?? 0)) ?></strong><?php endif ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars(fcc_t('Mentor potez:'), ENT_QUOTES, 'UTF-8') ?> <strong class="text-white"><?= htmlspecialchars((string) ($row['ai_mentor_admin_action'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                                        </div>
                                    </div>
</div></details><footer class="fp-los-person-actions">
                                <div class="d-flex flex-column">
                                    <a href="<?= $row['detail_url'] ?>" class="leader-os-link"><?= l('admin_leader_operating_system.open_detail_demo') ?></a>
                                    <a href="<?= $row['admin_user_url'] ?>" class="leader-os-link text-muted"><?= l('admin_index.biolink_qualified_watch.open_profile') ?></a>
                                    <?php if(!empty($row['ai_access_is_pro'])): ?>
                                        <div class="leader-os-ai-access-actions">
                                            <?php if(($row['ai_access_tier_key'] ?? '') !== 'qualified' && ($row['ai_access_tier_key'] ?? '') !== 'top'): ?>
                                                <form action="<?= $leader_os_action_url ?>" method="post">
                                                    <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                                                    <input type="hidden" name="user_id" value="<?= (int) $row['user_id'] ?>" />
                                                    <input type="hidden" name="los_ai_unlock_action" value="qualified" />
                                                    <button type="submit" class="btn btn-sm leader-os-action-button is-primary">15+</button>
                                                </form>
                                            <?php endif ?>

                                            <?php if(($row['ai_access_tier_key'] ?? '') !== 'top'): ?>
                                                <form action="<?= $leader_os_action_url ?>" method="post">
                                                    <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                                                    <input type="hidden" name="user_id" value="<?= (int) $row['user_id'] ?>" />
                                                    <input type="hidden" name="los_ai_unlock_action" value="top" />
                                                    <button type="submit" class="btn btn-sm leader-os-action-button is-success">50+</button>
                                                </form>
                                            <?php endif ?>

                                            <?php if(!empty($row['ai_access_manual_tier'])): ?>
                                                <form action="<?= $leader_os_action_url ?>" method="post">
                                                    <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                                                    <input type="hidden" name="user_id" value="<?= (int) $row['user_id'] ?>" />
                                                    <input type="hidden" name="los_ai_unlock_action" value="auto" />
                                                    <button type="submit" class="btn btn-sm leader-os-action-button"><?= htmlspecialchars(fcc_t('Auto'), ENT_QUOTES, 'UTF-8') ?></button>
                                                </form>
                                            <?php endif ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-muted small mt-2"><?= htmlspecialchars(fcc_t('Ručni AI unlock dostupan je samo za aktivni PRO paket.'), ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif ?>
                                </div>
                            </footer></article>
<?php endforeach ?>
</div>
