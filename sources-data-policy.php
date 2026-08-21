<?php
include(__DIR__ . '/include/autoloader.php');

if (!isset($general_setting['siteurl'])) {
    $general_setting['siteurl'] = './';
}

$policy_title = 'Sources & Data Policy';
$policy_content = <<<HTML
<h1>Sources &amp; Data Policy</h1>

<p><strong>Last Updated: August 21, 2026</strong></p>

<p>Missing USA publishes publicly available information from official and reputable sources. Information may change as investigations progress. Readers should verify critical information with the appropriate law enforcement agency.</p>

<h2>Where Our Data Comes From</h2>

<ul>
  <li>Official missing persons organizations and public alerts.</li>
  <li>Law enforcement publications and agency updates.</li>
  <li>Established news outlets and publicly available RSS feeds.</li>
  <li>Publicly accessible case references and source pages.</li>
</ul>

<h2>How Information Is Published</h2>

<p>Some content is imported from source feeds and organized into case pages to improve readability and discovery. We may reformat text structure for clarity while preserving the core reported facts from source material.</p>

<h2>What Readers Should Know</h2>

<ul>
  <li>Case details can change quickly as investigations evolve.</li>
  <li>Older reports may contain outdated status information.</li>
  <li>Not all sources update at the same speed or with the same level of detail.</li>
  <li>Users should avoid speculation and rely on official channels for urgent decisions.</li>
</ul>

<h2>Verification Guidance</h2>

<p>Before sharing or acting on critical details, confirm the latest status with local law enforcement, the original source, or the official missing persons organization linked in the case.</p>

<h2>Corrections and Updates</h2>

<p>If you identify inaccurate, outdated, or sensitive information, contact us so the report can be reviewed promptly.</p>

<p><strong>Contact:</strong> contact@missing-usa.com</p>
HTML;

$smarty->assign('is_page', 1);
$smarty->assign('page_title', $policy_title);
$smarty->assign('page_content', $policy_content);
$smarty->assign('seo_title', $policy_title . ' - ' . $general_setting['seo_title']);
$smarty->assign('seo_keywords', 'sources policy, data policy, missing persons data, editorial policy, verification policy');
$smarty->assign('seo_description', 'Sources & Data Policy for Missing USA explaining where case data comes from and how readers should verify critical information.');

$smarty->display('sources-data-policy.html');
?>