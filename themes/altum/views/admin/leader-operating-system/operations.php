<?php defined('ALTUMCODE') || die(); ?>

<section class="los-operations">
    <header class="los-panel los-ops-intro">
        <div><div class="los-eyebrow">Operativa</div><h2>Odobrenja i NFC kartice</h2><p class="los-muted">Odobri novog suradnika, pripremi karticu i evidentiraj slanje. Ovdje se prikazuje trenutačni red za obradu.</p></div>
        <div class="los-ops-counts">
            <a href="<?= $leader_os_action_url ?>#los-approvals"><strong><?= nr((int)($data->operations['totals']['pending_approvals']??0)) ?></strong><span>Čeka odobrenje</span></a>
            <a href="<?= $leader_os_action_url ?>#los-cards"><strong><?= nr((int)($data->operations['totals']['card_queue']??0)) ?></strong><span>Kartice za slanje</span></a>
            <a href="<?= $leader_os_action_url ?>#los-recent"><strong><?= nr((int)($data->operations['totals']['registered']??0)) ?></strong><span>Registrirani računi</span></a>
        </div>
    </header>
        <div class="leader-os-ops-grid">
            <div class="leader-os-panel" id="los-approvals">
                <div class="d-flex justify-content-between align-items-start flex-wrap mb-3" style="gap:0.75rem;">
                    <div>
                        <div class="text-uppercase small text-muted mb-2"><?= htmlspecialchars(fcc_t('Prvi korak'), ENT_QUOTES, 'UTF-8') ?></div>
                        <h3 class="h5 mb-1"><?= htmlspecialchars(fcc_t('Novi suradnici za odobrenje'), ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="text-muted small"><?= htmlspecialchars(fcc_t('Kad odobriš pristup, suradnik dobiva poseban email da sada može koristiti FCC i svoju izrađenu aplikaciju.'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <span class="leader-os-status-badge status-warning"><?= nr((int) ($data->operations['totals']['pending_approvals'] ?? 0)) ?> <?= htmlspecialchars(fcc_t('čekaju'), ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <?php if(empty($data->operations['pending_rows'])): ?>
                    <div class="los-empty"><?= htmlspecialchars(fcc_t('Trenutno nema novih suradnika koji čekaju odobrenje.'), ENT_QUOTES, 'UTF-8') ?></div>
                <?php else: ?>
                    <div class="leader-os-ops-list">
                        <?php foreach(($data->operations['pending_rows'] ?? []) as $row): ?>
                            <div class="leader-os-ops-item">
                                <div class="leader-os-ops-item-header">
                                    <div>
                                        <div class="leader-os-ops-title"><?= htmlspecialchars((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="leader-os-ops-email"><?= htmlspecialchars((string) ($row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>

                                    <div class="d-flex flex-wrap justify-content-lg-end" style="gap:0.45rem;">
                                        <span class="leader-os-status-badge status-warning"><?= htmlspecialchars((string) ($row['status_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="leader-os-status-badge status-info"><?= htmlspecialchars((string) ($row['plan_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>

                                <div class="leader-os-ops-meta">
                                    <div>
                                        <span class="leader-os-ops-meta-label"><?= htmlspecialchars(fcc_t('Registracija'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <div class="leader-os-ops-meta-value"><?= !empty($row['datetime']) ? \Altum\Date::get($row['datetime'], 2) : '-' ?></div>
                                    </div>
                                    <div>
                                        <span class="leader-os-ops-meta-label"><?= htmlspecialchars(fcc_t('Glavna aplikacija'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <div class="leader-os-ops-meta-value">
                                            <?php if(!empty($row['main_biolink_url'])): ?>
                                                <a href="<?= $row['main_biolink_url'] ?>" target="_blank" rel="noopener" class="leader-os-link"><?= htmlspecialchars((string) $row['main_biolink_url'], ENT_QUOTES, 'UTF-8') ?></a>
                                            <?php else: ?>
                                                <?= htmlspecialchars(fcc_t('Još nije povezana'), ENT_QUOTES, 'UTF-8') ?>
                                            <?php endif ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="leader-os-ops-meta-label"><?= htmlspecialchars(fcc_t('Kontakt za karticu'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <div class="leader-os-ops-meta-value">
                                            <?= !empty($row['phone']) ? htmlspecialchars((string) $row['phone'], ENT_QUOTES, 'UTF-8') : fcc_t('Telefon nije upisan') ?>
                                            <?php if(!empty($row['forever_id'])): ?>
                                                <br><span class="text-muted"><?= htmlspecialchars(fcc_t('Forever ID:'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) $row['forever_id'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="leader-os-ops-meta-label"><?= htmlspecialchars(fcc_t('Adresa'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <div class="leader-os-ops-meta-value leader-os-ops-address"><?= !empty($row['address_lines']) ? htmlspecialchars(implode("\n", (array) $row['address_lines']), ENT_QUOTES, 'UTF-8') : fcc_t('Adresa još nije upisana') ?></div>
                                    </div>
                                </div>

                                <div class="leader-os-ops-actions leader-os-ops-approval">
                                    <form action="<?= $leader_os_action_url ?>" method="post" class="mb-0 d-inline-block">
                                        <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                                        <input type="hidden" name="user_id" value="<?= (int) ($row['user_id'] ?? 0) ?>" />
                                        <?php $teamSubjectId=(int)$row['user_id']; require THEME_PATH.'views/admin/partials/sponsor-fields.php'; ?>
                                        <button type="submit" name="los_approve_access" value="1" class="btn btn-sm leader-os-action-button">
                                            <i class="fas fa-check-circle mr-1"></i> <?= htmlspecialchars(fcc_t('Odobri pristup'), ENT_QUOTES, 'UTF-8') ?>
                                        </button>
                                    </form>

                                    <form action="<?= $leader_os_action_url ?>" method="post" class="mb-0 d-inline-flex flex-wrap align-items-center" style="gap:0.45rem;" onsubmit="return confirm('Jeste li sigurni da želite odbiti pristup? Korisniku će se prvo poslati odabrana email obavijest, a zatim će račun biti trajno uklonjen s Forever Card Cluba.');">
                                        <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                                        <input type="hidden" name="user_id" value="<?= (int) ($row['user_id'] ?? 0) ?>" />
                                        <select name="los_rejection_reason" class="form-control form-control-sm bg-dark text-light border-secondary" style="min-width:17rem;max-width:100%;" aria-label="<?= htmlspecialchars(fcc_t('Razlog odbijanja pristupa'), ENT_QUOTES, 'UTF-8') ?>">
                                            <option value="invalid_id"><?= htmlspecialchars(fcc_t('Neispravan ID ili nije potvrđeno članstvo'), ENT_QUOTES, 'UTF-8') ?></option>
                                            <option value="valid_id_not_team"><?= htmlspecialchars(fcc_t('ID je potvrđen, ali nije naš tim, pošalji Force Click'), ENT_QUOTES, 'UTF-8') ?></option>
                                        </select>
                                        <button type="submit" name="los_reject_access" value="1" class="btn btn-sm leader-os-action-button-danger">
                                            <i class="fas fa-times-circle mr-1"></i> <?= htmlspecialchars(fcc_t('Pristup odbijen'), ENT_QUOTES, 'UTF-8') ?>
                                        </button>
                                    </form>

                                    <a href="<?= $row['admin_user_update_url'] ?>" class="btn btn-sm btn-outline-light">
                                        <i class="fas fa-user-cog mr-1"></i> <?= htmlspecialchars(fcc_t('Uredi korisnika'), ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            </div>

            <div class="leader-os-panel" id="los-cards">
                <div class="d-flex justify-content-between align-items-start flex-wrap mb-3" style="gap:0.75rem;">
                    <div>
                        <div class="text-uppercase small text-muted mb-2"><?= htmlspecialchars(fcc_t('Drugi korak'), ENT_QUOTES, 'UTF-8') ?></div>
                        <h3 class="h5 mb-1"><?= htmlspecialchars(fcc_t('NFC kartice za obradu i slanje'), ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="text-muted small"><?= htmlspecialchars(fcc_t('U ovu listu automatski ulaze samo suradnici koji su aktivirali Forever Pro ili PRO trial od 30 dana i sada trebaju QR, pismo i slanje kartice.'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <span class="leader-os-status-badge status-info"><?= nr((int) ($data->operations['totals']['card_queue'] ?? 0)) ?> <?= htmlspecialchars(fcc_t('u redu'), ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <?php if(empty($data->operations['card_queue_rows'])): ?>
                    <div class="los-empty"><?= htmlspecialchars(fcc_t('Trenutno nema aktiviranih PRO suradnika koji čekaju obradu kartice.'), ENT_QUOTES, 'UTF-8') ?></div>
                <?php else: ?>
                    <div class="leader-os-ops-list">
                        <?php foreach(($data->operations['card_queue_rows'] ?? []) as $row): ?>
                            <div class="leader-os-ops-item">
                                <div class="leader-os-ops-item-header">
                                    <div>
                                        <div class="leader-os-ops-title"><?= htmlspecialchars((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="leader-os-ops-email"><?= htmlspecialchars((string) ($row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>

                                    <div class="d-flex flex-wrap justify-content-lg-end" style="gap:0.45rem;">
                                        <span class="leader-os-status-badge status-success"><?= htmlspecialchars((string) ($row['plan_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="leader-os-status-badge <?= !empty($row['is_trial_activation']) ? 'status-warning' : 'status-info' ?>">
                                            <?= !empty($row['is_trial_activation']) ? fcc_t('PRO trial') : fcc_t('PRO aktivacija') ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="leader-os-ops-meta">
                                    <div>
                                        <span class="leader-os-ops-meta-label"><?= htmlspecialchars(fcc_t('Kartica otvorena'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <div class="leader-os-ops-meta-value"><?= !empty($row['card_requested_at']) ? \Altum\Date::get($row['card_requested_at'], 2) : (!empty($row['datetime']) ? \Altum\Date::get($row['datetime'], 2) : '-') ?></div>
                                    </div>
                                    <div>
                                        <span class="leader-os-ops-meta-label"><?= htmlspecialchars(fcc_t('Glavna aplikacija'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <div class="leader-os-ops-meta-value">
                                            <?php if(!empty($row['main_biolink_url'])): ?>
                                                <a href="<?= $row['main_biolink_url'] ?>" target="_blank" rel="noopener" class="leader-os-link"><?= htmlspecialchars((string) $row['main_biolink_url'], ENT_QUOTES, 'UTF-8') ?></a>
                                            <?php else: ?>
                                                <?= htmlspecialchars(fcc_t('Još nije povezana'), ENT_QUOTES, 'UTF-8') ?>
                                            <?php endif ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="leader-os-ops-meta-label"><?= htmlspecialchars(fcc_t('Kontakt i ID'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <div class="leader-os-ops-meta-value">
                                            <?= !empty($row['phone']) ? htmlspecialchars((string) $row['phone'], ENT_QUOTES, 'UTF-8') : fcc_t('Telefon nije upisan') ?>
                                            <?php if(!empty($row['forever_id'])): ?>
                                                <br><span class="text-muted"><?= htmlspecialchars(fcc_t('Forever ID:'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) $row['forever_id'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="leader-os-ops-meta-label"><?= htmlspecialchars(fcc_t('Adresa za slanje'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <div class="leader-os-ops-meta-value leader-os-ops-address"><?= !empty($row['address_lines']) ? htmlspecialchars(implode("\n", (array) $row['address_lines']), ENT_QUOTES, 'UTF-8') : fcc_t('Adresa još nije upisana') ?></div>
                                    </div>
                                </div>

                                <div class="leader-os-ops-helper small mb-3"><?= htmlspecialchars(fcc_t('Preuzmi gotove datoteke ili otvori ispis. Za QR karticu odaberi printer za kartice, a za pismo klasičan A4 printer.'), ENT_QUOTES, 'UTF-8') ?></div>

                                <div class="leader-os-ops-actions">
                                    <a href="<?= $row['qr_download_url'] ?>" class="btn btn-sm btn-outline-light">
                                        <i class="fas fa-qrcode mr-1"></i> <?= htmlspecialchars(fcc_t('Preuzmi QR kod'), ENT_QUOTES, 'UTF-8') ?>
                                    </a>

                                    <a href="<?= $row['qr_download_url'] . '&mode=print' ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-light">
                                        <i class="fas fa-print mr-1"></i> <?= htmlspecialchars(fcc_t('Ispiši QR karticu'), ENT_QUOTES, 'UTF-8') ?>
                                    </a>

                                    <a href="<?= $row['letter_download_url'] ?>" class="btn btn-sm btn-outline-light">
                                        <i class="fas fa-file-pdf mr-1"></i> <?= htmlspecialchars(fcc_t('Preuzmi pismo'), ENT_QUOTES, 'UTF-8') ?>
                                    </a>

                                    <a href="<?= $row['letter_download_url'] . '&mode=print' ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-light">
                                        <i class="fas fa-print mr-1"></i> <?= htmlspecialchars(fcc_t('Ispiši pismo'), ENT_QUOTES, 'UTF-8') ?>
                                    </a>

                                    <a href="<?= $row['admin_user_update_url'] ?>" class="btn btn-sm btn-outline-light">
                                        <i class="fas fa-user-cog mr-1"></i> <?= htmlspecialchars(fcc_t('Uredi korisnika'), ENT_QUOTES, 'UTF-8') ?>
                                    </a>

                                    <form action="<?= $leader_os_action_url ?>" method="post" class="mb-0 d-inline-block">
                                        <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                                        <input type="hidden" name="user_id" value="<?= (int) ($row['user_id'] ?? 0) ?>" />
                                        <button type="submit" name="los_mark_card_sent" value="1" class="btn btn-sm leader-os-action-button">
                                            <i class="fas fa-paper-plane mr-1"></i> <?= htmlspecialchars(fcc_t('Označi poslano'), ENT_QUOTES, 'UTF-8') ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            </div>
        </div>

        <div class="leader-os-panel" id="los-recent">
            <div class="d-flex justify-content-between align-items-start flex-wrap mb-3" style="gap:0.75rem;">
                <div>
                    <div class="text-uppercase small text-muted mb-2"><?= htmlspecialchars(fcc_t('Pregled registracija'), ENT_QUOTES, 'UTF-8') ?></div>
                    <h3 class="h5 mb-1"><?= htmlspecialchars(fcc_t('Nedavno registrirani suradnici'), ENT_QUOTES, 'UTF-8') ?></h3>
                    <div class="text-muted small"><?= htmlspecialchars(fcc_t('Brzi pregled svih novih registracija, plana, statusa i glavne aplikacije bez izlaska iz LOS sustava.'), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <span class="leader-os-status-badge status-dark"><?= nr(count((array) ($data->operations['recent_rows'] ?? []))) ?> <?= htmlspecialchars(fcc_t('prikazano'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <div class="leader-os-table leader-os-ops-recent-table table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                    <tr>
                        <th><?= htmlspecialchars(fcc_t('Suradnik'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(fcc_t('Status'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(fcc_t('Plan'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(fcc_t('Glavna aplikacija'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th class="text-right"><?= htmlspecialchars(fcc_t('Akcija'), ENT_QUOTES, 'UTF-8') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach(($data->operations['recent_rows'] ?? []) as $row): ?>
                        <tr>
                            <td data-label="Suradnik">
                                <div class="font-weight-bold"><?= htmlspecialchars((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-muted small"><?= htmlspecialchars((string) ($row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td data-label="Status">
                                <span class="leader-os-status-badge <?= (int) ($row['status'] ?? 0) === 1 ? 'status-success' : ((int) ($row['status'] ?? 0) === 2 ? 'status-dark' : 'status-warning') ?>">
                                    <?= htmlspecialchars((string) ($row['status_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td data-label="Plan">
                                <div><?= htmlspecialchars((string) ($row['plan_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-muted small"><?= !empty($row['datetime']) ? \Altum\Date::get($row['datetime'], 2) : '-' ?></div>
                            </td>
                            <td data-label="Glavna aplikacija">
                                <?php if(!empty($row['main_biolink_url'])): ?>
                                    <a href="<?= $row['main_biolink_url'] ?>" target="_blank" rel="noopener" class="leader-os-link"><?= htmlspecialchars((string) $row['main_biolink_url'], ENT_QUOTES, 'UTF-8') ?></a>
                                <?php else: ?>
                                    <span class="text-muted"><?= htmlspecialchars(fcc_t('Još nije povezana'), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif ?>
                            </td>
                            <td data-label="Akcija" class="text-right">
                                <div class="d-flex justify-content-end flex-wrap" style="gap:0.45rem;">
                                    <?php if(!empty($row['is_active_pro'])): ?>
                                        <a href="<?= $row['qr_download_url'] ?>" class="btn btn-sm btn-outline-light">
                                            <i class="fas fa-qrcode mr-1"></i> <?= htmlspecialchars(fcc_t('QR'), ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                        <a href="<?= $row['letter_download_url'] . '&mode=print' ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-light">
                                            <i class="fas fa-file-pdf mr-1"></i> <?= htmlspecialchars(fcc_t('Pismo'), ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    <?php endif ?>

                                    <a href="<?= $memberLink((int)$row['user_id']) ?>" class="btn btn-sm btn-outline-light">
                                        <i class="fas fa-user-cog mr-1"></i> <?= htmlspecialchars(fcc_t('Profil u LOS-u'), ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
</section>
