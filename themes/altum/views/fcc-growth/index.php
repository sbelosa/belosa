<?php defined('ALTUMCODE') || die() ?>

<style>
    .fcc-growth-page .fcc-growth-hero,
    .fcc-growth-page .fcc-growth-card,
    .fcc-growth-page .fcc-growth-copy-box,
    .fcc-growth-page .fcc-growth-action {
        border: 1px solid rgba(255, 255, 255, 0.07);
        border-radius: 18px;
        background: linear-gradient(160deg, rgba(255,255,255,0.03), rgba(255,255,255,0.015));
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.22);
    }

    .fcc-growth-page .fcc-growth-hero {
        background:
            radial-gradient(900px circle at 0% 0%, rgba(79, 222, 215, 0.16), transparent 45%),
            radial-gradient(700px circle at 100% 0%, rgba(255, 198, 0, 0.12), transparent 40%),
            linear-gradient(180deg, rgba(11, 16, 26, 0.92), rgba(9, 12, 20, 0.92));
    }

    .fcc-growth-page .fcc-growth-eyebrow {
        text-transform: uppercase;
        letter-spacing: 0.18em;
        font-size: 0.72rem;
        color: rgba(255,255,255,0.56);
        margin-bottom: 0.85rem;
    }

    .fcc-growth-page .fcc-growth-title {
        font-size: clamp(2rem, 3.2vw, 2.7rem);
        color: #f4fbff;
        margin-bottom: 0.8rem;
        font-weight: 700;
    }

    .fcc-growth-page .fcc-growth-subtitle,
    .fcc-growth-page .fcc-growth-muted {
        color: rgba(225, 236, 247, 0.78);
    }

    .fcc-growth-page .fcc-growth-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        border-radius: 999px;
        padding: 0.42rem 0.75rem;
        background: rgba(127, 215, 208, 0.12);
        color: #cbfffa;
        border: 1px solid rgba(127, 215, 208, 0.14);
        font-size: 0.84rem;
        font-weight: 700;
        margin: 0.35rem 0.35rem 0 0;
    }

    .fcc-growth-page .fcc-growth-card-title {
        color: #f6fbff;
        font-weight: 700;
        margin-bottom: 0.65rem;
    }

    .fcc-growth-page .fcc-growth-bullet-list {
        list-style: none;
        padding-left: 0;
        margin-bottom: 0;
    }

    .fcc-growth-page .fcc-growth-bullet-list li {
        position: relative;
        padding-left: 1.3rem;
        margin-bottom: 0.75rem;
        color: rgba(228, 235, 245, 0.84);
    }

    .fcc-growth-page .fcc-growth-bullet-list li::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0.55rem;
        width: 0.5rem;
        height: 0.5rem;
        border-radius: 999px;
        background: linear-gradient(135deg, #7df3e6 0%, #ffc600 100%);
        box-shadow: 0 0 0 5px rgba(125, 243, 230, 0.08);
    }

    .fcc-growth-page .fcc-growth-step {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.1rem;
        height: 2.1rem;
        border-radius: 999px;
        font-weight: 700;
        color: #082524;
        background: linear-gradient(135deg, #c7fff8 0%, #7df3e6 100%);
        box-shadow: 0 10px 24px rgba(79, 222, 215, 0.22);
        margin-right: 0.85rem;
        flex-shrink: 0;
    }

    .fcc-growth-page .fcc-growth-copy-box {
        padding: 1rem 1rem 1.1rem;
        background: rgba(7, 17, 29, 0.52);
    }

    .fcc-growth-page .fcc-growth-copy-label {
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: rgba(255,255,255,0.48);
        margin-bottom: 0.5rem;
    }

    .fcc-growth-page .fcc-growth-copy-text {
        color: #f4fbff;
        margin-bottom: 0;
        white-space: pre-line;
    }

    .fcc-growth-page .fcc-growth-action {
        display: block;
        text-decoration: none;
        padding: 1rem 1rem 1rem 1.05rem;
        color: inherit;
        height: 100%;
        transition: transform 0.18s ease, border-color 0.18s ease, background 0.18s ease;
    }

    .fcc-growth-page .fcc-growth-action:hover {
        transform: translateY(-2px);
        border-color: rgba(127, 215, 208, 0.28);
        background: rgba(127, 215, 208, 0.08);
        text-decoration: none;
    }

    .fcc-growth-page .fcc-growth-action-icon {
        width: 2.4rem;
        height: 2.4rem;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgba(125, 243, 230, 0.18), rgba(255, 198, 0, 0.14));
        color: #d8fff9;
        margin-bottom: 0.9rem;
    }

    .fcc-growth-page .fcc-growth-anchor {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.75rem 1rem;
        border-radius: 14px;
        background: rgba(127, 215, 208, 0.09);
        border: 1px solid rgba(127, 215, 208, 0.18);
        color: #ddfffb;
        font-weight: 700;
        text-decoration: none;
        margin: 0.35rem 0.45rem 0 0;
    }

    .fcc-growth-page .fcc-growth-anchor:hover {
        color: #ffffff;
        text-decoration: none;
        background: rgba(127, 215, 208, 0.14);
    }

    .fcc-growth-page .fcc-growth-install-btn {
        border-radius: 14px;
        font-weight: 700;
        border: 1px solid rgba(127, 215, 208, 0.35);
        background: linear-gradient(135deg, rgba(125, 243, 230, 0.2), rgba(255, 198, 0, 0.2));
        color: #f6fffe;
    }

    .fcc-growth-page .fcc-growth-install-btn:disabled {
        opacity: 0.72;
    }

    .fcc-growth-page .fcc-growth-install-feedback {
        margin-top: 0.85rem;
    }
</style>

<div class="container">
    <div class="fcc-growth-page">
        <div class="fcc-growth-hero p-4 p-lg-5 mb-4">
            <div class="fcc-growth-eyebrow">FCC rast za suradnike</div>
            <h1 class="fcc-growth-title">Odaberi gotov set, postavi ga danas i počni ga odmah koristiti</h1>
            <p class="fcc-growth-subtitle mb-0">Ovdje ne trebaš smišljati strategiju. Ispod imaš gotove setove, točne korake, spremne tekstove i jasan redoslijed kojim sve postavljaš. Tvoj zadatak je odabrati jedan set, kopirati sadržaj, objaviti i krenuti ga koristiti isti dan.</p>

            <div class="mt-3 mb-3">
                <span class="fcc-growth-chip"><i class="fas fa-rocket"></i> odaberi jedan cilj</span>
                <span class="fcc-growth-chip"><i class="fas fa-copy"></i> kopiraj gotove tekstove</span>
                <span class="fcc-growth-chip"><i class="fas fa-play"></i> objavi i počni danas</span>
            </div>

            <a href="#products" class="fcc-growth-anchor">SET ZA PRODAJU PROIZVODA</a>
            <a href="#recruitment" class="fcc-growth-anchor">SET ZA SURADNJU</a>
        </div>

        <div class="fcc-growth-card p-4 p-lg-5 mb-4">
            <div class="d-flex align-items-center mb-3">
                <div class="fcc-growth-step">1</div>
                <div>
                    <h2 class="h4 mb-1 text-white"><?= $data->start_here['title'] ?></h2>
                    <p class="fcc-growth-muted mb-0">Kreni jednostavno. Ne radiš sve odjednom, nego jedan gotov set koji ti najbrže može početi donositi rezultate.</p>
                </div>
            </div>

            <ul class="fcc-growth-bullet-list mb-4">
                <?php foreach($data->start_here['items'] as $item): ?>
                    <li><?= $item ?></li>
                <?php endforeach ?>
            </ul>

            <div class="row m-n2">
                <?php foreach($data->implementation as $implementation): ?>
                    <div class="col-12 col-lg-6 p-2">
                        <div class="fcc-growth-copy-box h-100">
                            <div class="fcc-growth-card-title"><?= $implementation['title'] ?></div>
                            <div class="fcc-growth-muted mb-0"><?= $implementation['description'] ?></div>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        </div>

        <?php foreach($data->sets as $set): ?>
            <div class="fcc-growth-card p-4 p-lg-5 mb-4" id="<?= $set['slug'] ?>">
                <div class="fcc-growth-eyebrow mb-2"><?= $set['eyebrow'] ?></div>
                <h2 class="h3 text-white mb-2"><?= $set['title'] ?></h2>
                <p class="fcc-growth-muted mb-2"><?= $set['subtitle'] ?></p>
                <p class="fcc-growth-muted mb-4"><strong>Što ovaj set radi:</strong> <?= $set['focus'] ?></p>

                <div class="d-flex flex-wrap align-items-center mb-4">
                    <button
                        type="button"
                        class="btn fcc-growth-install-btn mr-2 mb-2"
                        data-install-set="<?= $set['slug'] ?>"
                    >
                        <i class="fas fa-magic mr-2"></i>Implementiraj set
                    </button>
                    <span class="fcc-growth-muted small mb-2">Klik kreira gotove biolinkove iz predložaka i otvara ih za doradu.</span>
                </div>

                <div class="fcc-growth-install-feedback" data-install-feedback="<?= $set['slug'] ?>"></div>

                <div class="row m-n2 mb-3">
                    <div class="col-12 col-lg-5 p-2">
                        <div class="fcc-growth-copy-box h-100">
                            <div class="fcc-growth-card-title">Ovaj set je za tebe ako</div>
                            <ul class="fcc-growth-bullet-list">
                                <?php foreach($set['who'] as $who_item): ?>
                                    <li><?= $who_item ?></li>
                                <?php endforeach ?>
                            </ul>
                        </div>
                    </div>

                    <div class="col-12 col-lg-7 p-2">
                        <div class="fcc-growth-copy-box h-100">
                            <div class="fcc-growth-card-title">Brzi ulaz u implementaciju</div>
                            <p class="fcc-growth-muted mb-3">Otvori alat, postavi sadržaj po koracima ispod i ne kompliciraj više od ovoga prvi dan.</p>
                            <?php foreach($set['links'] as $link): ?>
                                <a href="<?= $link['url'] ?>" class="fcc-growth-anchor"><i class="<?= $link['icon'] ?> mr-2"></i><?= $link['title'] ?></a>
                            <?php endforeach ?>
                        </div>
                    </div>
                </div>

                <?php foreach($set['steps'] as $step): ?>
                    <div class="fcc-growth-copy-box mb-3">
                        <div class="d-flex align-items-start mb-3">
                            <div class="fcc-growth-step"><?= $step['number'] ?></div>
                            <div>
                                <div class="fcc-growth-card-title mb-1"><?= $step['title'] ?></div>
                                <p class="fcc-growth-muted mb-2"><strong>Zašto ovo radiš:</strong> <?= $step['why'] ?></p>
                                <p class="fcc-growth-muted mb-0"><strong>Što sad napravi:</strong> <?= $step['action'] ?></p>
                            </div>
                        </div>

                        <ul class="fcc-growth-bullet-list">
                            <?php foreach($step['blocks'] as $block): ?>
                                <li><?= $block ?></li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                <?php endforeach ?>

                <div class="row m-n2 mb-3">
                    <div class="col-12 col-lg-6 p-2">
                        <div class="fcc-growth-copy-box h-100">
                            <div class="fcc-growth-copy-label">Gotovi naslov</div>
                            <p class="fcc-growth-copy-text"><?= $set['copy']['headline'] ?></p>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6 p-2">
                        <div class="fcc-growth-copy-box h-100">
                            <div class="fcc-growth-copy-label">Gotovi uvod</div>
                            <p class="fcc-growth-copy-text"><?= $set['copy']['subheadline'] ?></p>
                        </div>
                    </div>
                </div>

                <div class="row m-n2 mb-3">
                    <div class="col-12 col-lg-4 p-2">
                        <div class="fcc-growth-copy-box h-100">
                            <div class="fcc-growth-copy-label">Gotovi gumbi</div>
                            <ul class="fcc-growth-bullet-list">
                                <?php foreach($set['copy']['buttons'] as $button): ?>
                                    <li><?= $button ?></li>
                                <?php endforeach ?>
                            </ul>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4 p-2">
                        <div class="fcc-growth-copy-box h-100">
                            <div class="fcc-growth-copy-label">WhatsApp poruka</div>
                            <p class="fcc-growth-copy-text"><?= $set['copy']['whatsapp'] ?></p>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4 p-2">
                        <div class="fcc-growth-copy-box h-100">
                            <div class="fcc-growth-copy-label">Tekst za collector</div>
                            <p class="fcc-growth-copy-text"><?= $set['copy']['collector_intro'] ?></p>
                        </div>
                    </div>
                </div>

                <div class="fcc-growth-copy-box mb-3">
                    <div class="fcc-growth-copy-label">Poruka koju šalješ nakon ostavljenog kontakta</div>
                    <p class="fcc-growth-copy-text"><?= $set['copy']['follow_up'] ?></p>
                </div>

                <div class="fcc-growth-copy-box">
                    <div class="fcc-growth-card-title">Dnevna rutina da ti set počne raditi</div>
                    <ul class="fcc-growth-bullet-list">
                        <?php foreach($set['daily_actions'] as $action): ?>
                            <li><?= $action ?></li>
                        <?php endforeach ?>
                    </ul>
                </div>
            </div>
        <?php endforeach ?>

        <div class="fcc-growth-card p-4 p-lg-5 mb-4">
            <div class="d-flex align-items-center mb-4">
                <div class="fcc-growth-step">2</div>
                <div>
                    <h2 class="h4 mb-1 text-white">Alati koje koristiš odmah nakon postavljanja</h2>
                    <p class="fcc-growth-muted mb-0">Otvori ono što ti treba za objavu, praćenje interesa i javljanje ljudima koji su ostavili kontakt.</p>
                </div>
            </div>

            <div class="row m-n2">
                <?php foreach($data->quick_links as $link): ?>
                    <div class="col-12 col-lg-4 p-2">
                        <a href="<?= $link['url'] ?>" class="fcc-growth-action">
                            <div class="fcc-growth-action-icon"><i class="<?= $link['icon'] ?>"></i></div>
                            <div class="fcc-growth-card-title mb-2"><?= $link['title'] ?></div>
                            <div class="fcc-growth-muted"><?= $link['description'] ?></div>
                        </a>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
    </div>
</div>

<?php ob_start() ?>
<script>
    'use strict';

    document.querySelectorAll('[data-install-set]').forEach(button => {
        button.addEventListener('click', event => {
            const current_button = event.currentTarget;
            const set_slug = current_button.getAttribute('data-install-set');
            const feedback_container = document.querySelector(`[data-install-feedback="${set_slug}"]`);

            if(!feedback_container) {
                return;
            }

            current_button.setAttribute('disabled', 'disabled');
            feedback_container.innerHTML = '';

            const form_data = new FormData();
            form_data.set('global_token', global_token);
            form_data.set('request_type', 'create_fcc_funnel_set');
            form_data.set('set_slug', set_slug);

            fetch(`${url}link-ajax`, {
                method: 'POST',
                body: form_data
            })
                .then(response => response.json())
                .then(data => {
                    current_button.removeAttribute('disabled');

                    if(data.status === 'error') {
                        feedback_container.innerHTML = `<div class="alert alert-danger mb-0">${data.message}</div>`;
                        return;
                    }

                    const created_items = data?.details?.created || [];
                    const skipped_items = data?.details?.skipped || [];

                    let created_html = '';
                    if(created_items.length) {
                        created_html = '<div class="mb-2"><strong>Kreirano:</strong><ul class="mb-0 mt-2">';

                        created_items.forEach(item => {
                            created_html += `<li>${item.label} - <a href="${item.edit_url}">otvori za uređivanje</a></li>`;
                        });

                        created_html += '</ul></div>';
                    }

                    let skipped_html = '';
                    if(skipped_items.length) {
                        skipped_html = '<div class="small text-muted"><strong>Preskočeno:</strong><ul class="mb-0 mt-1">';

                        skipped_items.forEach(item => {
                            skipped_html += `<li>${item.label} (${item.reason})</li>`;
                        });

                        skipped_html += '</ul></div>';
                    }

                    feedback_container.innerHTML = `<div class="alert alert-success mb-0">${data.message}${created_html}${skipped_html}</div>`;
                })
                .catch(() => {
                    current_button.removeAttribute('disabled');
                    feedback_container.innerHTML = '<div class="alert alert-danger mb-0">Dogodila se greška. Pokušaj ponovno.</div>';
                });
        });
    });
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>