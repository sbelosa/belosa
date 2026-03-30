<?php defined('ALTUMCODE') || die() ?>

<?= \Altum\Alerts::output_alerts() ?>

<?php ob_start() ?>
<style>
    .login-shell {
        color: #e6f3f1;
    }

    .login-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .35rem .7rem;
        border-radius: 999px;
        background: rgba(46, 211, 198, .12);
        border: 1px solid rgba(46, 211, 198, .16);
        color: #85efe4;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
        margin-bottom: .7rem;
    }

    .login-title {
        margin: 0;
        font-size: clamp(1.7rem, 3.2vw, 2.2rem);
        line-height: 1.02;
        letter-spacing: -.03em;
        color: #f7fbfb;
        font-weight: 800;
    }

    .login-subtitle {
        display: inline-block;
        margin-left: .45rem;
        color: #72e5d9;
        font-size: .82rem;
        font-weight: 700;
        vertical-align: middle;
    }

    .login-note-card {
        margin-top: .9rem;
        margin-bottom: 1.05rem;
        padding: .82rem .92rem;
        border-radius: 1rem;
        background: linear-gradient(180deg, rgba(19, 41, 43, .92), rgba(17, 34, 38, .92));
        border: 1px solid rgba(84, 224, 208, .16);
        color: #ddfbf7;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.03);
        font-size: .88rem;
        line-height: 1.5;
    }

    .login-card {
        padding: .9rem;
        border-radius: 1rem;
        background: rgba(255,255,255,.02);
        border: 1px solid rgba(255,255,255,.06);
    }

    .login-section-title {
        margin-bottom: .75rem;
        color: #f5fbfa;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .1em;
        text-transform: uppercase;
    }

    .login-shell label,
    .login-shell .custom-control-label {
        color: #c6d8d7;
        font-weight: 700;
        font-size: .82rem;
        margin-bottom: .45rem;
    }

    .login-shell .form-control {
        min-height: 3rem;
        border-radius: .9rem;
        border: 1px solid rgba(255,255,255,.08);
        background: rgba(255,255,255,.03);
        color: #f7fbfb;
        box-shadow: none;
    }

    .login-shell .form-control::placeholder {
        color: rgba(207, 226, 224, .44);
    }

    .login-shell .form-control:focus {
        border-color: rgba(81, 229, 212, .5);
        box-shadow: 0 0 0 .2rem rgba(81, 229, 212, .12);
        background: rgba(255,255,255,.05);
    }

    .login-shell .text-muted,
    .login-shell .form-text {
        color: rgba(198, 216, 215, .72) !important;
        font-size: .76rem;
        line-height: 1.45;
    }

    .login-links-row {
        display: flex;
        flex-direction: column;
        gap: .7rem;
        margin-top: .15rem;
    }

    .login-links-row a {
        color: #87f0e6;
    }

    .login-submit-wrap {
        margin-top: .95rem;
    }

    .login-submit-btn {
        min-height: 3.15rem;
        border: 0;
        border-radius: .95rem;
        font-size: .95rem;
        font-weight: 800;
        letter-spacing: .01em;
        color: #062624;
        background: linear-gradient(135deg, #6fffd2 0%, #42d4bf 52%, #2bb6dd 100%);
        box-shadow: 0 1rem 2rem rgba(52, 213, 198, .18);
    }

    .login-submit-btn:hover,
    .login-submit-btn:focus {
        color: #041d1b;
        transform: translateY(-1px);
        box-shadow: 0 1.2rem 2.2rem rgba(52, 213, 198, .22);
    }

    .login-auth-divider {
        position: relative;
        margin: 1.15rem 0 .85rem;
        text-align: center;
    }

    .login-auth-divider::before {
        content: '';
        position: absolute;
        inset: 50% 0 auto;
        border-top: 1px solid rgba(255,255,255,.08);
    }

    .login-auth-divider span {
        position: relative;
        z-index: 1;
        display: inline-block;
        padding: 0 .75rem;
        background: #0e141d;
        color: rgba(198, 216, 215, .72);
        font-size: .74rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .login-social-btn {
        border-radius: .85rem;
        border: 1px solid rgba(255,255,255,.07);
        background: rgba(255,255,255,.03);
        color: #eef7f6;
        font-weight: 700;
    }

    .login-social-btn:hover,
    .login-social-btn:focus {
        background: rgba(255,255,255,.06);
        color: #fff;
    }

    .login-footer-link {
        color: rgba(228, 242, 241, .78);
    }

    .login-footer-link a {
        color: #87f0e6;
    }

    @media (min-width: 768px) {
        .login-links-row {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }
</style>
<?php \Altum\Event::add_content(ob_get_clean(), 'head', 'fcc_login_premium_styles') ?>

<?php
//ALTUMCODE:DEMO if(DEMO) {
//ALTUMCODE:DEMO echo '<div class="card mb-4">';
//ALTUMCODE:DEMO echo '<div class="card-body">';
//ALTUMCODE:DEMO echo '<div class="h6">' . l('login.demo_title') . '</div>';
//ALTUMCODE:DEMO echo '<div><small class="text-muted">📱 ' . l('login.demo_disabled_features') . '</small></div>';
//ALTUMCODE:DEMO echo '<div><small class="text-muted">🛠️ ' . l('login.demo_admin_credentials') . '</small></div>';
//ALTUMCODE:DEMO echo '<div><small class="text-muted">👨‍💻 ' . l('login.demo_register_account') . '</small></div>';
//ALTUMCODE:DEMO echo '</div>';
//ALTUMCODE:DEMO echo '</div>';
//ALTUMCODE:DEMO }
?>

<div class="login-shell">
    <div class="login-eyebrow">Forever Card Club</div>

    <h1 class="login-title">
        <?= l('login.hero_title') ?>
        <span class="login-subtitle"><?= l('login.hero_subtitle') ?></span>
    </h1>

    <div class="login-note-card">
        Prijavite se u svoj Forever Card Club račun
    </div>

    <form action="" method="post" class="mt-4" role="form">
        <?php if(session_has('twofa_required') && $data->user && $data->user->twofa_secret && $data->user->status == 1): ?>
            <input id="email" type="hidden" name="email" value="<?= $data->user ? $data->values['email'] : null ?>" required="required" />
            <input id="password" type="hidden" name="password" value="<?= $data->user ? $data->values['password'] : null ?>" required="required" />
            <input id="rememberme" type="hidden" name="rememberme" value="<?= $data->values['rememberme'] ? '1' : null ?>">

            <div class="login-card">
                <div class="login-section-title">Potvrda prijave</div>

                <div class="form-group mb-0">
                    <label for="twofa_token"><?= l('login.twofa_token') ?></label>
                    <input id="twofa_token" type="text" name="twofa_token" class="form-control <?= \Altum\Alerts::has_field_errors('twofa_token') ? 'is-invalid' : null ?>" required="required" autocomplete="off" autofocus="autofocus" placeholder="123 456" maxlength="6" />
                    <?= \Altum\Alerts::output_field_error('twofa_token') ?>
                </div>

                <div class="login-submit-wrap">
                    <button type="submit" name="submit" class="btn btn-block login-submit-btn"><?= l('login.verify') ?></button>
                </div>
            </div>
        <?php else: ?>
            <div class="login-card">
                <div class="login-section-title">Prijava</div>

                <div class="form-group">
                    <label for="email"><?= l('global.email') ?></label>
                    <input id="email" type="text" name="email" class="form-control <?= \Altum\Alerts::has_field_errors('email') ? 'is-invalid' : null ?>" value="<?= $data->values['email'] ?>" required="required" autofocus="autofocus" />
                    <?= \Altum\Alerts::output_field_error('email') ?>
                </div>

                <div class="form-group" data-password-toggle-view data-password-toggle-view-show="<?= l('global.show') ?>" data-password-toggle-view-hide="<?= l('global.hide') ?>">
                    <label for="password"><?= l('global.password') ?></label>
                    <input id="password" type="password" name="password" class="form-control <?= \Altum\Alerts::has_field_errors('password') ? 'is-invalid' : null ?>" value="<?= $data->user ? $data->values['password'] : null ?>" required="required" />
                    <?= \Altum\Alerts::output_field_error('password') ?>
                </div>

                <?php if(settings()->captcha->login_is_enabled): ?>
                    <div class="form-group">
                        <?php $data->captcha->display() ?>
                    </div>
                <?php endif ?>

                <div class="login-links-row">
                    <div class="custom-control custom-checkbox" data-toggle="tooltip" title="<?= sprintf(l('login.remember_me_help'), settings()->users->login_rememberme_cookie_days ?? 30) ?>" data-tooltip-hide-on-click>
                        <input type="checkbox" name="rememberme" class="custom-control-input" id="rememberme" <?= $data->values['rememberme'] ? 'checked="checked"' : null ?>>
                        <label class="custom-control-label" for="rememberme"><small class="text-muted"><?= l('login.remember_me') ?></small></label>
                    </div>

                    <small class="text-muted">
                        <a href="<?= url('lost-password' . $data->redirect_append) ?>" class="text-decoration-none"><?= l('login.lost_password') ?></a>
                        <?php if(settings()->users->email_confirmation): ?>
                            / <a href="<?= url('resend-activation' . $data->redirect_append) ?>" class="text-decoration-none" role="button"><?= l('login.resend_activation') ?></a>
                        <?php endif ?>
                    </small>
                </div>

                <div class="login-submit-wrap">
                    <button type="submit" name="submit" class="btn btn-block login-submit-btn" <?= isset($_COOKIE['login_lockout']) ? 'disabled="disabled"' : null ?>><?= l('login.login') ?></button>
                </div>
            </div>
        <?php endif ?>

        <?php if(settings()->facebook->is_enabled || settings()->google->is_enabled || settings()->twitter->is_enabled || settings()->discord->is_enabled || settings()->linkedin->is_enabled || settings()->microsoft->is_enabled): ?>
            <div class="login-auth-divider"><span>ili nastavi s</span></div>

            <div>
                <?php if(settings()->facebook->is_enabled): ?>
                    <div class="mt-2">
                        <a href="<?= url('login/facebook-initiate') ?>" class="btn btn-block login-social-btn">
                            <img src="<?= ASSETS_FULL_URL . 'images/facebook.svg' ?>" class="mr-1" />
                            <?= l('login.facebook') ?>
                        </a>
                    </div>
                <?php endif ?>
                <?php if(settings()->google->is_enabled): ?>
                    <div class="mt-2">
                        <a href="<?= url('login/google-initiate') ?>" class="btn btn-block login-social-btn">
                            <img src="<?= ASSETS_FULL_URL . 'images/google.svg' ?>" class="mr-1" />
                            <?= l('login.google') ?>
                        </a>
                    </div>
                <?php endif ?>
                <?php if(settings()->twitter->is_enabled): ?>
                    <div class="mt-2">
                        <a href="<?= url('login/twitter-initiate') ?>" class="btn btn-block login-social-btn">
                            <img src="<?= ASSETS_FULL_URL . 'images/x.svg' ?>" class="mr-1" />
                            <?= l('login.twitter') ?>
                        </a>
                    </div>
                <?php endif ?>
                <?php if(settings()->discord->is_enabled): ?>
                    <div class="mt-2">
                        <a href="<?= url('login/discord-initiate') ?>" class="btn btn-block login-social-btn">
                            <img src="<?= ASSETS_FULL_URL . 'images/discord.svg' ?>" class="mr-1" />
                            <?= l('login.discord') ?>
                        </a>
                    </div>
                <?php endif ?>
                <?php if(settings()->linkedin->is_enabled): ?>
                    <div class="mt-2">
                        <a href="<?= url('login/linkedin-initiate') ?>" class="btn btn-block login-social-btn">
                            <img src="<?= ASSETS_FULL_URL . 'images/linkedin.svg' ?>" class="mr-1" />
                            <?= l('login.linkedin') ?>
                        </a>
                    </div>
                <?php endif ?>
                <?php if(settings()->microsoft->is_enabled): ?>
                    <div class="mt-2">
                        <a href="<?= url('login/microsoft-initiate') ?>" class="btn btn-block login-social-btn">
                            <img src="<?= ASSETS_FULL_URL . 'images/microsoft.svg' ?>" class="mr-1" />
                            <?= l('login.microsoft') ?>
                        </a>
                    </div>
                <?php endif ?>
            </div>
        <?php endif ?>
    </form>

    <?php if(settings()->users->register_is_enabled): ?>
        <div class="mt-5 text-center login-footer-link">
            <?= sprintf(l('login.register'), '<a href="' . url('register' . $data->redirect_append) . '" class="font-weight-bold">' . l('login.register_help') . '</a>') ?></a>
        </div>
    <?php endif ?>
</div>

<?php ob_start() ?>
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "BreadcrumbList",
            "itemListElement": [
                {
                    "@type": "ListItem",
                    "position": 1,
                    "name": "<?= l('index.title') ?>",
                    "item": "<?= url() ?>"
                },
                {
                    "@type": "ListItem",
                    "position": 2,
                    "name": "<?= l('login.title') ?>",
                    "item": "<?= url('login') ?>"
                }
            ]
        }
    </script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
