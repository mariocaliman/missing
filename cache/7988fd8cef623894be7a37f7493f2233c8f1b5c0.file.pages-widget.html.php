<?php /* Smarty version Smarty-3.1.21-dev, created on 2026-08-20 03:55:03
         compiled from "/home/mariocaliman/Documentos/rss-news-v4.0.0/rss_news_4.0.0/rss-news/themes/default/pages-widget.html" */ ?>
<?php /*%%SmartyHeaderCode:18580044986a867a978ff7f5-77826016%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '7988fd8cef623894be7a37f7493f2233c8f1b5c0' => 
    array (
      0 => '/home/mariocaliman/Documentos/rss-news-v4.0.0/rss_news_4.0.0/rss-news/themes/default/pages-widget.html',
      1 => 1434296126,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '18580044986a867a978ff7f5-77826016',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'pages' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.21-dev',
  'unifunc' => 'content_6a867a9790ada5_92588307',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_6a867a9790ada5_92588307')) {function content_6a867a9790ada5_92588307($_smarty_tpl) {?><?php if (!is_callable('smarty_modifier_html_decode')) include '/home/mariocaliman/Documentos/rss-news-v4.0.0/rss_news_4.0.0/rss-news/include/smarty/plugins/modifier.html_decode.php';
if (!is_callable('smarty_modifier_slug')) include '/home/mariocaliman/Documentos/rss-news-v4.0.0/rss_news_4.0.0/rss-news/include/smarty/plugins/modifier.slug.php';
?><!-- Pages Widget -->
<?php if ($_smarty_tpl->tpl_vars['pages']->value!=0) {?>
<div class="widget links">
<h4>Pages</h4>
<ul>
<?php if (isset($_smarty_tpl->tpl_vars['smarty']->value['section']['x'])) unset($_smarty_tpl->tpl_vars['smarty']->value['section']['x']);
$_smarty_tpl->tpl_vars['smarty']->value['section']['x']['name'] = 'x';
$_smarty_tpl->tpl_vars['smarty']->value['section']['x']['loop'] = is_array($_loop=$_smarty_tpl->tpl_vars['pages']->value) ? count($_loop) : max(0, (int) $_loop); unset($_loop);
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
<a href="./page/<?php echo $_smarty_tpl->tpl_vars['pages']->value[$_smarty_tpl->getVariable('smarty')->value['section']['x']['index']]['id'];?>
/<?php echo smarty_modifier_slug(smarty_modifier_html_decode($_smarty_tpl->tpl_vars['pages']->value[$_smarty_tpl->getVariable('smarty')->value['section']['x']['index']]['title']));?>
"><i class="fa fa-file"></i><?php echo smarty_modifier_html_decode($_smarty_tpl->tpl_vars['pages']->value[$_smarty_tpl->getVariable('smarty')->value['section']['x']['index']]['title']);?>
</a></li>
<?php endfor; endif; ?>
</ul>
</div>
<?php }?>
<?php }} ?>
