<?php defined('ALTUMCODE') || die() ?>

<?php
$feedback_statuses = [
    'open' => l('feedback_tickets.status.open'),
    'answered' => l('feedback_tickets.status.answered'),
    'closed' => l('feedback_tickets.status.closed'),
];

$feedback_categories = [
    'change' => l('feedback_tickets.category.change'),
    'add' => l('feedback_tickets.category.add'),
    'bug' => l('feedback_tickets.category.bug'),
    'other' => l('feedback_tickets.category.other'),
];

$ticket_has_unread_admin_update = static function($ticket): bool {
    $admin_last_replied_at = (string) ($ticket->admin_last_replied_at ?? '');
    $user_last_read_at = (string) ($ticket->user_last_read_at ?? '');
    $status = (string) ($ticket->status ?? 'open');

    if($admin_last_replied_at === '' || !in_array($status, ['answered', 'closed'], true)) {
        return false;
    }

    return $user_last_read_at === '' || $user_last_read_at < $admin_last_replied_at;
};

$support_totals = [
    'all' => count($data->feedback_tickets),
    'open' => 0,
    'answered' => 0,
    'closed' => 0,
    'unread' => 0,
    'webinar' => 0,
];

foreach($data->feedback_tickets as $ticket) {
    $status = (string) ($ticket->status ?? 'open');

    if(isset($support_totals[$status])) {
        $support_totals[$status]++;
    }

    if($ticket_has_unread_admin_update($ticket)) {
        $support_totals['unread']++;
    }

    if(!empty($ticket->is_webinar_topic_suggestion)) {
        $support_totals['webinar']++;
    }
}
?>

<style>
    .fcc-support-shell {
        color: #eef6ff;
    }

    .fcc-support-shell .text-muted {
        color: rgba(191, 211, 238, 0.72) !important;
    }

    .fcc-support-card,
    .fcc-support-kpi,
    .fcc-support-ticket {
        border: 1px solid rgba(148, 163, 184, 0.14);
        border-radius: 1.15rem;
        background:
            radial-gradient(circle at top right, rgba(45, 212, 191, 0.08), transparent 28%),
            linear-gradient(180deg, rgba(17, 24, 39, 0.96) 0%, rgba(10, 15, 28, 0.98) 100%);
        box-shadow: 0 1.2rem 2.5rem rgba(2, 6, 23, 0.24);
    }

    .fcc-support-hero {
        padding: 1.45rem;
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(280px, 0.65fr);
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .fcc-support-hero-title {
        font-size: clamp(2rem, 3vw, 2.7rem);
        line-height: 1.05;
        margin-bottom: 0.85rem;
        color: #f8fbff;
        font-weight: 800;
        letter-spacing: -0.04em;
    }

    .fcc-support-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.45rem 0.8rem;
        border-radius: 999px;
        border: 1px solid rgba(94, 234, 212, 0.2);
        background: rgba(15, 118, 110, 0.22);
        color: #d8fff8;
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .fcc-support-hero-copy {
        color: rgba(226, 232, 240, 0.86);
        max-width: 700px;
        font-size: 1rem;
        line-height: 1.65;
    }

    .fcc-support-hero-aside {
        padding: 1.15rem;
        border-radius: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.12);
        background: rgba(15, 23, 42, 0.66);
    }

    .fcc-support-hero-aside-row {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.7rem 0;
        border-bottom: 1px solid rgba(148, 163, 184, 0.12);
    }

    .fcc-support-hero-aside-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .fcc-support-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.9rem;
        margin-bottom: 1rem;
    }

    .fcc-support-kpi {
        padding: 1rem 1.05rem;
    }

    .fcc-support-kpi-label {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: rgba(191, 211, 238, 0.72);
        margin-bottom: 0.6rem;
    }

    .fcc-support-kpi-value {
        font-size: 2rem;
        line-height: 1;
        font-weight: 800;
        color: #f8fbff;
        letter-spacing: -0.04em;
        margin-bottom: 0.45rem;
    }

    .fcc-support-kpi-note {
        color: rgba(191, 211, 238, 0.66);
        font-size: 0.84rem;
        line-height: 1.45;
    }

    .fcc-support-main-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(340px, 0.75fr);
        gap: 1rem;
        align-items: start;
    }

    .fcc-support-card {
        padding: 1.35rem;
    }

    .fcc-support-section-title {
        font-size: 1.45rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: #f8fbff;
        margin-bottom: 0.45rem;
    }

    .fcc-support-eyebrow {
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-size: 0.78rem;
        color: rgba(191, 211, 238, 0.72);
        margin-bottom: 0.5rem;
    }

    .fcc-support-input,
    .fcc-support-select,
    .fcc-support-textarea {
        width: 100%;
        border-radius: 0.95rem;
        border: 1px solid rgba(148, 163, 184, 0.16);
        background: rgba(8, 14, 26, 0.9);
        color: #f8fbff;
        padding: 0.9rem 1rem;
    }

    .fcc-support-textarea {
        min-height: 180px;
        resize: vertical;
    }

    .fcc-support-input:focus,
    .fcc-support-select:focus,
    .fcc-support-textarea:focus {
        outline: none;
        border-color: rgba(45, 212, 191, 0.45);
        box-shadow: 0 0 0 0.2rem rgba(45, 212, 191, 0.12);
    }

    .fcc-support-field {
        margin-bottom: 1rem;
    }

    .fcc-support-label {
        display: block;
        margin-bottom: 0.45rem;
        font-size: 0.84rem;
        font-weight: 700;
        color: rgba(226, 232, 240, 0.92);
    }

    .fcc-support-helper {
        color: rgba(148, 163, 184, 0.82);
        font-size: 0.78rem;
        margin-top: 0.4rem;
        line-height: 1.45;
    }

    .fcc-support-webinar-box {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 0.85rem;
        align-items: start;
        border: 1px solid rgba(45, 212, 191, 0.18);
        border-radius: 1rem;
        background: linear-gradient(135deg, rgba(15, 118, 110, 0.18) 0%, rgba(15, 23, 42, 0.2) 100%);
        padding: 0.95rem 1rem;
        margin-bottom: 1rem;
    }

    .fcc-support-checkbox {
        margin-top: 0.22rem;
        width: 18px;
        height: 18px;
    }

    .fcc-support-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        border: 0;
        border-radius: 1rem;
        background: linear-gradient(135deg, #2dd4bf 0%, #22c55e 100%);
        color: #07131f;
        font-weight: 700;
        padding: 0.95rem 1.15rem;
        box-shadow: 0 1rem 2rem rgba(45, 212, 191, 0.18);
    }

    .fcc-support-button:hover {
        color: #07131f;
        text-decoration: none;
        transform: translateY(-1px);
    }

    .fcc-support-ticket-list {
        display: grid;
        gap: 0.85rem;
        max-height: 980px;
        overflow: auto;
        padding-right: 0.2rem;
    }

    .fcc-support-ticket {
        padding: 1rem 1.05rem;
    }

    .fcc-support-ticket.is-active {
        border-color: rgba(45, 212, 191, 0.32);
        box-shadow: inset 0 0 0 1px rgba(45, 212, 191, 0.12);
        background:
            radial-gradient(circle at top right, rgba(45, 212, 191, 0.12), transparent 28%),
            linear-gradient(180deg, rgba(19, 33, 49, 0.98) 0%, rgba(11, 18, 32, 1) 100%);
    }

    .fcc-support-ticket-top {
        display: flex;
        justify-content: space-between;
        gap: 0.85rem;
        align-items: flex-start;
        margin-bottom: 0.5rem;
    }

    .fcc-support-ticket-title {
        color: #f8fbff;
        font-weight: 700;
        text-decoration: none;
    }

    .fcc-support-ticket-title:hover {
        color: #9ceef2;
        text-decoration: none;
    }

    .fcc-support-badges {
        display: flex;
        gap: 0.45rem;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .fcc-support-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.34rem 0.62rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        border: 1px solid rgba(148, 163, 184, 0.16);
        background: rgba(15, 23, 42, 0.82);
        color: #eaf3ff;
    }

    .fcc-support-badge.is-unread {
        background: rgba(220, 38, 38, 0.16);
        border-color: rgba(248, 113, 113, 0.28);
        color: #fecaca;
    }

    .fcc-support-badge.is-webinar {
        background: rgba(8, 145, 178, 0.18);
        border-color: rgba(34, 211, 238, 0.24);
        color: #cffafe;
    }

    .fcc-support-badge.is-open {
        background: rgba(30, 64, 175, 0.22);
        border-color: rgba(96, 165, 250, 0.24);
    }

    .fcc-support-badge.is-answered {
        background: rgba(22, 101, 52, 0.2);
        border-color: rgba(74, 222, 128, 0.24);
    }

    .fcc-support-badge.is-closed {
        background: rgba(71, 85, 105, 0.28);
        border-color: rgba(148, 163, 184, 0.22);
    }

    .fcc-support-ticket-meta {
        color: rgba(191, 211, 238, 0.72);
        font-size: 0.82rem;
        margin-bottom: 0.45rem;
    }

    .fcc-support-ticket-preview {
        color: rgba(226, 232, 240, 0.84);
        line-height: 1.55;
        font-size: 0.92rem;
    }

    .fcc-support-notification-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.9rem;
    }

    .fcc-support-notification {
        border-radius: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.12);
        background: rgba(8, 14, 26, 0.74);
        padding: 1rem;
    }

    .fcc-support-thread-header {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: flex-start;
        margin-bottom: 1rem;
    }

    .fcc-support-thread-card {
        min-height: 100%;
        background:
            radial-gradient(circle at top right, rgba(45, 212, 191, 0.08), transparent 24%),
            linear-gradient(180deg, rgba(18, 26, 42, 0.96) 0%, rgba(10, 15, 28, 0.99) 100%);
    }

    .fcc-support-sidebar-stack {
        display: grid;
        gap: 1rem;
    }

    .fcc-support-compose-card {
        position: relative;
        overflow: hidden;
        background:
            radial-gradient(circle at 12% 14%, rgba(63, 215, 199, 0.12) 0%, rgba(63, 215, 199, 0) 34%),
            radial-gradient(circle at 88% 10%, rgba(84, 124, 255, 0.12) 0%, rgba(84, 124, 255, 0) 30%),
            radial-gradient(circle at 72% 0%, rgba(226, 188, 116, 0.08) 0%, rgba(226, 188, 116, 0) 22%),
            linear-gradient(180deg, rgba(18, 31, 49, 0.98) 0%, rgba(10, 17, 29, 0.995) 100%);
        border-color: rgba(93, 167, 255, 0.18);
        box-shadow: 0 1.45rem 3rem rgba(2, 8, 23, 0.32), inset 0 1px 0 rgba(255, 255, 255, 0.04);
    }

    .fcc-support-compose-card::before {
        content: '';
        position: absolute;
        inset: 0 0 auto;
        height: 3px;
        background: linear-gradient(90deg, rgba(92, 239, 223, 0.96) 0%, rgba(103, 160, 255, 0.78) 54%, rgba(228, 188, 118, 0.88) 100%);
        opacity: 0.96;
    }

    .fcc-support-compose-card::after {
        content: '';
        position: absolute;
        inset: auto auto -4rem -3rem;
        width: 15rem;
        height: 15rem;
        border-radius: 999px;
        background: radial-gradient(circle, rgba(63, 215, 199, 0.1) 0%, rgba(63, 215, 199, 0) 72%);
        pointer-events: none;
    }

    .fcc-support-compose-card .fcc-support-eyebrow,
    .fcc-support-compose-card .fcc-support-section-title,
    .fcc-support-compose-card .text-muted,
    .fcc-support-compose-card form {
        position: relative;
        z-index: 1;
    }

    .fcc-support-compose-card .fcc-support-section-title {
        margin-bottom: 0.55rem;
    }

    .fcc-support-compose-card .fcc-support-webinar-box {
        border-color: rgba(92, 239, 223, 0.22);
        background:
            radial-gradient(circle at top right, rgba(84, 124, 255, 0.1) 0%, rgba(84, 124, 255, 0) 40%),
            linear-gradient(135deg, rgba(17, 72, 89, 0.28) 0%, rgba(15, 23, 42, 0.32) 100%);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
    }

    .fcc-support-compose-card .fcc-support-button {
        min-width: 11rem;
        box-shadow: 0 1.1rem 2.3rem rgba(45, 212, 191, 0.22);
    }

    .fcc-support-compose-card .fcc-support-button:hover {
        box-shadow: 0 1.35rem 2.7rem rgba(45, 212, 191, 0.28);
    }

    .fcc-support-inbox-card {
        background:
            radial-gradient(circle at top right, rgba(96, 165, 250, 0.08), transparent 26%),
            linear-gradient(180deg, rgba(17, 25, 41, 0.96) 0%, rgba(10, 15, 28, 0.99) 100%);
    }

    .fcc-support-empty-state {
        min-height: 640px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 2rem;
        border: 1px dashed rgba(148, 163, 184, 0.18);
        border-radius: 1.1rem;
        background: rgba(8, 14, 26, 0.32);
    }

    .fcc-support-empty-state-title {
        font-size: 1.5rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: #f8fbff;
        margin-bottom: 0.65rem;
    }

    .fcc-support-thread-messages {
        display: grid;
        gap: 0.9rem;
        margin-bottom: 1rem;
    }

    .fcc-support-thread-message {
        padding: 1rem 1.05rem;
        border-radius: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.12);
        background: rgba(8, 14, 26, 0.76);
    }

    .fcc-support-thread-message.is-admin {
        background:
            radial-gradient(circle at top right, rgba(45, 212, 191, 0.08), transparent 28%),
            linear-gradient(180deg, rgba(14, 41, 53, 0.95) 0%, rgba(10, 15, 28, 0.98) 100%);
        border-color: rgba(45, 212, 191, 0.18);
    }

    .fcc-support-thread-message-top {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        align-items: flex-start;
        margin-bottom: 0.5rem;
    }

    .fcc-support-thread-message-copy {
        color: rgba(241, 245, 249, 0.94);
        line-height: 1.7;
        white-space: pre-wrap;
    }

    .fcc-support-thread-actions {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        align-items: center;
        margin-bottom: 1rem;
    }

    .fcc-support-inline-link {
        color: #9ceef2;
        font-weight: 700;
        text-decoration: none;
    }

    .fcc-support-inline-link:hover {
        color: #d8fff8;
        text-decoration: none;
    }

    .fcc-support-reply-box {
        border-top: 1px solid rgba(148, 163, 184, 0.12);
        padding-top: 1rem;
    }

    .fcc-support-empty-thread {
        min-height: 420px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
    }

    @media (max-width: 1199.98px) {
        .fcc-support-hero,
        .fcc-support-main-layout {
            grid-template-columns: 1fr;
        }

        .fcc-support-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .fcc-support-grid,
        .fcc-support-notification-grid {
            grid-template-columns: 1fr;
        }

        .fcc-support-ticket-top,
        .fcc-support-hero-aside-row,
        .fcc-support-thread-header,
        .fcc-support-thread-message-top,
        .fcc-support-thread-actions {
            flex-direction: column;
            align-items: flex-start;
        }

        .fcc-support-ticket-list {
            max-height: none;
        }
    }
</style>

<div class="container fcc-support-shell">
    <?= \Altum\Alerts::output_alerts() ?>

    <div class="fcc-support-card fcc-support-hero">
        <div>
            <div class="d-flex flex-wrap mb-3" style="gap:.6rem;">
                <span class="fcc-support-pill">FCC podrška</span>
                <span class="fcc-support-pill">Prijedlozi i ideje</span>
                <span class="fcc-support-pill">Webinar teme</span>
            </div>
            <div class="fcc-support-hero-title">Podrška koja prati tvoj rad i pomaže ti da ideš dalje.</div>
            <div class="fcc-support-hero-copy">
                Ovdje nam možeš poslati pitanje, prijedlog, prijaviti problem ili predložiti temu koju želiš da obradimo na sljedećem webinaru.
                Sve poruke ostaju na jednom mjestu, a novi admin odgovori ostaju jasno označeni dok ih ne otvoriš.
            </div>
        </div>

        <div class="fcc-support-hero-aside">
            <div class="fcc-support-eyebrow">Tvoja podrška sada</div>
            <div class="fcc-support-hero-aside-row">
                <span class="text-muted">Aktivni razgovori</span>
                <strong><?= nr($support_totals['open'] + $support_totals['answered']) ?></strong>
            </div>
            <div class="fcc-support-hero-aside-row">
                <span class="text-muted">Nepročitani admin odgovori</span>
                <strong><?= nr($support_totals['unread']) ?></strong>
            </div>
            <div class="fcc-support-hero-aside-row">
                <span class="text-muted">Prijedlozi za webinar</span>
                <strong><?= nr($support_totals['webinar']) ?></strong>
            </div>
            <div class="fcc-support-helper mt-3">
                Ako admin označi da je nešto riješeno, vidjet ćeš to kao novu obavijest i oznaka nestaje tek kad otvoriš ticket.
            </div>
        </div>
    </div>

    <div class="fcc-support-grid">
        <div class="fcc-support-kpi">
            <div class="fcc-support-kpi-label">Otvoreni</div>
            <div class="fcc-support-kpi-value"><?= nr($support_totals['open']) ?></div>
            <div class="fcc-support-kpi-note">Upiti koji još čekaju obradu ili tvoj daljnji odgovor.</div>
        </div>
        <div class="fcc-support-kpi">
            <div class="fcc-support-kpi-label">Odgovoreni</div>
            <div class="fcc-support-kpi-value"><?= nr($support_totals['answered']) ?></div>
            <div class="fcc-support-kpi-note">Admin odgovor je spreman, otvori ticket i provjeri smjer.</div>
        </div>
        <div class="fcc-support-kpi">
            <div class="fcc-support-kpi-label">Nepročitano</div>
            <div class="fcc-support-kpi-value"><?= nr($support_totals['unread']) ?></div>
            <div class="fcc-support-kpi-note">Nova obavijest ili odgovor koji još nisi otvorio.</div>
        </div>
        <div class="fcc-support-kpi">
            <div class="fcc-support-kpi-label">Webinar ideje</div>
            <div class="fcc-support-kpi-value"><?= nr($support_totals['webinar']) ?></div>
            <div class="fcc-support-kpi-note">Teme koje si predložio za sljedeće edukacije i webinare.</div>
        </div>
    </div>

    <div class="fcc-support-main-layout">
        <div class="fcc-support-card fcc-support-thread-card">
            <?php if(!empty($data->selected_feedback_ticket)): ?>
                <?php
                $selected_ticket = $data->selected_feedback_ticket;
                $selected_status_key = (string) ($selected_ticket->status ?? 'open');
                $selected_status_badge_class = match($selected_status_key) {
                    'answered' => 'is-answered',
                    'closed' => 'is-closed',
                    default => 'is-open',
                };
                ?>
                <div class="fcc-support-thread-header">
                    <div>
                        <div class="fcc-support-eyebrow">Otvoreni razgovor</div>
                        <div class="fcc-support-section-title mb-1">#<?= (int) $selected_ticket->feedback_ticket_id ?> · <?= htmlspecialchars((string) ($selected_ticket->subject ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="text-muted"><?= htmlspecialchars((string) ($feedback_categories[$selected_ticket->category] ?? $selected_ticket->category), ENT_QUOTES, 'UTF-8') ?> · Zadnje ažuriranje <?= \Altum\Date::get($selected_ticket->last_datetime, 2) ?></div>
                    </div>
                    <div class="fcc-support-badges">
                        <span class="fcc-support-badge <?= $selected_status_badge_class ?>"><?= htmlspecialchars((string) ($feedback_statuses[$selected_ticket->status] ?? $selected_ticket->status), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if(!empty($selected_ticket->is_webinar_topic_suggestion)): ?>
                            <span class="fcc-support-badge is-webinar">Webinar tema</span>
                        <?php endif ?>
                        <?php if($ticket_has_unread_admin_update($selected_ticket)): ?>
                            <span class="fcc-support-badge is-unread">Novo</span>
                        <?php endif ?>
                    </div>
                </div>

                <div class="fcc-support-thread-actions">
                    <div class="text-muted small">Sve poruke i odgovori nalaze se ovdje, bez otvaranja nove stranice.</div>
                    <?php if(($selected_ticket->status ?? 'open') !== 'closed'): ?>
                        <form action="" method="post" class="d-inline-flex">
                            <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
                            <input type="hidden" name="action_type" value="close" />
                            <input type="hidden" name="feedback_ticket_id" value="<?= (int) $selected_ticket->feedback_ticket_id ?>" />
                            <button type="submit" class="fcc-support-inline-link" style="background:none;border:0;padding:0;">Označi kao riješeno</button>
                        </form>
                    <?php else: ?>
                        <div class="text-muted small">Ako trebaš dodatno pojašnjenje, samo odgovori ispod i razgovor će se ponovno otvoriti.</div>
                    <?php endif ?>
                </div>

                <div class="fcc-support-thread-messages">
                    <?php foreach($data->selected_messages as $message): ?>
                        <div class="fcc-support-thread-message <?= !empty($message->is_admin_reply) ? 'is-admin' : '' ?>">
                            <div class="fcc-support-thread-message-top">
                                <strong><?= !empty($message->is_admin_reply) ? 'Admin / mentor' : 'Ti' ?></strong>
                                <span class="text-muted small"><?= \Altum\Date::get($message->datetime, 2) ?></span>
                            </div>
                            <div class="fcc-support-thread-message-copy"><?= nl2br(htmlspecialchars((string) ($message->message ?? ''), ENT_QUOTES, 'UTF-8')) ?></div>
                            <?php if(!empty($message->attachment)): ?>
                                <div class="mt-3">
                                    <a href="<?= \Altum\Uploads::get_full_url('feedback_tickets') . $message->attachment ?>" target="_blank" rel="noopener noreferrer" class="fcc-support-inline-link">Otvori privitak</a>
                                </div>
                            <?php endif ?>
                        </div>
                    <?php endforeach ?>
                </div>

                <div class="fcc-support-reply-box">
                    <div class="fcc-support-eyebrow">Tvoj odgovor</div>
                    <form action="" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
                        <input type="hidden" name="action_type" value="reply" />
                        <input type="hidden" name="feedback_ticket_id" value="<?= (int) $selected_ticket->feedback_ticket_id ?>" />

                        <div class="fcc-support-field">
                            <label for="reply_message" class="fcc-support-label">Napiši odgovor ili dodatno pojašnjenje</label>
                            <textarea id="reply_message" name="message" class="fcc-support-textarea <?= \Altum\Alerts::has_field_errors('message') ? 'is-invalid' : null ?>" required="required"><?= htmlspecialchars((string) ($_POST['action_type'] ?? '') === 'reply' ? ($_POST['message'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            <?= \Altum\Alerts::output_field_error('message') ?>
                        </div>

                        <div class="fcc-support-field">
                            <label for="attachment" class="fcc-support-label">Opcionalni privitak</label>
                            <input type="file" id="attachment" name="attachment" class="fcc-support-input" accept="<?= \Altum\Uploads::get_whitelisted_file_extensions_accept('feedback_tickets') ?>" />
                        </div>

                        <button type="submit" class="fcc-support-button">Pošalji odgovor</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="fcc-support-empty-state">
                    <div>
                        <div class="fcc-support-eyebrow">Razgovor</div>
                        <div class="fcc-support-empty-state-title">Odaberi razgovor i nastavi odmah</div>
                        <div class="text-muted">S desne strane otvori jedan od svojih razgovora. Ovdje ćeš odmah vidjeti cijelu komunikaciju i polje za odgovor na istoj stranici.</div>
                    </div>
                </div>
            <?php endif ?>
        </div>

        <div class="fcc-support-sidebar-stack">
            <div class="fcc-support-card fcc-support-compose-card">
            <div class="fcc-support-eyebrow">Pošalji poruku</div>
            <div class="fcc-support-section-title">Nova poruka podršci</div>
            <div class="text-muted mb-4">Jednim mjestom možeš prijaviti problem, poslati ideju ili predložiti temu koja ti stvarno treba na sljedećem webinaru.</div>

            <form action="" method="post" enctype="multipart/form-data" role="form">
                <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />

                <div class="fcc-support-webinar-box">
                    <input type="checkbox" id="is_webinar_topic_suggestion" name="is_webinar_topic_suggestion" value="1" class="fcc-support-checkbox" <?= !empty($_POST['is_webinar_topic_suggestion']) ? 'checked="checked"' : '' ?> />
                    <div>
                        <label for="is_webinar_topic_suggestion" class="fcc-support-label mb-1">Predloži temu sljedećeg webinara</label>
                        <div class="fcc-support-helper mt-0">Ako označiš ovu opciju, tvoj upit ulazi i u prijedloge tema koje tim može obraditi na narednom webinaru.</div>
                    </div>
                </div>

                <div class="fcc-support-field">
                    <label for="subject" class="fcc-support-label"><?= l('feedback_tickets.subject') ?></label>
                    <input type="text" id="subject" name="subject" class="fcc-support-input <?= \Altum\Alerts::has_field_errors('subject') ? 'is-invalid' : null ?>" maxlength="128" value="<?= htmlspecialchars((string) ($_POST['subject'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required="required" />
                    <?= \Altum\Alerts::output_field_error('subject') ?>
                    <div class="fcc-support-helper"><?= l('feedback_tickets.subject_help') ?></div>
                </div>

                <div class="fcc-support-field">
                    <label for="category" class="fcc-support-label"><?= l('feedback_tickets.category') ?></label>
                    <select id="category" name="category" class="fcc-support-select">
                        <?php $selected_category = $_POST['category'] ?? 'other' ?>
                        <option value="change" <?= $selected_category == 'change' ? 'selected="selected"' : null ?>><?= l('feedback_tickets.category.change') ?></option>
                        <option value="add" <?= $selected_category == 'add' ? 'selected="selected"' : null ?>><?= l('feedback_tickets.category.add') ?></option>
                        <option value="bug" <?= $selected_category == 'bug' ? 'selected="selected"' : null ?>><?= l('feedback_tickets.category.bug') ?></option>
                        <option value="other" <?= $selected_category == 'other' ? 'selected="selected"' : null ?>><?= l('feedback_tickets.category.other') ?></option>
                    </select>
                </div>

                <div class="fcc-support-field">
                    <label for="message" class="fcc-support-label"><?= l('feedback_tickets.message') ?></label>
                    <textarea id="message" name="message" class="fcc-support-textarea <?= \Altum\Alerts::has_field_errors('message') ? 'is-invalid' : null ?>" required="required"><?= htmlspecialchars((string) ($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                    <?= \Altum\Alerts::output_field_error('message') ?>
                    <div class="fcc-support-helper"><?= l('feedback_tickets.message_help') ?></div>
                </div>

                <div class="fcc-support-field">
                    <label for="screenshot" class="fcc-support-label"><?= l('feedback_tickets.screenshot_optional') ?></label>
                    <input type="file" id="screenshot" name="screenshot" class="fcc-support-input" accept="<?= \Altum\Uploads::get_whitelisted_file_extensions_accept('feedback_tickets') ?>" />
                    <div class="fcc-support-helper"><?= l('feedback_tickets.allowed') ?>: <?= \Altum\Uploads::get_whitelisted_file_extensions_accept('feedback_tickets') ?></div>
                </div>

                <button type="submit" class="fcc-support-button">Pošalji podršci</button>
            </form>
            </div>

            <div class="fcc-support-card fcc-support-inbox-card">
                <div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:.75rem;">
                    <div>
                        <div class="fcc-support-eyebrow">Tvoji razgovori</div>
                        <div class="fcc-support-section-title mb-0">Inbox podrške</div>
                    </div>
                    <span class="fcc-support-badge"><?= nr($support_totals['all']) ?> ukupno</span>
                </div>

                <?php if(count($data->feedback_tickets)): ?>
                    <div class="fcc-support-ticket-list">
                        <?php foreach($data->feedback_tickets as $ticket): ?>
                            <?php
                            $has_unread_admin_update = $ticket_has_unread_admin_update($ticket);
                            $status_key = (string) ($ticket->status ?? 'open');
                            $status_badge_class = match($status_key) {
                                'answered' => 'is-answered',
                                'closed' => 'is-closed',
                                default => 'is-open',
                            };
                            $is_selected = !empty($data->selected_feedback_ticket) && (int) $data->selected_feedback_ticket->feedback_ticket_id === (int) $ticket->feedback_ticket_id;
                            ?>
                            <div class="fcc-support-ticket <?= $is_selected ? 'is-active' : '' ?>">
                                <div class="fcc-support-ticket-top">
                                    <div>
                                        <a href="<?= url('feedback-tickets?ticket_id=' . (int) $ticket->feedback_ticket_id) ?>" class="fcc-support-ticket-title"><?= htmlspecialchars((string) ($ticket->subject ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                                        <div class="fcc-support-ticket-meta"><?= htmlspecialchars((string) ($feedback_categories[$ticket->category] ?? $ticket->category), ENT_QUOTES, 'UTF-8') ?> · Zadnje ažuriranje <?= \Altum\Date::get($ticket->last_datetime, 2) ?></div>
                                    </div>
                                    <div class="fcc-support-badges">
                                        <span class="fcc-support-badge <?= $status_badge_class ?>"><?= htmlspecialchars((string) ($feedback_statuses[$ticket->status] ?? $ticket->status), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if($has_unread_admin_update): ?>
                                            <span class="fcc-support-badge is-unread">Nepročitano</span>
                                        <?php endif ?>
                                        <?php if(!empty($ticket->is_webinar_topic_suggestion)): ?>
                                            <span class="fcc-support-badge is-webinar">Webinar tema</span>
                                        <?php endif ?>
                                    </div>
                                </div>
                                <div class="fcc-support-ticket-preview"><?= htmlspecialchars((string) ($ticket->subject ?? ''), ENT_QUOTES, 'UTF-8') ?> · otvori razgovor i nastavi komunikaciju na istoj stranici.</div>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php else: ?>
                    <div class="text-muted">Još nemaš otvorenih poruka podršci. Kad pošalješ prvi upit ili prijedlog, ovdje ćeš pratiti cijeli razgovor.</div>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>
