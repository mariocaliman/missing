<?php /* Smarty version Smarty-3.1.21-dev, created on 2026-08-20 03:55:03
         compiled from "/home/mariocaliman/Documentos/rss-news-v4.0.0/rss_news_4.0.0/rss-news/themes/default/search-form-widget.html" */ ?>
<?php /*%%SmartyHeaderCode:4576548826a867a978dbc11-11429421%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '571db9827afcaa981d5dfb7b4c4894051573c464' => 
    array (
      0 => '/home/mariocaliman/Documentos/rss-news-v4.0.0/rss_news_4.0.0/rss-news/themes/default/search-form-widget.html',
      1 => 1434531470,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '4576548826a867a978dbc11-11429421',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'is_search' => 0,
    'q' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.21-dev',
  'unifunc' => 'content_6a867a978df407_53924547',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_6a867a978df407_53924547')) {function content_6a867a978df407_53924547($_smarty_tpl) {?><!-- Search Form Widget -->
<div class="widget search-form">
	<form method="GET" action="./search/">
		<div class="input-group">
		  <input type="text" name="q" class="form-control" placeholder="Search" <?php if (isset($_smarty_tpl->tpl_vars['is_search']->value)&&$_smarty_tpl->tpl_vars['is_search']->value==1) {?>value="<?php echo $_smarty_tpl->tpl_vars['q']->value;?>
"<?php }?> />
		  <span class="input-group-addon"><button type="submit" class="btn-link"><span class="fa fa-search"></span></button></span>
		</div>
	</form>
</div>
<?php }} ?>
