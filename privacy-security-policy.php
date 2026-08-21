<?php
include(__DIR__ . '/include/autoloader.php');

if (!isset($general_setting['siteurl'])) {
    $general_setting['siteurl'] = './';
}

$privacy_title = 'Privacy and Security Policy';
$privacy_content = <<<HTML
<h1>Privacy and Security Policy</h1>

<p><strong>Last Updated: August 20, 2026</strong></p>

<p>At Missing-USA.com, we respect your privacy and are committed to protecting the information of our visitors. This Privacy and Security Policy explains how information may be collected, used, stored, and protected when you visit our website.</p>

<h2>1. Information We Collect</h2>

<p>Missing-USA.com may collect limited information automatically when you visit our website. This may include information such as your IP address, browser type, device type, operating system, pages visited, referring website, approximate geographic location, and date and time of access.</p>

<p>We may also receive information that you voluntarily provide to us, such as your email address and the contents of messages sent to our contact email.</p>

<h2>2. Use of Information</h2>

<p>Information collected through the website may be used to:</p>

<ul>
  <li>Operate and maintain Missing-USA.com;</li>
  <li>Improve website performance, content, and user experience;</li>
  <li>Analyze website traffic and usage;</li>
  <li>Respond to inquiries and requests;</li>
  <li>Detect, prevent, and investigate abuse, fraud, spam, or security issues;</li>
  <li>Maintain the security and functionality of our website.</li>
</ul>

<h2>3. Cookies and Similar Technologies</h2>

<p>Missing-USA.com may use cookies and similar technologies to improve website functionality, understand how visitors use the website, and provide relevant advertising.</p>

<h2>4. Google AdSense and Advertising</h2>

<p>Missing-USA.com uses <strong>Google AdSense</strong> to display advertisements on our website.</p>

<p>Google and its advertising partners may use cookies, web beacons, device identifiers, or similar technologies to collect information about visitors and provide, personalize, measure, and improve advertising.</p>

<p>Advertising partners may use information such as browsing activity, device information, approximate location, and interactions with advertisements to provide relevant advertising, subject to their own privacy policies and applicable laws.</p>

<p>Google may use the <strong>DoubleClick cookie</strong> and other technologies to help deliver and measure advertisements. Visitors may be able to manage their advertising preferences through Google's advertising settings and other available privacy controls.</p>

<p>For more information about how Google handles data in connection with advertising, please review Google's applicable privacy documentation.</p>

<h2>5. Third-Party Services</h2>

<p>Our website may use third-party services for advertising, analytics, hosting, security, content distribution, or other website functionality.</p>

<p>These third-party providers may collect or process information according to their own privacy policies. Missing-USA.com does not control the privacy practices of third-party services.</p>

<h2>6. Children's Privacy</h2>

<p>Missing-USA.com publishes information and news about missing children and missing persons. However, our website is not intended to collect personal information directly from children.</p>

<p>We do not knowingly request or collect personal information from children for the purpose of creating user accounts, marketing, or similar activities.</p>

<h2>7. Information About Missing Persons</h2>

<p>Information concerning missing children and missing persons may be obtained from publicly available sources, news organizations, RSS feeds, government agencies, law enforcement sources, and other third-party publishers.</p>

<p>Missing-USA.com does not claim ownership of personal information contained in third-party reports and does not independently verify every piece of information published through these sources.</p>

<p>If you believe that personal information displayed on our website is inaccurate, outdated, sensitive, or should no longer be publicly displayed, please contact us so that we can review the request.</p>

<h2>8. Data Security</h2>

<p>We take reasonable technical and organizational measures to help protect information associated with our website against unauthorized access, alteration, disclosure, or destruction.</p>

<p>However, no website, server, network, or electronic transmission can be guaranteed to be completely secure. Therefore, we cannot guarantee absolute security of information transmitted to or through our website.</p>

<h2>9. Data Retention</h2>

<p>We retain information only for as long as reasonably necessary for the purposes described in this Privacy and Security Policy, to operate the website, comply with legal obligations, resolve disputes, enforce our agreements, or protect the security of our services.</p>

<h2>10. Third-Party Links</h2>

<p>Missing-USA.com may contain links to external websites, including news organizations, government agencies, law enforcement resources, and other websites.</p>

<p>We are not responsible for the privacy, security, content, or practices of third-party websites. We encourage visitors to review the privacy policies of any external website they visit.</p>

<h2>11. Your Privacy Choices</h2>

<p>Depending on your location and applicable laws, you may have rights concerning your personal information, including rights to access, correct, delete, restrict, or object to certain processing of your information.</p>

<p>Requests concerning personal information or privacy may be submitted to us using the contact information below. We will review requests in accordance with applicable law.</p>

<h2>12. Changes to This Privacy Policy</h2>

<p>We may update or modify this Privacy and Security Policy from time to time to reflect changes to our website, services, advertising practices, technology, or applicable laws.</p>

<p>The updated version will be posted on this page with a revised "Last Updated" date.</p>

<h2>13. Contact Us</h2>

<p>If you have questions regarding privacy, security, personal data, advertising, or this Privacy and Security Policy, please contact us at:</p>

<p><strong>Email:</strong> contact@missing-usa.com</p>

<p>By using Missing-USA.com, you acknowledge that you have read and understood this Privacy and Security Policy.</p>
HTML;

$smarty->assign('is_page', 1);
$smarty->assign('page_title', $privacy_title);
$smarty->assign('page_content', $privacy_content);
$smarty->assign('seo_title', $privacy_title . ' - ' . $general_setting['seo_title']);
$smarty->assign('seo_keywords', 'privacy policy, security policy, ad sense, cookies, missing persons, missing children');
$smarty->assign('seo_description', 'Privacy and Security Policy for Missing USA, including cookies, advertising, and data protection practices.');

$smarty->display('privacy-security-policy.html');
?>