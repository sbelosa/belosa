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

$has_unread_admin_update = false;
$admin_last_replied_at = (string) ($data->feedback_ticket->admin_last_replied_at ?? '');
$user_last_read_at = (string) ($data->feedback_ticket->user_last_read_at ?? '');
$ticket_status = (string) ($data->feedback_ticket->status ?? 'open');

if($admin_last_replied_at !== '' && in_array($ticket_status, ['answered', 'closed'], true)) {
    $has_unread_admin_update = $user_last_read_at === '' || $user_last_read_at < $admin_last_replied_at;
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
    .fcc-support-message {
        border: 1px solid rgba(148, 163, 184, 0.14);
        border-radius: 1.15rem;
        background:
            radial-gradient(circle at top right, rgba(45, 212, 191, 0.08), transparent 28%),
            linear-gradient(180deg, rgba(17, 24, 39, 0.96) 0%, rgba(10, 15, 28, 0.98) 100%);
        box-shadow: 0 1.2rem 2.5rem rgba(2, 6, 23, 0.24);
    }

    .fcc-support-hero {
        padding: 1.4rem;
        display: grid;
        grid-template-columns: minmax(0, 1.3fr) minmax(290px, 0.7fr);
        gap: 1rem;
        margin-bottom: 1rem;
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

    .fcc-support-title {
        font-size: clamp(1.9rem, 3vw, 2.55rem);
        line-height: 1.08;
        color: #f8fbff;
        font-weight: 800;
        letter-spacing: -0.04em;
        margin: 0.8rem 0;
    }

    .fcc-support-hero-copy {
        color: rgba(226, 232, 240, 0.84);
        line-height: 1.65;
        font-size: 0.98rem;
        max-width: 720px;
    }

    .fcc-support-aside {
        padding: 1.1rem;
        border: 1px solid rgba(148, 163, 184, 0.12);
        border-radius: 1rem;
        background: rgba(15, 23, 42, 0.66);
    }

    .fcc-support-aside-row {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.72rem 0;
        border-bottom: 1px solid rgba(148, 163, 184, 0.12);
    }

    .fcc-support-aside-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .fcc-support-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(280px, 0.72fr);
        gap: 1rem;
    }

    .fcc-support-card {
        padding: 1.25rem;
    }

    .fcc-support-eyebrow {
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-size: 0.78rem;
        color: rgba(191, 211, 238, 0.72);
        margin-bottom: 0.5rem;
    }

    .fcc-support-section-title {
        font-size: 1.35rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: #f8fbff;
        margin-bottom: 0.45rem;
    }

    .fcc-support-badges {
        display: flex;
        gap: 0.45rem;
        flex-wrap: wrap;
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

    .fcc-support-badge.is-webinar {
        background: rgba(8, 145, 178, 0.18);
        border-color: rgba(34, 211, 238, 0.24);
        color: #cffafe;
    }

    .fcc-support-message-list {
        display: grid;
        gap: 0.95rem;
    }

    .fcc-support-message {
        padding: 1rem 1.05rem;
    }

    .fcc-support-message.is-admin {
        background:
            radial-gradient(circle at top right, rgba(56, 189, 248, 0.1), transparent 28%),
            linear-gradient(180deg, rgba(9, 32, 56, 0.96) 0%, rgba(9, 15, 28, 0.98) 100%);
        border-color: rgba(56, 189, 248, 0.2);
    }

    .fcc-support-message-top {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        align-items: flex-start;
        margin-bottom: 0.65rem;
    }

    .fcc-support-message-copy {
        color: rgba(241, 245, 249, 0.92);
        line-height: 1.7;
        white-space: pre-wrap;
    }

    .fcc-support-input,
    .fcc-support-textarea {
        width: 100%;
        border-radius: 0.95rem;
        border: 1px solid rgba(148, 163, 184, 0.16);
        background: rgba(8, 14, 26, 0.9);
        color: #f8fbff;
        padding: 0.9rem 1rem;
    }

    .fcc-support-textarea {
        min-height: 170px;
        resize: vertical;
    }

    .fcc-support-input:focus,
    .fcc-support-textarea:focus {
        outline: none;
        border-color: rgba(45, 212, 191, 0.45);
        box-shadow: 0 0 0 0.2rem rgba(45, 212, 191, 0.12);
    }

    .fcc-support-label {
        display: block;
        margin-bottom: 0.45rem;
        font-size: 0.84rem;
        font-weight: 700;
        color: rgba(226, 232, 240, 0.92);
    }

    .fcc-support-button,
    .fcc-support-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        border-radius: 1rem;
        padding: 0.9rem 1.1rem;
        font-weight: 700;
        text-decoration: none;
    }

    .fcc-support-button {
        border: 0;
        background: linear-gradient(135deg, #2dd4bf 0%, #22c55e 100%);
        color: #07131f;
        box-shadow: 0 1rem 2rem rgba(45, 212, 191, 0.18);
    }

    .fcc-support-secondary {
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: rgba(15, 23, 42, 0.72);
        color: #eef6ff;
    }

    .fcc-support-button:hover,
    .fcc-support-secondary:hover {
        text-decoration: none;
    }

    .fcc-support-note {
        border-radius: 1rem;
        border: 1px solid rgba(45, 212, 191, 0.18);
        background: linear-gradient(135deg, rgba(15, 118, 110, 0.18) 0%, rgba(15, 23, 42, 0.22) 100%);
        padding: 0.95rem 1rem;
        color: rgba(226, 232, 240, 0.9);
        line-height: 1.6;
    }

    @media (max-width: 1199.98px) {
        .fcc-support-hero,
        .fcc-support-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .fcc-support-message-top,
        .fcc-support-aside-row {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<div class="container fcc-support-shell">
    <?= \Altum\Alerts::output_alerts() ?>

    <div class="fcc-support-card fcc-support-hero">
        <div>
            <div class="d-flex flex-wrap mb-3" style="gap:.6rem;">
                <span class="fcc-support-pill">Razgovor podrške</span>
                <?php if(!empty($data->feedback_ticket->is_webinar_topic_suggestion)): ?>
                    <span class="fcc-support-pill">Webinar prijedlog</span>
                <?php endif ?>
            </div>

            <div class="fcc-support-title">#<?= (int) $data->feedback_ticket->feedback_ticket_id ?> · <?= htmlspecialchars((string) $data->feedback_ticket->subject, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="fcc-support-hero-copy">
                Ovdje pratiš cijeli razgovor s podrškom, vidiš zadnji smjer admina i možeš odmah nastaviti razgovor ako nešto treba dodatno pojasniti.
            </div>
        </div>

        <div class="fcc-support-aside">
            <div class="fcc-support-eyebrow">Status razgovora</div>
            <div class="fcc-support-aside-row">
                <span class="text-muted">Status</span>
                <?php $status_class = $ticket_status === 'open' ? 'is-open' : ($ticket_status === 'answered' ? 'is-answered' : 'is-closed') ?>
                <span class="fcc-support-badge <?= $status_class ?>"><?= htmlspecialchars((string) ($feedback_statuses[$data->feedback_ticket->status] ?? $data->feedback_ticket->status), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="fcc-support-aside-row">
                <span class="text-muted">Kategorija</span>
                <strong><?= htmlspecialchars((string) ($feedback_categories[$data->feedback_ticket->category] ?? $data->feedback_ticket->category), ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div class="fcc-support-aside-row">
                <span class="text-muted">Zadnje ažuriranje</span>
                <strong><?= \Altum\Date::get($data->feedback_ticket->last_datetime, 2) ?></strong>
            </div>
            <?php if($has_unread_admin_update): ?>
                <div class="fcc-support-aside-row">
                    <span class="text-muted">Novi admin odgovor</span>
                    <span class="fcc-support-badge is-unread">Nepročitano</span>
                </div>
            <?php endif ?>
            <?php if(!empty($data->feedback_ticket->is_webinar_topic_suggestion)): ?>
                <div class="fcc-support-aside-row">
                    <span class="text-muted">Webinar signal</span>
                    <span class="fcc-support-badge is-webinar">Predložena tema</span>
                </div>
            <?php endif ?>
        </div>
    </div>

    <div class="fcc-support-layout">
        <div>
            <div class="fcc-support-card mb-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:.75rem;">
                    <div>
                        <div class="fcc-support-eyebrow">Konverzacija</div>
                        <div class="fcc-support-section-title mb-0">Cijeli tijek razgovora</div>
                    </div>
                    <a href="<?= url('feedback-tickets') ?>" class="fcc-support-secondary">Natrag na podršku</a>
                </div>

                <div class="fcc-support-message-list">
                    <?php foreach($data->messages as $message): ?>
                        <div class="fcc-support-message <?= $message->is_admin_reply ? 'is-admin' : '' ?>">
                            <div class="fcc-support-message-top">
                                <strong><?= $message->is_admin_reply ? l('feedback_tickets.author_admin') : l('feedback_tickets.author_you') ?></strong>
                                <small class="text-muted"><?= \Altum\Date::get($message->datetime, 2) ?></small>
                            </div>

                            <div class="fcc-support-message-copy"><?= htmlspecialchars((string) $message->message, ENT_QUOTES, 'UTF-8') ?></div>

                            <?php if($message->attachment): ?>
                                <a href="<?= \Altum\Uploads::get_full_url('feedback_tickets') . $message->attachment ?>" target="_blank" class="d-inline-block mt-3 text-white-50">Otvori privitak</a>
                            <?php endif ?>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>

            <?php if($data->feedback_ticket->status != 'closed'): ?>
                <div class="fcc-support-card">
                    <div class="fcc-support-eyebrow">Nastavi razgovor</div>
                    <div class="fcc-support-section-title">Pošalji dodatno pojašnjenje ili novo pitanje</div>
                    <div class="text-muted mb-4">Ako treba još detalja, slobodno nastavi isti razgovor kako bi sve ostalo povezano i pregledno.</div>

                    <form action="" method="post" enctype="multipart/form-data" role="form">
                        <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
                        <input type="hidden" name="action_type" value="reply" />

                        <div class="form-group">
                            <label for="message" class="fcc-support-label"><?= l('feedback_tickets.message') ?></label>
                            <textarea id="message" name="message" class="fcc-support-textarea <?= \Altum\Alerts::has_field_errors('message') ? 'is-invalid' : null ?>" required="required"><?= htmlspecialchars((string) ($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                            <?= \Altum\Alerts::output_field_error('message') ?>
                        </div>

                        <div class="form-group">
                            <label for="attachment" class="fcc-support-label"><?= l('feedback_tickets.attachment_optional') ?></label>
                            <input type="file" id="attachment" name="attachment" class="fcc-support-input" accept="<?= \Altum\Uploads::get_whitelisted_file_extensions_accept('feedback_tickets') ?>" />
                        </div>

                        <button type="submit" class="fcc-support-button"><?= l('feedback_tickets.send_reply') ?></button>
                    </form>
                </div>
            <?php else: ?>
                <div class="fcc-support-card">
                    <div class="fcc-support-note">
                        Ovaj razgovor je označen kao riješen. Ako ti ipak treba dodatna pomoć, možeš otvoriti novu poruku podršci i nastaviti s novim kontekstom.
                    </div>
                </div>
            <?php endif ?>
        </div>

        <div class="fcc-support-card">
            <div class="fcc-support-eyebrow">Što sada</div>
            <div class="fcc-support-section-title">Brzi pregled</div>

            <div class="fcc-support-aside-row">
                <span class="text-muted">Glavni status</span>
                <strong><?= htmlspecialchars((string) ($feedback_statuses[$data->feedback_ticket->status] ?? $data->feedback_ticket->status), ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div class="fcc-support-aside-row">
                <span class="text-muted">Ako je odgovor spreman</span>
                <strong>Provjeri smjer i odluči treba li nastavak</strong>
            </div>
            <div class="fcc-support-aside-row">
                <span class="text-muted">Ako je riješeno</span>
                <strong>Razgovor ostaje spremljen za kasniji pregled</strong>
            </div>
            <div class="fcc-support-aside-row">
                <span class="text-muted">Ako trebaš novu temu</span>
                <strong>Pošalji novu poruku kroz podršku</strong>
            </div>

            <?php if($data->feedback_ticket->status != 'closed'): ?>
                <form action="" method="post" class="mt-4">
                    <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
                    <input type="hidden" name="action_type" value="close" />
                    <button type="submit" class="fcc-support-secondary">Označi kao riješeno</button>
                </form>
            <?php endif ?>
        </div>
    </div>
</div>
