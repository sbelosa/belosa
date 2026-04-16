<div class="card ai-plan-card ai-plan-tool-card ai-plan-app-review-card" id="ai-plan-app-review">
    <div class="card-body">
        <div class="ai-plan-tool-header">
            <div class="ai-plan-tool-heading">
                <h2 class="h5 mb-1"><?= l('ai_plan.optional_tool_title') ?></h2>
                <p class="text-muted mb-0"><?= l('ai_plan.optional_tool_text') ?></p>
            </div>
            <div class="ai-plan-tool-meta">
                <?php if($data->latest_app_review): ?>
                    <div class="small text-muted"><?= l('ai_plan.plan_generated_at') ?>: <?= \Altum\Date::get($data->latest_app_review['generated_at'], 2) ?></div>
                <?php endif ?>
            </div>
        </div>

        <div class="ai-plan-inline-meta">
            <span class="ai-plan-chip active"><?= l($data->app_review_access_payload['plan_label_key']) ?></span>
            <span class="ai-plan-chip <?= $data->app_review_access_payload['can_select_any_app'] ? 'active' : '' ?>"><?= $data->app_review_access_payload['can_select_any_app'] ? l('ai_plan.app_review_scope_multiple') : l('ai_plan.app_review_scope_main') ?></span>
            <span class="ai-plan-chip">
                <?php if(!empty($data->app_review_access_payload['is_admin_testing'])): ?>
                    <?= l('ai_plan.app_review_frequency_unlimited') ?>
                <?php elseif(!empty($data->app_review_access_payload['uses_starter_credit'])): ?>
                    <?= l('ai_plan.app_review_frequency_note_intro') ?>
                <?php elseif(!$data->app_review_access_payload['can_generate']): ?>
                    <?= l('ai_plan.app_review_frequency_note_locked') ?>
                <?php elseif((int) ($data->app_review_access_payload['cooldown_days'] ?? 7) <= 1): ?>
                    <?= l('ai_plan.app_review_frequency_note_daily') ?>
                <?php else: ?>
                    <?= l('ai_plan.app_review_frequency_note_weekly') ?>
                <?php endif ?>
            </span>
        </div>

        <div class="ai-plan-review-grid">
            <div class="ai-plan-review-box ai-plan-app-review-form-box">
                <?php if(!$data->is_profile_complete): ?>
                    <div class="ai-plan-lock-box">
                        <div class="font-weight-bold mb-2"><?= l('ai_plan.app_review_locked_profile_title') ?></div>
                        <div class="text-muted small mb-0"><?= l('ai_plan.app_review_locked_profile') ?></div>
                    </div>
                <?php else: ?>
                    <?php if(!empty($data->app_review_access_payload['can_select_any_app']) && count((array) ($data->app_review_available_apps ?? [])) > 1): ?>
                        <form action="<?= $ai_plan_section_urls['app_review'] ?>" method="get" class="mb-3">
                            <input type="hidden" name="section" value="app_review" />
                            <div class="form-group mb-0">
                                <label for="app_review_selected_link_id" class="font-weight-bold"><?= l('ai_plan.app_review_select_app') ?></label>
                                <select id="app_review_selected_link_id" name="app_review_selected_link_id" class="custom-select" onchange="this.form.submit()">
                                    <option value=""><?= l('global.choose') ?></option>
                                    <?php foreach(($data->app_review_available_apps ?? []) as $app_option): ?>
                                        <option
                                            value="<?= (int) $app_option['link_id'] ?>"
                                            <?= (int) ($data->app_review_selected_link_id ?? 0) === (int) $app_option['link_id'] ? 'selected="selected"' : null ?>
                                        ><?= htmlspecialchars((string) $app_option['label'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach ?>
                                </select>
                                <div class="small text-muted mt-2"><?= l('ai_plan.app_review_select_app_help') ?></div>
                            </div>
                        </form>
                    <?php endif ?>

                    <form action="<?= $ai_plan_section_urls['app_review'] ?>" method="post" role="form" id="ai-plan-app-review-form">
                        <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
                        <input type="hidden" name="app_review_return_section" value="app_review" />
                        <input type="hidden" name="generate_app_review" value="1" />
                        <input type="hidden" name="app_review_selected_link_id" value="<?= (int) ($data->app_review_selected_link_id ?? 0) ?>" />

                        <?php if(!empty($data->has_admin_testing_access)): ?>
                            <div class="ai-plan-soft-box mb-3">
                                <div class="font-weight-bold mb-1"><?= l('ai_plan.app_review_admin_testing_title') ?></div>
                                <div class="text-muted small mb-0"><?= l('ai_plan.app_review_admin_testing_text') ?></div>
                            </div>
                        <?php endif ?>

                        <?php if($data->app_review_is_locked): ?>
                            <div class="ai-plan-lock-box ai-plan-app-review-lock mb-3">
                                <div class="font-weight-bold mb-2"><?= l('ai_plan.app_review_locked_cooldown_title') ?></div>
                                <div class="text-muted small mb-0"><?= sprintf(l('ai_plan.app_review_locked_cooldown_short'), $data->app_review_countdown_days ?? 0) ?></div>
                            </div>
                        <?php else: ?>
                            <div class="small text-muted mb-3"><?= !empty($data->app_review_access_payload['uses_starter_credit']) ? l('ai_plan.app_review_helper_intro') : l('ai_plan.app_review_helper') ?></div>
                        <?php endif ?>

                        <?php if(empty($data->app_review_access_payload['can_select_any_app']) || count((array) ($data->app_review_available_apps ?? [])) <= 1): ?>
                            <?php if(!empty($data->app_review_selected_app)): ?>
                            <div class="ai-plan-soft-box ai-plan-app-review-main-app mb-3">
                                <div class="small text-muted mb-1"><?= l('ai_plan.app_review_main_app') ?></div>
                                <div class="font-weight-bold"><?= htmlspecialchars((string) (($data->app_review_selected_app['name'] ?? '') ?: ($data->app_review_selected_app['url'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if(!empty($data->app_review_selected_app['url'])): ?>
                                    <div class="small text-muted mt-1"><?= htmlspecialchars((string) $data->app_review_selected_app['url'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif ?>
                                <div class="small text-muted mt-2"><?= l('ai_plan.app_review_main_app_phase_1') ?></div>
                            </div>
                            <?php endif ?>
                        <?php endif ?>

                        <div class="form-group mb-0">
                            <label for="app_review_context" class="font-weight-bold"><?= l('ai_plan.app_review_context') ?></label>
                            <textarea id="app_review_context" name="app_review_context" class="form-control" rows="4" maxlength="1600" placeholder="<?= l('ai_plan.app_review_context_placeholder') ?>"><?= htmlspecialchars((string) ($data->app_review_context ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>

                        <div class="ai-plan-processing-box" id="ai-plan-app-review-processing" style="display:none;">
                            <div class="font-weight-bold mb-2"><?= l('ai_plan.app_review_processing_title') ?></div>
                            <div class="text-muted small mb-0" id="ai-plan-app-review-processing-text"><?= l('ai_plan.app_review_processing_message') ?></div>
                            <div class="ai-plan-processing-steps">
                                <div class="ai-plan-processing-step"><?= l('ai_plan.app_review_processing_step_1') ?></div>
                                <div class="ai-plan-processing-step"><?= l('ai_plan.app_review_processing_step_2') ?></div>
                                <div class="ai-plan-processing-step"><?= l('ai_plan.app_review_processing_step_3') ?></div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex flex-wrap align-items-center" style="gap:.75rem;">
                            <button type="submit" class="btn btn-primary" id="ai-plan-app-review-submit" <?= $data->app_review_is_locked ? 'disabled="disabled"' : null ?>><i class="fas fa-search fa-sm mr-1"></i> <?= !empty($data->app_review_history) ? l('ai_plan.app_review_evolution') : l('ai_plan.app_review_generate') ?></button>
                            <span class="text-muted small" id="ai-plan-app-review-help"><?= $data->app_review_is_locked ? sprintf(l('ai_plan.app_review_locked_cooldown_short'), $data->app_review_countdown_days ?? 0) : l('ai_plan.app_review_footer') ?></span>
                        </div>
                    </form>
                <?php endif ?>
            </div>

            <div class="ai-plan-preview-card">
                <div class="ai-plan-preview-header">
                    <div>
                        <div class="small text-muted mb-1"><?= l('ai_plan.app_review_preview_title') ?></div>
                        <div class="font-weight-bold" id="ai-plan-preview-title"><?= htmlspecialchars($app_review_preview_label ?: '-', ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="ai-plan-preview-meta mt-1" id="ai-plan-preview-url"><?= htmlspecialchars((string) (($app_review_selected_app['url'] ?? '') ?: ($data->latest_app_review['selected_app_url'] ?? '')), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <a id="ai-plan-preview-open" href="<?= htmlspecialchars($app_review_preview_url ?: '#', ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm <?= $app_review_preview_url ? null : 'disabled' ?>"><?= l('ai_plan.app_review_preview_open') ?></a>
                </div>

                <div class="ai-plan-preview-frame-wrap" id="ai-plan-preview-frame-wrap" <?= $app_review_preview_url ? null : 'style="display:none;"' ?>>
                    <iframe id="ai-plan-preview-frame" class="ai-plan-preview-frame" <?= $app_review_preview_url ? 'src="' . htmlspecialchars($app_review_preview_url, ENT_QUOTES, 'UTF-8') . '"' : null ?> loading="lazy" title="<?= htmlspecialchars($app_review_preview_label ?: 'App preview', ENT_QUOTES, 'UTF-8') ?>"></iframe>
                </div>

                <div class="ai-plan-preview-empty" id="ai-plan-preview-empty" <?= $app_review_preview_url ? 'style="display:none;"' : null ?>>
                    <div>
                        <div class="font-weight-bold mb-2"><?= l('ai_plan.app_review_preview_empty_title') ?></div>
                        <div class="text-muted small mb-0"><?= l('ai_plan.app_review_preview_empty_text') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="ai-plan-history-strip mt-3">
            <div class="ai-plan-history-strip-head">
                <div>
                    <div class="font-weight-bold mb-1"><?= l('ai_plan.app_review_history_title') ?></div>
                    <div class="small text-muted"><?= l('ai_plan.app_review_history_text') ?></div>
                </div>
            </div>

            <?php if(!empty($data->app_review_history)): ?>
                <div class="ai-plan-history-list">
                    <?php foreach(array_slice((array) ($data->app_review_history ?? []), 0, 5) as $history_review): ?>
                        <?php
                        $history_generated_at = (string) ($history_review['generated_at'] ?? '');
                        $history_is_active = $history_generated_at !== '' && $history_generated_at === (string) ($data->app_review_active_generated_at ?? '');
                        $history_link = $history_is_active
                            ? $ai_plan_section_urls['app_review'] . ($data->app_review_selected_link_id ? '&app_review_selected_link_id=' . (int) ($data->app_review_selected_link_id ?? 0) : '')
                            : $ai_plan_section_urls['app_review'] . '&app_review_selected_link_id=' . (int) ($data->app_review_selected_link_id ?? 0) . '&app_review_generated_at=' . urlencode($history_generated_at);
                        ?>
                        <div class="ai-plan-history-entry <?= $history_is_active ? 'active' : '' ?>">
                            <div class="ai-plan-history-card <?= $history_is_active ? 'active' : '' ?>">
                                <div class="ai-plan-history-copy">
                                    <div class="ai-plan-history-title"><?= htmlspecialchars((string) (($history_review['headline'] ?? '') ?: ($history_review['top_recommendation'] ?? l('ai_plan.app_review_page_title'))), ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="ai-plan-history-meta"><?= l('ai_plan.plan_generated_at') ?>: <?= !empty($history_review['generated_at']) ? \Altum\Date::get($history_review['generated_at'], 2) : '-' ?></div>
                                    <div class="ai-plan-history-note"><?= htmlspecialchars((string) (($history_review['summary'] ?? '') ?: ($history_review['top_recommendation'] ?? '')), ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                                <div class="ai-plan-history-side">
                                    <a href="<?= htmlspecialchars($history_link, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary ai-plan-history-action"><?= $history_is_active ? l('ai_plan.app_review_history_close') : l('ai_plan.app_review_history_open') ?></a>
                                </div>
                            </div>
                            <?php if($history_is_active && $data->latest_app_review): ?>
                                <div class="ai-plan-history-expanded">
                                    <?= $render_app_review_result_cards($data->latest_app_review, $data->app_review_quality_payload ?? []) ?>
                                </div>
                            <?php endif ?>
                        </div>
                    <?php endforeach ?>
                </div>
            <?php else: ?>
                <div class="ai-plan-history-empty small"><?= l('ai_plan.app_review_history_empty') ?></div>
            <?php endif ?>
        </div>

        <?php if(!$data->latest_app_review): ?>
            <div class="ai-plan-history-empty mt-3">
                <div class="font-weight-bold mb-1"><?= l('ai_plan.app_review_result_empty_title') ?></div>
                <div class="small mb-0"><?= l('ai_plan.app_review_result_empty_text') ?></div>
            </div>
        <?php endif ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const appReviewForm = document.getElementById('ai-plan-app-review-form');
        const appReviewProcessingBox = document.getElementById('ai-plan-app-review-processing');
        const appReviewSubmit = document.getElementById('ai-plan-app-review-submit');
        const appReviewHelp = document.getElementById('ai-plan-app-review-help');

        if(appReviewForm && appReviewProcessingBox && appReviewSubmit) {
            appReviewForm.addEventListener('submit', function() {
                appReviewProcessingBox.style.display = '';
                appReviewProcessingBox.classList.add('is-visible');
                appReviewSubmit.setAttribute('disabled', 'disabled');
                if(appReviewHelp) {
                    appReviewHelp.textContent = <?= json_encode(l('ai_plan.app_review_processing_message')) ?>;
                }
                appReviewSubmit.innerHTML = '<i class="fas fa-spinner fa-spin fa-sm mr-1"></i> ' + <?= json_encode(l('ai_plan.app_review_processing_title')) ?>;
            });
        }

        document.querySelectorAll('.ai-plan-review-disclosure-stack[data-accordion-group]').forEach(function(stack) {
            const items = Array.from(stack.querySelectorAll('details[data-accordion-item]'));
            const initiallyOpen = items.find(function(item) { return item.hasAttribute('open'); }) || items[0] || null;

            items.forEach(function(item) {
                item.open = initiallyOpen === item;

                item.addEventListener('toggle', function() {
                    if(!item.open) {
                        return;
                    }

                    items.forEach(function(other) {
                        if(other !== item) {
                            other.open = false;
                        }
                    });
                });

                const summary = item.querySelector('summary');
                if(!summary) {
                    return;
                }

                summary.addEventListener('click', function(event) {
                    event.preventDefault();

                    const willOpen = !item.open;

                    items.forEach(function(other) {
                        other.open = false;
                    });

                    item.open = willOpen;
                });
            });
        });
    });
</script>
