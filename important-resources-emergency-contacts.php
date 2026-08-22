<?php
include(__DIR__ . '/include/autoloader.php');

if (!isset($general_setting['siteurl'])) {
    $general_setting['siteurl'] = './';
}

$page_title = 'Important Resources and Emergency Contacts';
$page_content = <<<'HTML'
<h1>Important Resources and Emergency Contacts</h1>

<h2>Missing Persons, Missing Children and AMBER Alerts</h2>
<p>When someone goes missing, every minute can matter. This page provides important emergency contacts, official resources, and helpful links for families, friends, witnesses, and anyone who has information about a missing person or child.</p>
<p><strong>If someone is in immediate danger, call 911 immediately.</strong></p>

<h2>Emergency: Call 911</h2>
<p><strong>Phone: 911</strong></p>
<p>Call 911 immediately if:</p>
<ul>
  <li>A child has disappeared and may be in immediate danger.</li>
  <li>You believe someone has been abducted.</li>
  <li>You have just seen a missing person or missing child.</li>
  <li>You have information that could help locate someone in danger.</li>
  <li>There is an immediate threat to someone's safety.</li>
</ul>
<p><strong>Do not delay reporting a disappearance because you think you need to wait 24 hours.</strong> If you believe someone is missing or in danger, contact local law enforcement immediately.</p>

<h2>Report a Missing Person</h2>
<p>If a family member, friend, child, or loved one has gone missing:</p>
<ol>
  <li><strong>Contact your local police or law enforcement agency immediately.</strong></li>
  <li>Provide as much information as possible, including:</li>
</ol>
<ul>
  <li>Full name</li>
  <li>Recent photograph</li>
  <li>Date of birth</li>
  <li>Physical description</li>
  <li>Clothing worn</li>
  <li>Last known location</li>
  <li>Vehicle information</li>
  <li>Phone numbers and social media accounts</li>
  <li>Any medical or safety concerns</li>
</ul>
<p>Ask law enforcement about entering the missing person into the appropriate national missing-person databases.</p>
<p>For additional information and missing-person resources, visit the <a href="https://www.fbi.gov/" target="_blank" rel="noopener">FBI official website</a>.</p>

<h2>Missing Children</h2>
<p>If your child is missing, act immediately.</p>

<h3>National Center for Missing and Exploited Children (NCMEC)</h3>
<p><strong>24-Hour Hotline:</strong><br>
<strong>1-800-THE-LOST</strong><br>
<strong>1-800-843-5678</strong></p>
<p>After reporting the disappearance to local law enforcement, families can contact NCMEC for assistance and resources related to missing children.</p>
<p><a href="https://us.missingkids.org/gethelpnow/isyourchildmissing" target="_blank" rel="noopener">Visit the official NCMEC website</a>.</p>

<h2>AMBER Alert</h2>
<p>An <strong>AMBER Alert</strong> is used in serious child-abduction cases when law enforcement determines that the case meets the applicable criteria.</p>
<p>AMBER Alerts are issued and coordinated through law enforcement agencies and official AMBER Alert programs. They are <strong>not initiated by families or private websites</strong>.</p>

<h3>Official AMBER Alert Resources</h3>
<p><a href="https://amberalert.ojp.gov/" target="_blank" rel="noopener">Official AMBER Alert Program</a></p>
<p>The official AMBER Alert website provides information about active alerts, how the system works, state contacts, and emergency alert resources.</p>

<h3>If You Receive an AMBER Alert</h3>
<ul>
  <li>Read the information carefully.</li>
  <li>Pay attention to the child's description.</li>
  <li>Note any vehicle information provided.</li>
  <li>Do not approach a suspected abductor if doing so could be dangerous.</li>
  <li>Call <strong>911</strong> or follow the instructions included in the official alert if you have information.</li>
</ul>

<h2>Have You Seen a Missing Child?</h2>
<p><strong>Call 911 if there is immediate danger.</strong></p>
<p>You can also contact:</p>
<p><strong>NCMEC - 24-Hour Hotline</strong><br>
<strong>1-800-THE-LOST</strong><br>
<strong>1-800-843-5678</strong></p>
<p>You can search official missing children posters through:</p>
<p><a href="https://www.missingkids.org/search?os=io__" target="_blank" rel="noopener">NCMEC Missing Children Search</a></p>

<h2>Active AMBER Alerts</h2>
<p>To check official AMBER Alert information and active alerts:</p>
<p><a href="https://amberalert.ojp.gov/" target="_blank" rel="noopener">View Official AMBER Alerts</a></p>
<p>For additional information about AMBER Alert criteria and how the program works:</p>
<p><a href="https://amberalert.ojp.gov/about/faqs" target="_blank" rel="noopener">AMBER Alert Frequently Asked Questions</a></p>

<h2>Report Suspected Online Child Exploitation</h2>
<p>If you have information about suspected online child sexual exploitation, you can submit a report through the official CyberTipline.</p>
<p><a href="https://cf.missingkids.org/blog/2026/the-cybertipline-americas-front-line-against-online-child-sexual-exploitation" target="_blank" rel="noopener">NCMEC CyberTipline Information</a></p>
<p>You may also contact:<br>
<strong>1-800-THE-LOST</strong><br>
<strong>1-800-843-5678</strong></p>

<h2>What to Do When Someone Goes Missing</h2>
<h3>Act Quickly</h3>
<p>Do not assume that you must wait before reporting someone missing.</p>

<h3>Contact Law Enforcement</h3>
<p>Call your local police department or <strong>911</strong> if there is immediate danger.</p>

<h3>Gather Important Information</h3>
<p>Try to collect:</p>
<ul>
  <li>A recent photograph</li>
  <li>Full name and nickname</li>
  <li>Date of birth</li>
  <li>Height and weight</li>
  <li>Hair and eye color</li>
  <li>Clothing last worn</li>
  <li>Last known location</li>
  <li>Vehicle information</li>
  <li>Phone number</li>
  <li>Social media accounts</li>
  <li>Names of friends or associates</li>
  <li>Medical conditions or medications</li>
  <li>Any information that may indicate the person is in danger</li>
</ul>

<h3>Preserve Information</h3>
<p>Avoid deleting:</p>
<ul>
  <li>Text messages</li>
  <li>Emails</li>
  <li>Social media messages</li>
  <li>Call logs</li>
  <li>Location information</li>
  <li>Security camera footage</li>
</ul>
<p>This information may be useful to law enforcement.</p>

<h2>Missing USA</h2>
<p><strong>Missing USA</strong> is dedicated to sharing information, news, alerts, and updates related to missing children, teenagers, and adults across the United States.</p>
<p><a href="https://missing-usa.com/" target="_blank" rel="noopener">Missing USA - Missing Persons, Children and Alerts</a></p>
<p>Our goal is to help raise awareness and make missing-person information more accessible to the public.</p>

<h3>Important Notice</h3>
<p><strong>Missing-USA.com is not a law enforcement agency and cannot replace emergency services or official investigations.</strong></p>
<p>If someone is in immediate danger, <strong>call 911</strong>.</p>
<p>If you have information about a missing person or child, contact the appropriate law enforcement agency or official organization handling the case.</p>

<h2>Important Phone Numbers</h2>
<table class="table table-bordered table-striped">
  <thead>
    <tr>
      <th>Service</th>
      <th>Contact</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Emergency Services</td>
      <td><strong>911</strong></td>
    </tr>
    <tr>
      <td>NCMEC - Missing and Exploited Children</td>
      <td><strong>1-800-THE-LOST (1-800-843-5678)</strong></td>
    </tr>
    <tr>
      <td>Local Police</td>
      <td>Contact your local law enforcement agency</td>
    </tr>
    <tr>
      <td>Missing USA</td>
      <td><a href="https://missing-usa.com/" target="_blank" rel="noopener">Visit Missing-USA.com</a></td>
    </tr>
  </tbody>
</table>

<h2>Disclaimer</h2>
<p>The information on this page is provided for general informational and awareness purposes. Contact information and procedures may change over time. In an emergency or when there is an immediate threat to life or safety, always call <strong>911</strong>.</p>
<p>Missing USA is an independent information and awareness website and is not affiliated with, endorsed by, or operated by any government agency, law enforcement agency, the National Center for Missing and Exploited Children, or the AMBER Alert Program.</p>
<p>For official information, always refer to the appropriate law enforcement agency or official organization responsible for the case.</p>
HTML;

$smarty->assign('is_page', 1);
$smarty->assign('page_title', $page_title);
$smarty->assign('page_content', $page_content);
$smarty->assign('seo_title', $page_title . ' - ' . $general_setting['seo_title']);
$smarty->assign('seo_keywords', 'emergency contacts, missing children, missing persons, amber alert, ncmec, 911');
$smarty->assign('seo_description', 'Official emergency contacts and resources for missing persons, missing children, and AMBER Alerts in the United States.');
$smarty->assign('canonical_url', rtrim($general_setting['siteurl'], '/') . '/important-resources-emergency-contacts');

$smarty->display('important-resources-emergency-contacts.html');
?>