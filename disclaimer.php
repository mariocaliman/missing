<?php
include(__DIR__ . '/include/autoloader.php');

if (!isset($general_setting['siteurl'])) {
    $general_setting['siteurl'] = './';
}

$disclaimer_title = 'Disclaimer';
$disclaimer_content = <<<HTML
<h1>Disclaimer</h1>

<p><strong>Last Updated: August 21, 2026</strong></p>

<p>Missing USA is an independent informational platform focused on awareness of missing children and missing adults in the United States.</p>

<h2>Informational Use Only</h2>

<p>All content on this website is provided for general informational and public-awareness purposes only. It is not legal advice, investigative advice, emergency guidance, or official law-enforcement communication.</p>

<h2>No Law Enforcement Affiliation</h2>

<p>Missing USA is not a law enforcement agency and is not officially affiliated with police departments, sheriff offices, government agencies, or emergency services unless explicitly stated.</p>

<h2>Third-Party Content and Sources</h2>

<p>Some content may be sourced from public feeds, external publishers, and third-party websites. Original rights remain with their respective owners. We do not guarantee that third-party content is always complete, current, or error-free.</p>

<h2>No Guarantee of Accuracy</h2>

<p>While we strive to organize and present information responsibly, Missing USA makes no warranties regarding completeness, timeliness, reliability, or accuracy of any case details, links, images, or status updates.</p>

<h2>Case Status May Change</h2>

<p>Missing persons cases can change quickly. A person may be located, identified, or have status updates after publication. Always verify current information through official authorities and source organizations.</p>

<h2>Emergency and Tip Guidance</h2>

<p>If you have urgent or credible information about an active case, contact local law enforcement immediately. Do not rely solely on this website in emergency situations.</p>

<h2>Limitation of Liability</h2>

<p>To the fullest extent permitted by applicable law, Missing USA and its operators are not liable for direct, indirect, incidental, consequential, or special damages resulting from use of this website or reliance on any content.</p>

<h2>External Links</h2>

<p>This website may include links to third-party resources. We are not responsible for the content, availability, or practices of external websites.</p>

<h2>Contact</h2>

<p>For corrections, clarifications, or content concerns, contact us at <strong>contact@missing-usa.com</strong>.</p>
HTML;

$smarty->assign('is_page', 1);
$smarty->assign('page_title', $disclaimer_title);
$smarty->assign('page_content', $disclaimer_content);
$smarty->assign('seo_title', $disclaimer_title . ' - ' . $general_setting['seo_title']);
$smarty->assign('seo_keywords', 'disclaimer, legal notice, missing persons, missing children, content policy');
$smarty->assign('seo_description', 'Disclaimer for Missing USA, including informational-use terms, liability limits, and third-party content notices.');

$smarty->display('disclaimer.html');
?>