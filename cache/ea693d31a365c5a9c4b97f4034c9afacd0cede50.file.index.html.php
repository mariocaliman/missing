<?php /* Smarty version Smarty-3.1.21-dev, created on 2026-08-20 03:55:03
         compiled from "/home/mariocaliman/Documentos/rss-news-v4.0.0/rss_news_4.0.0/rss-news/themes/default/index.html" */ ?>
<?php /*%%SmartyHeaderCode:1400316386a867a977ffc08-75155604%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'ea693d31a365c5a9c4b97f4034c9afacd0cede50' => 
    array (
      0 => '/home/mariocaliman/Documentos/rss-news-v4.0.0/rss_news_4.0.0/rss-news/themes/default/index.html',
      1 => 1787187436,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '1400316386a867a977ffc08-75155604',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'categories' => 0,
    'theme_home_category_news_number' => 0,
    'category_news' => 0,
    'news' => 0,
    'theme_allow_lazyload' => 0,
    'content_ad' => 0,
    'latest_home' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.21-dev',
  'unifunc' => 'content_6a867a97895df7_01262406',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_6a867a97895df7_01262406')) {function content_6a867a97895df7_01262406($_smarty_tpl) {?><?php if (!is_callable('smarty_modifier_news_in_category')) include '/home/mariocaliman/Documentos/rss-news-v4.0.0/rss_news_4.0.0/rss-news/include/smarty/plugins/modifier.news_in_category.php';
if (!is_callable('smarty_modifier_article_thumbnail')) include '/home/mariocaliman/Documentos/rss-news-v4.0.0/rss_news_4.0.0/rss-news/include/smarty/plugins/modifier.article_thumbnail.php';
if (!is_callable('smarty_modifier_html_decode')) include '/home/mariocaliman/Documentos/rss-news-v4.0.0/rss_news_4.0.0/rss-news/include/smarty/plugins/modifier.html_decode.php';
if (!is_callable('smarty_modifier_slug')) include '/home/mariocaliman/Documentos/rss-news-v4.0.0/rss_news_4.0.0/rss-news/include/smarty/plugins/modifier.slug.php';
if (!is_callable('smarty_modifier_get_since')) include '/home/mariocaliman/Documentos/rss-news-v4.0.0/rss_news_4.0.0/rss-news/include/smarty/plugins/modifier.get_since.php';
if (!is_callable('smarty_modifier_get_category')) include '/home/mariocaliman/Documentos/rss-news-v4.0.0/rss_news_4.0.0/rss-news/include/smarty/plugins/modifier.get_category.php';
if (!is_callable('smarty_modifier_get_source')) include '/home/mariocaliman/Documentos/rss-news-v4.0.0/rss_news_4.0.0/rss-news/include/smarty/plugins/modifier.get_source.php';
if (!is_callable('smarty_modifier_truncate')) include '/home/mariocaliman/Documentos/rss-news-v4.0.0/rss_news_4.0.0/rss-news/include/smarty/plugins/modifier.truncate.php';
?><!-- HomePage HTML -->
<?php echo $_smarty_tpl->getSubTemplate ("header.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, null, array(), 0);?>

<div class="content">
<div class="row">
<div class="col-md-8">
<!-- Homepage Categories Loop -->
<?php $_smarty_tpl->tpl_vars["home_sections"] = new Smarty_variable(0, null, 0);?>
<?php if (isset($_smarty_tpl->tpl_vars['smarty']->value['section']['x'])) unset($_smarty_tpl->tpl_vars['smarty']->value['section']['x']);
$_smarty_tpl->tpl_vars['smarty']->value['section']['x']['name'] = 'x';
$_smarty_tpl->tpl_vars['smarty']->value['section']['x']['loop'] = is_array($_loop=$_smarty_tpl->tpl_vars['categories']->value) ? count($_loop) : max(0, (int) $_loop); unset($_loop);
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
<?php if ($_smarty_tpl->tpl_vars['categories']->value[$_smarty_tpl->getVariable('smarty')->value['section']['x']['index']]['index_view']==1) {?>
<?php $_smarty_tpl->tpl_vars["category_news"] = new Smarty_variable(smarty_modifier_news_in_category($_smarty_tpl->tpl_vars['categories']->value[$_smarty_tpl->getVariable('smarty')->value['section']['x']['index']]['id'],$_smarty_tpl->tpl_vars['theme_home_category_news_number']->value), null, 0);?>
<?php if ($_smarty_tpl->tpl_vars['category_news']->value!=0) {?>
<?php $_smarty_tpl->tpl_vars["home_sections"] = new Smarty_variable(1, null, 0);?>
<div class="section-box">
<!-- Category Title -->
<h4><?php echo $_smarty_tpl->tpl_vars['categories']->value[$_smarty_tpl->getVariable('smarty')->value['section']['x']['index']]['category'];?>
</h4>
<!-- Category News Loop -->
<?php  $_smarty_tpl->tpl_vars['news'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['news']->_loop = false;
 $_smarty_tpl->tpl_vars['id'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['category_news']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
 $_smarty_tpl->tpl_vars['news']->index=-1;
foreach ($_from as $_smarty_tpl->tpl_vars['news']->key => $_smarty_tpl->tpl_vars['news']->value) {
$_smarty_tpl->tpl_vars['news']->_loop = true;
 $_smarty_tpl->tpl_vars['id']->value = $_smarty_tpl->tpl_vars['news']->key;
 $_smarty_tpl->tpl_vars['news']->index++;
 $_smarty_tpl->tpl_vars['news']->first = $_smarty_tpl->tpl_vars['news']->index === 0;
 $_smarty_tpl->tpl_vars['smarty']->value['foreach']['x']['first'] = $_smarty_tpl->tpl_vars['news']->first;
?>
<!-- First Article Of Category -->
<?php if ($_smarty_tpl->getVariable('smarty')->value['foreach']['x']['first']) {?>
<div class="first-section-news">
<div class="row">
<div class="col-md-4 col-xs-3">
<!-- Article Thumbnail -->
<?php echo smarty_modifier_article_thumbnail($_smarty_tpl->tpl_vars['news']->value['thumbnail'],$_smarty_tpl->tpl_vars['news']->value['source_id'],"max-width",$_smarty_tpl->tpl_vars['theme_allow_lazyload']->value);?>

</div>
<div class="col-md-8 col-xs-9">
<!-- Article Title -->
<a href="./news/<?php echo $_smarty_tpl->tpl_vars['news']->value['id'];?>
/<?php echo smarty_modifier_slug(smarty_modifier_html_decode($_smarty_tpl->tpl_vars['news']->value['title']));?>
"><?php echo smarty_modifier_html_decode($_smarty_tpl->tpl_vars['news']->value['title']);?>
</a>
<!-- Article Info (date,category,source,hits) -->
<div class="article-meta hidden-xs">
<span><i class="fa fa-clock-o"></i><?php echo smarty_modifier_get_since($_smarty_tpl->tpl_vars['news']->value['datetime']);?>
</span>
<span><i class="fa fa-reorder"></i><a href="./category/<?php echo $_smarty_tpl->tpl_vars['news']->value['category_id'];?>
/<?php echo smarty_modifier_slug(smarty_modifier_html_decode(smarty_modifier_get_category($_smarty_tpl->tpl_vars['news']->value['category_id'])));?>
"><?php echo smarty_modifier_get_category($_smarty_tpl->tpl_vars['news']->value['category_id']);?>
</a></span>
<span><i class="fa fa-rss"></i><a href="./source/<?php echo $_smarty_tpl->tpl_vars['news']->value['source_id'];?>
/<?php echo smarty_modifier_slug(smarty_modifier_html_decode(smarty_modifier_get_source($_smarty_tpl->tpl_vars['news']->value['source_id'])));?>
"><?php echo smarty_modifier_get_source($_smarty_tpl->tpl_vars['news']->value['source_id']);?>
</a></span>
<span><i class="fa fa-bar-chart"></i> <?php echo $_smarty_tpl->tpl_vars['news']->value['hits'];?>
</span>
</div>
<!-- Article Excerpt -->
<p class="hidden-xs"><?php echo smarty_modifier_truncate(preg_replace('!<[^>]*?>!', ' ', smarty_modifier_html_decode($_smarty_tpl->tpl_vars['news']->value['details'])),150);?>
</p>
</div>
</div>
</div>
<!-- End of First Article Of Category -->
<?php } else { ?>
<!-- Rest Of News Loop -->
<div class="rest-section-news">
<a href="./news/<?php echo $_smarty_tpl->tpl_vars['news']->value['id'];?>
/<?php echo smarty_modifier_slug(smarty_modifier_html_decode($_smarty_tpl->tpl_vars['news']->value['title']));?>
"><?php echo smarty_modifier_html_decode($_smarty_tpl->tpl_vars['news']->value['title']);?>
</a>
</div>
<?php }?>
<?php } ?>
<!-- End Of Category News Loop -->
</div>
<!-- Start Content Advertisement -->
<?php if ($_smarty_tpl->getVariable('smarty')->value['section']['x']['first']) {?>
<div class="content-ad"><?php echo $_smarty_tpl->tpl_vars['content_ad']->value;?>
</div>
<?php }?>
<!-- End Content Advertisement -->
<?php }?>
<?php }?>
<?php endfor; endif; ?>
<!-- End Of Homepage Categories Loop -->
<div class="section-box">
<h4>Latest News</h4>
<?php if ($_smarty_tpl->tpl_vars['latest_home']->value!=0) {?>
<?php  $_smarty_tpl->tpl_vars['news'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['news']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['latest_home']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['news']->key => $_smarty_tpl->tpl_vars['news']->value) {
$_smarty_tpl->tpl_vars['news']->_loop = true;
?>
<div class="rest-section-news">
<a href="./news/<?php echo $_smarty_tpl->tpl_vars['news']->value['id'];?>
/<?php echo smarty_modifier_slug(smarty_modifier_html_decode($_smarty_tpl->tpl_vars['news']->value['title']));?>
"><?php echo smarty_modifier_html_decode($_smarty_tpl->tpl_vars['news']->value['title']);?>
</a>
</div>
<?php } ?>
<?php } else { ?>
<div class="alert alert-warning" style="margin:0;">No published news found yet.</div>
<?php }?>
</div>
</div>
<div class="col-md-4">
<!-- Include the SideBar -->
<?php echo $_smarty_tpl->getSubTemplate ("sidebar.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, null, array(), 0);?>

</div>
</div>
</div>
<?php echo $_smarty_tpl->getSubTemplate ("footer.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, null, array(), 0);?>

<!-- End HomePage HTML --><?php }} ?>
