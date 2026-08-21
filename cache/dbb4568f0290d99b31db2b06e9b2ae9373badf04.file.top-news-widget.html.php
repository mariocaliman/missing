<?php /* Smarty version Smarty-3.1.21-dev, created on 2026-08-20 03:55:03
         compiled from "/home/mariocaliman/Documentos/rss-news-v4.0.0/rss_news_4.0.0/rss-news/themes/default/top-news-widget.html" */ ?>
<?php /*%%SmartyHeaderCode:10586630376a867a978e2c28-42210222%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'dbb4568f0290d99b31db2b06e9b2ae9373badf04' => 
    array (
      0 => '/home/mariocaliman/Documentos/rss-news-v4.0.0/rss_news_4.0.0/rss-news/themes/default/top-news-widget.html',
      1 => 1434296108,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '10586630376a867a978e2c28-42210222',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'top' => 0,
    'theme_allow_lazyload' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.21-dev',
  'unifunc' => 'content_6a867a978f29c9_45728231',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_6a867a978f29c9_45728231')) {function content_6a867a978f29c9_45728231($_smarty_tpl) {?><?php if (!is_callable('smarty_modifier_article_thumbnail')) include '/home/mariocaliman/Documentos/rss-news-v4.0.0/rss_news_4.0.0/rss-news/include/smarty/plugins/modifier.article_thumbnail.php';
if (!is_callable('smarty_modifier_html_decode')) include '/home/mariocaliman/Documentos/rss-news-v4.0.0/rss_news_4.0.0/rss-news/include/smarty/plugins/modifier.html_decode.php';
if (!is_callable('smarty_modifier_slug')) include '/home/mariocaliman/Documentos/rss-news-v4.0.0/rss_news_4.0.0/rss-news/include/smarty/plugins/modifier.slug.php';
if (!is_callable('smarty_modifier_truncate')) include '/home/mariocaliman/Documentos/rss-news-v4.0.0/rss_news_4.0.0/rss-news/include/smarty/plugins/modifier.truncate.php';
?><!-- Start Top News Widget HTML -->
<?php if ($_smarty_tpl->tpl_vars['top']->value!=0) {?>
<div class="widget top-hits-news">
<h4>Top News</h4>
<ul>
<?php if (isset($_smarty_tpl->tpl_vars['smarty']->value['section']['x'])) unset($_smarty_tpl->tpl_vars['smarty']->value['section']['x']);
$_smarty_tpl->tpl_vars['smarty']->value['section']['x']['name'] = 'x';
$_smarty_tpl->tpl_vars['smarty']->value['section']['x']['loop'] = is_array($_loop=$_smarty_tpl->tpl_vars['top']->value) ? count($_loop) : max(0, (int) $_loop); unset($_loop);
$_smarty_tpl->tpl_vars['smarty']->value['section']['x']['show'] = true;
$_smarty_tpl->tpl_vars['smarty']->value['section']['x']['max'] = $_smarty_tpl->tpl_vars['smarty']->value['section']['x']['loop'];
$_smarty_tpl->tpl_vars['smarty']->value['section']['x']['step'] = 1;
$_smarty_tpl->tpl_vars['smarty']->value['section']['x']['start'] = $_smarty_tpl->tpl_vars['smarty']->value['section']['x']['step'] > 0 ? 0 : $_smarty_tpl->tpl_vars['smarty']->value['section']['x']['loop']-1;
if ($_smarty_tpl->tpl_vars['smarty']->value['section']['x']['show']) {
    $_smarty_tpl->tpl_vars['smarty']->value['section']['x']['total'] = $_smarty_tpl->tpl_vars['smarty']->value['section']['x']['loop'];
    if ($_smarty_tpl->tpl_vars['smarty']->value['section']['x']['total'] == 0)
        $_smarty_tpl->tpl_vars['smarty']->value['section']['x']['show'] = false;
} else
    $_smarty_tpl->tpl_vars['smarty']->value['section']['x']['total'] = 0;
if ($_smarty_tpl->tpl_vars['smarty']->value['section']['x']['show']):

            for ($_smarty_tpl->tpl_vars['smarty']->value['section']['x']['index'] = $_smarty_tpl->tpl_vars['smarty']->value['section']['x']['start'], $_smarty_tpl->tpl_vars['smarty']->value['section']['x']['iteration'] = 1;
                 $_smarty_tpl->tpl_vars['smarty']->value['section']['x']['iteration'] <= $_smarty_tpl->tpl_vars['smarty']->value['section']['x']['total'];
                 $_smarty_tpl->tpl_vars['smarty']->value['section']['x']['index'] += $_smarty_tpl->tpl_vars['smarty']->value['section']['x']['step'], $_smarty_tpl->tpl_vars['smarty']->value['section']['x']['iteration']++):
$_smarty_tpl->tpl_vars['smarty']->value['section']['x']['rownum'] = $_smarty_tpl->tpl_vars['smarty']->value['section']['x']['iteration'];
$_smarty_tpl->tpl_vars['smarty']->value['section']['x']['index_prev'] = $_smarty_tpl->tpl_vars['smarty']->value['section']['x']['index'] - $_smarty_tpl->tpl_vars['smarty']->value['section']['x']['step'];
$_smarty_tpl->tpl_vars['smarty']->value['section']['x']['index_next'] = $_smarty_tpl->tpl_vars['smarty']->value['section']['x']['index'] + $_smarty_tpl->tpl_vars['smarty']->value['section']['x']['step'];
$_smarty_tpl->tpl_vars['smarty']->value['section']['x']['first']      = ($_smarty_tpl->tpl_vars['smarty']->value['section']['x']['iteration'] == 1);
$_smarty_tpl->tpl_vars['smarty']->value['section']['x']['last']       = ($_smarty_tpl->tpl_vars['smarty']->value['section']['x']['iteration'] == $_smarty_tpl->tpl_vars['smarty']->value['section']['x']['total']);
?>
<li>
<div class="col-xs-3">
<!-- Aticle Thumbnail -->
<?php echo smarty_modifier_article_thumbnail($_smarty_tpl->tpl_vars['top']->value[$_smarty_tpl->getVariable('smarty')->value['section']['x']['index']]['thumbnail'],$_smarty_tpl->tpl_vars['top']->value[$_smarty_tpl->getVariable('smarty')->value['section']['x']['index']]['source_id'],false,$_smarty_tpl->tpl_vars['theme_allow_lazyload']->value);?>
 
</div>
<div class="col-xs-9">
<!-- Aticle Title & Link -->
<a href="./news/<?php echo $_smarty_tpl->tpl_vars['top']->value[$_smarty_tpl->getVariable('smarty')->value['section']['x']['index']]['id'];?>
/<?php echo smarty_modifier_slug(smarty_modifier_html_decode($_smarty_tpl->tpl_vars['top']->value[$_smarty_tpl->getVariable('smarty')->value['section']['x']['index']]['title']));?>
"><?php echo smarty_modifier_html_decode($_smarty_tpl->tpl_vars['top']->value[$_smarty_tpl->getVariable('smarty')->value['section']['x']['index']]['title']);?>
</a>
<!-- Aticle Excerpt -->
<p class="visible-sm"><?php echo smarty_modifier_truncate(preg_replace('!<[^>]*?>!', ' ', smarty_modifier_html_decode($_smarty_tpl->tpl_vars['top']->value[$_smarty_tpl->getVariable('smarty')->value['section']['x']['index']]['details'])),150);?>
</p>
</div>
</li>
<?php endfor; endif; ?>
</ul>
</div>
<?php }?>
<!-- End Top News Widget HTML -->
<?php }} ?>
