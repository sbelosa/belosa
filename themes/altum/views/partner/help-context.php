<?php defined('ALTUMCODE') || die();
if(!fcc_help_enabled())return;
$helpId=fcc_help_context_topic((string)\Altum\Router::$controller_key,(string)(\Altum\Router::$params[0]??'today'),is_string($_GET['type']??null)?$_GET['type']:'',is_string($_GET['view']??null)?$_GET['view']:'');
if(!$helpId)return;
$helpUid=(int)($this->user->user_id??0);
if($helpUid<=0||isset(fcc_help_state($helpUid)['read'][$helpId]))return;
$helpArticle=fcc_help_article($helpId,fcc_help_locale());if(!$helpArticle)return;
?>
<aside class="fh-context" aria-label="<?= fcc_partner_h(fcc_help_t('title')) ?>"><p><?= fcc_partner_h(fcc_help_t('title')) ?></p><a href="<?= fcc_help_url($helpId) ?>"><?= fcc_partner_h($helpArticle['title']) ?></a></aside>
