<?php
include(__DIR__ . '/include/autoloader.php');

if (!isset($general_setting['siteurl'])) {
    $general_setting['siteurl'] = './';
}

$page_title = 'Thank You for Donating';
$page_content = <<<'HTML'
<h1>Thank You for Helping Keep Missing USA Alive</h1>

<div class="donation-thanks-hero">
  <p class="donation-thanks-lead">Your support means more than a payment. It means a child’s name stays visible, a family’s hope stays alive, and a case continues to reach the people who need to see it.</p>
  <p>By donating, you are helping us cover hosting, editorial work, verification, design improvements, and the AI tools that allow us to organize information faster and present it more clearly.</p>
</div>

<h2>What Your Gift Supports</h2>
<ul>
  <li><strong>Editorial work:</strong> reviewing and presenting cases with care.</li>
  <li><strong>Hosting and maintenance:</strong> keeping the site online and available.</li>
  <li><strong>Tools and technology:</strong> supporting the systems we use to improve speed, structure, and accuracy.</li>
  <li><strong>Awareness:</strong> helping more families, friends, and communities find the information they need.</li>
</ul>

<h2>Why It Matters</h2>
<p>Missing persons cases can fade quickly when the public stops seeing them. Your donation helps us continue building a place where those stories remain visible, searchable, and easier to share.</p>
<p>That visibility can make a real difference for a family waiting, searching, and hoping for answers.</p>

<h2>From All of Us</h2>
<p>Thank you for standing with Missing USA. Whether your gift was large or small, it became part of something important: helping keep attention on people who should never be forgotten.</p>
<p><a class="btn btn-danger" href="./">Return to Home</a></p>
HTML;

$smarty->assign('is_page', 1);
$smarty->assign('page_title', $page_title);
$smarty->assign('page_content', $page_content);
$smarty->assign('seo_title', $page_title . ' - ' . $general_setting['seo_title']);
$smarty->assign('seo_keywords', 'donate, thank you, missing usa, missing persons, support, awareness');
$smarty->assign('seo_description', 'Thank you for supporting Missing USA and helping keep missing persons cases visible and accessible.');
$smarty->assign('canonical_url', rtrim($general_setting['siteurl'], '/') . '/thank-you-for-donating');

$smarty->display('thank-you-for-donating.html');
?>