<?php /* Smarty version Smarty-3.1.21-dev, created on 2026-08-20 03:55:03
         compiled from "/home/mariocaliman/Documentos/rss-news-v4.0.0/rss_news_4.0.0/rss-news/themes/default/ad-widget.html" */ ?>
<?php /*%%SmartyHeaderCode:14540297316a867a978f7ef7-38815858%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '8f1c47a4b01542fb66d1428408674229c00702eb' => 
    array (
      0 => '/home/mariocaliman/Documentos/rss-news-v4.0.0/rss_news_4.0.0/rss-news/themes/default/ad-widget.html',
      1 => 1434215420,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '14540297316a867a978f7ef7-38815858',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'widget_ad' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.21-dev',
  'unifunc' => 'content_6a867a978fc423_72048487',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_6a867a978fc423_72048487')) {function content_6a867a978fc423_72048487($_smarty_tpl) {?><!-- SideBar Advertisement Widget -->
<?php if (!empty($_smarty_tpl->tpl_vars['widget_ad']->value)) {?>
<div class="widget">
<?php echo $_smarty_tpl->tpl_vars['widget_ad']->value;?>

</div>
<?php }?>
<?php }} ?>
