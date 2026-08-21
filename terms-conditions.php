<?php
include(__DIR__ . '/include/autoloader.php');

if (!isset($general_setting['siteurl'])) {
    $general_setting['siteurl'] = './';
}

$terms_title = 'Terms & Conditions';
$terms_content = <<<HTML
<h1>Terms and Conditions</h1>

<p><strong>Last Updated: August 20, 2026</strong></p>

<p>Welcome to Missing-USA.com. By accessing or using this website, you agree to be bound by these Terms and Conditions. If you do not agree with any part of these terms, please do not use our website.</p>

<h2>1. About Missing-USA.com</h2>

<p>Missing-USA.com is an informational website dedicated to sharing news and publicly available information related to missing children and missing persons in the United States.</p>

<p>Our goal is to help increase public awareness by collecting and displaying relevant news and information from various publicly available sources.</p>

<h2>2. Content and RSS Sources</h2>

<p>Some of the news, articles, headlines, images, and information displayed on Missing-USA.com may be automatically collected or republished through RSS feeds and other publicly available content sources.</p>

<p>Content originating from third-party sources remains the property of its respective owners, publishers, authors, or copyright holders.</p>

<p>Missing-USA.com does not claim ownership of third-party content unless explicitly stated otherwise.</p>

<h2>3. Accuracy of Information</h2>

<p>While we make reasonable efforts to provide accurate and relevant information, Missing-USA.com cannot guarantee that all information published on the website is complete, accurate, current, or free from errors.</p>

<p>Information may originate from third-party news organizations, public sources, RSS feeds, government agencies, law enforcement agencies, or other publishers.</p>

<p>Missing-USA.com is not responsible for inaccuracies, outdated information, errors, omissions, or changes in information provided by third-party sources.</p>

<h2>4. No Responsibility for Third-Party Content</h2>

<p>Missing-USA.com does not independently verify every article, report, image, statement, or piece of information obtained through RSS feeds or third-party sources.</p>

<p>The original publisher or source is responsible for the accuracy and legality of its published content. Any opinions expressed in third-party articles belong solely to the original authors or publishers and do not necessarily reflect the views of Missing-USA.com.</p>

<h2>5. Missing Persons Information</h2>

<p>The information published on Missing-USA.com is provided for general informational and awareness purposes only.</p>

<p>Information regarding missing children or missing persons may change as investigations develop. A person listed as missing may later be found, located, or have their case status updated.</p>

<p>Users should not rely solely on information published on this website when making decisions or taking action.</p>

<p>For emergencies or information related to an active missing persons case, please contact local law enforcement or the appropriate government agency.</p>

<h2>6. No Law Enforcement or Government Affiliation</h2>

<p>Missing-USA.com is an independent website and is not affiliated with, endorsed by, sponsored by, or officially connected to any law enforcement agency, government agency, missing persons organization, or emergency service unless explicitly stated.</p>

<h2>7. External Links</h2>

<p>Our website may contain links to third-party websites, news sources, government agencies, or other external resources.</p>

<p>Missing-USA.com has no control over the content, privacy policies, availability, or practices of third-party websites and is not responsible for any information or services provided by those websites.</p>

<h2>8. Copyright and Content Removal</h2>

<p>We respect the intellectual property rights of others. If you are a copyright owner, content owner, publisher, or authorized representative and believe that content displayed on Missing-USA.com infringes your rights or should be removed, please contact us.</p>

<p>Please provide sufficient information to identify the content in question and your relationship to the copyrighted or original material.</p>

<p>We will review legitimate removal requests and take appropriate action when necessary.</p>

<h2>9. Disclaimer</h2>

<p>All information on Missing-USA.com is provided on an "as is" and "as available" basis.</p>

<p>Missing-USA.com makes no warranties or guarantees regarding the accuracy, reliability, completeness, availability, or suitability of the information provided on this website.</p>

<p>To the fullest extent permitted by applicable law, Missing-USA.com shall not be held liable for any direct, indirect, incidental, consequential, or other damages resulting from the use of, or inability to use, this website or information obtained through third-party sources.</p>

<h2>10. Changes to These Terms</h2>

<p>We may update or modify these Terms and Conditions at any time without prior notice. Any changes will become effective immediately upon publication on this page.</p>

<p>We encourage users to review this page periodically to stay informed about any updates.</p>

<h2>11. Contact Us</h2>

<p>If you have questions, concerns, copyright requests, correction requests, or would like to request the removal of content, please contact us at:</p>

<p><strong>Email:</strong> contact@missing-usa.com</p>
HTML;

$smarty->assign('is_page', 1);
$smarty->assign('page_title', $terms_title);
$smarty->assign('page_content', $terms_content);
$smarty->assign('seo_title', $terms_title . ' - ' . $general_setting['seo_title']);
$smarty->assign('seo_keywords', 'terms and conditions, missing persons, missing children, site policy, legal notice');
$smarty->assign('seo_description', 'Terms and conditions for Missing USA, a website dedicated to missing children and missing persons awareness.');

$smarty->display('terms-conditions.html');
?>