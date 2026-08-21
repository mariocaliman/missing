<?php
include(__DIR__ . '/include/autoloader.php');

if (!isset($general_setting['siteurl'])) {
    $general_setting['siteurl'] = './';
}

$about_title = 'About Us';
$about_content = <<<HTML
<h1>About Missing USA</h1>

<p><strong>Missing USA</strong> is an independent awareness platform focused on missing children and missing adults across the United States.</p>

<p>Our purpose is simple: keep cases visible, organize verified information, and help communities share the right details in the moments that matter most.</p>

<h2>What We Do</h2>

<p>We collect and publish public information from trusted sources such as news outlets, official alerts, and recognized organizations. We then structure that information into clear, searchable case pages so readers can quickly understand key facts and follow official references.</p>

<h2>Why This Work Matters</h2>

<p>Every missing persons report represents a real family living with uncertainty. As time passes, public attention can fade. Missing USA exists to help keep cases discoverable and to support responsible visibility through accurate sharing.</p>

<h2>Our Editorial Approach</h2>

<ul>
  <li>We prioritize clarity, context, and public usefulness.</li>
  <li>We avoid speculation and encourage readers to rely on official updates.</li>
  <li>We continuously improve article quality, including better structure and readability for case pages.</li>
  <li>We provide space for community participation through tip and support channels.</li>
</ul>

<h2>Important Notice</h2>

<p>Missing USA is <strong>not</strong> a law enforcement agency and is not a substitute for emergency services. If you have immediate or credible information about a case, contact local law enforcement or the official organization listed in the case page as soon as possible.</p>

<h2>How You Can Help</h2>

<ul>
  <li>Share case pages responsibly and without altering facts.</li>
  <li>Submit tips only when information is credible and specific.</li>
  <li>Report outdated or incorrect content so it can be reviewed quickly.</li>
</ul>

<h2>Contact</h2>

<p>For corrections, content concerns, support requests, or general inquiries, contact us at <strong>contact@missing-usa.com</strong>.</p>
HTML;

$smarty->assign('is_page', 1);
$smarty->assign('page_title', $about_title);
$smarty->assign('page_content', $about_content);
$smarty->assign('seo_title', $about_title . ' - ' . $general_setting['seo_title']);
$smarty->assign('seo_keywords', 'about missing usa, missing persons awareness, missing children, case visibility, community tips');
$smarty->assign('seo_description', 'Learn about Missing USA, our mission, editorial approach, and how communities can help keep missing persons cases visible.');

$smarty->display('about-us.html');
?>