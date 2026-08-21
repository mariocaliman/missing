<?php /* Smarty version Smarty-3.1.21-dev, created on 2026-08-20 03:55:03
         compiled from "/home/mariocaliman/Documentos/rss-news-v4.0.0/rss_news_4.0.0/rss-news/themes/default/sidebar.html" */ ?>
<?php /*%%SmartyHeaderCode:17574424546a867a978d2964-86541202%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '0c5794c8eb29328560d9c5249c50600c4963c222' => 
    array (
      0 => '/home/mariocaliman/Documentos/rss-news-v4.0.0/rss_news_4.0.0/rss-news/themes/default/sidebar.html',
      1 => 1434214456,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '17574424546a867a978d2964-86541202',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.21-dev',
  'unifunc' => 'content_6a867a978d7c19_71483905',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_6a867a978d7c19_71483905')) {function content_6a867a978d7c19_71483905($_smarty_tpl) {?><!-- Search Widget -->
<?php echo $_smarty_tpl->getSubTemplate ("search-form-widget.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, null, array(), 0);?>

<!-- Top News Widget -->
<?php echo $_smarty_tpl->getSubTemplate ("top-news-widget.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, null, array(), 0);?>

<!-- SideBar Advertisement Widget -->
<?php echo $_smarty_tpl->getSubTemplate ("ad-widget.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, null, array(), 0);?>

<!-- Pages Widget -->
<?php echo $_smarty_tpl->getSubTemplate ("pages-widget.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, null, array(), 0);?>

<!-- Links Widget -->
<?php echo $_smarty_tpl->getSubTemplate ("links-widget.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, null, array(), 0);?>

<?php }} ?>
