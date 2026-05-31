<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Terms &amp; Conditions – QuickCart</title>
  <link href="user/assets/img/qc-favicon.png" rel="icon">
  <link href="user/assets/img/qc-touch-icon.png" rel="qc-touch-icon">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <style>
    :root { --qc-green: #005d21; --qc-green-light: #e6f2ea; }
    body { background: #f5f7f5; font-family: 'Segoe UI', sans-serif; }

    .qc-topbar {
      background: var(--qc-green); padding: 12px 0;
      position: sticky; top: 0; z-index: 100;
      box-shadow: 0 2px 8px rgba(0,0,0,.15);
    }
    .qc-topbar .brand { color:#fff; font-size:1.4rem; font-weight:700; text-decoration:none; }
    .qc-topbar .brand span { color:#7ddf9e; }
    .qc-topbar .nav-links a {
      color:rgba(255,255,255,.85); text-decoration:none;
      margin-left:18px; font-size:.93rem; transition: color .2s;
    }
    .qc-topbar .nav-links a:hover { color:#fff; }

    .tc-hero {
      background: linear-gradient(135deg, var(--qc-green) 0%, #007a2e 100%);
      color: #fff; padding: 56px 0 40px; text-align: center;
    }
    .tc-hero h1 { font-size: 2.2rem; font-weight: 700; margin-bottom: 8px; }
    .tc-hero p  { opacity: .85; font-size: 1rem; }

    /* Layout */
    .tc-layout { display: flex; gap: 32px; padding: 40px 0 64px; }

    /* Sticky sidebar TOC */
    .tc-toc {
      flex: 0 0 220px;
      position: sticky; top: 74px; align-self: flex-start;
      background: #fff; border-radius: 12px; padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,.06); font-size: .88rem;
    }
    .tc-toc h6 { font-weight: 700; color: var(--qc-green); margin-bottom: 12px; font-size: .82rem; text-transform: uppercase; letter-spacing: .07em; }
    .tc-toc a { display: block; color: #555; text-decoration: none; padding: 5px 0; border-left: 2px solid transparent; padding-left: 10px; transition: all .2s; line-height: 1.4; }
    .tc-toc a:hover, .tc-toc a.active { color: var(--qc-green); border-left-color: var(--qc-green); font-weight: 600; }

    /* Main content */
    .tc-content { flex: 1; min-width: 0; }
    .tc-card {
      background: #fff; border-radius: 14px;
      box-shadow: 0 2px 10px rgba(0,0,0,.06);
      padding: 36px 40px; margin-bottom: 20px;
    }
    .tc-card .section-badge {
      display: inline-block; background: var(--qc-green-light);
      color: var(--qc-green); font-size: .75rem; font-weight: 700;
      padding: 3px 10px; border-radius: 50px; margin-bottom: 10px;
      text-transform: uppercase; letter-spacing: .06em;
    }
    .tc-card h2 { font-size: 1.2rem; font-weight: 700; color: #1a2e1a; margin-bottom: 14px; }
    .tc-card p, .tc-card li { font-size: .94rem; color: #444; line-height: 1.8; }
    .tc-card ul { padding-left: 20px; }
    .tc-card ul li { margin-bottom: 6px; }
    .tc-card .highlight {
      background: var(--qc-green-light); border-left: 4px solid var(--qc-green);
      border-radius: 0 8px 8px 0; padding: 12px 16px; margin: 14px 0;
      font-size: .92rem; color: #2a4a2a;
    }

    .last-updated { font-size: .82rem; color: #999; margin-top: 4px; }

    .qc-footer { background: #1a2e1a; color: rgba(255,255,255,.7); text-align: center; padding: 24px 0; font-size: .88rem; }
    .qc-footer a { color: rgba(255,255,255,.6); margin: 0 10px; text-decoration: none; }
    .qc-footer a:hover { color: #fff; }

    @media(max-width:768px) {
      .tc-layout { flex-direction: column; }
      .tc-toc { position: static; flex: none; width: 100%; }
      .tc-card { padding: 24px 20px; }
    }
  </style>
</head>
<body>

<!-- Top bar -->
<nav class="qc-topbar">
  <div class="container d-flex align-items-center justify-content-between">
    <a href="/WST-QuickCart/public/index.php" class="brand">Quick<span>Cart</span></a>
    <div class="nav-links">
      <a href="/WST-QuickCart/public/faqs.php"><i class="bi bi-question-circle me-1"></i>FAQs</a>
      <a href="/WST-QuickCart/public/terms.php"><i class="bi bi-file-text me-1"></i>T&amp;C's</a>
      <?php if(isset($_SESSION['user_id'])): ?>
        <a href="/WST-QuickCart/public/user/index.php"><i class="bi bi-person-circle me-1"></i>My Account</a>
      <?php else: ?>
        <a href="/WST-QuickCart/public/login.php"><i class="bi bi-box-arrow-in-right me-1"></i>Login</a>
        <a href="/WST-QuickCart/public/registration.php"><i class="bi bi-person-plus me-1"></i>Register</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- Hero -->
<section class="tc-hero">
  <div class="container">
    <h1><i class="bi bi-file-earmark-text me-2"></i>Terms &amp; Conditions</h1>
    <p>Please read these terms carefully before using QuickCart.</p>
    <p class="last-updated">Last updated: January 1, 2026</p>
  </div>
</section>

<!-- Layout -->
<div class="container">
  <div class="tc-layout">

    <!-- TOC Sidebar -->
    <aside class="tc-toc">
      <h6><i class="bi bi-list-ul me-1"></i>Contents</h6>
      <a href="#acceptance">1. Acceptance</a>
      <a href="#eligibility">2. Eligibility</a>
      <a href="#account">3. Account</a>
      <a href="#orders">4. Orders &amp; Payment</a>
      <a href="#shipping">5. Shipping</a>
      <a href="#returns">6. Returns &amp; Refunds</a>
      <a href="#ip">7. Intellectual Property</a>
      <a href="#privacy">8. Privacy</a>
      <a href="#liability">9. Liability</a>
      <a href="#changes">10. Changes to Terms</a>
      <a href="#contact">11. Contact Us</a>
    </aside>

    <!-- Content -->
    <main class="tc-content">

      <div class="tc-card" id="acceptance">
        <span class="section-badge">Section 1</span>
        <h2><i class="bi bi-check-circle me-2" style="color:var(--qc-green)"></i>Acceptance of Terms</h2>
        <p>By accessing or using the QuickCart platform — whether through our website, mobile app, or any related service — you agree to be bound by these Terms and Conditions. If you do not agree to any part of these terms, you must discontinue use of our services immediately.</p>
        <div class="highlight">These terms constitute a legally binding agreement between you and QuickCart, Inc.</div>
      </div>

      <div class="tc-card" id="eligibility">
        <span class="section-badge">Section 2</span>
        <h2><i class="bi bi-person-check me-2" style="color:var(--qc-green)"></i>Eligibility</h2>
        <p>To use QuickCart's services, you must:</p>
        <ul>
          <li>Be at least 18 years old, or have parental/guardian consent.</li>
          <li>Provide accurate, current, and complete registration information.</li>
          <li>Not be prohibited from using the services under applicable law.</li>
        </ul>
        <p>We reserve the right to refuse service to anyone at our sole discretion.</p>
      </div>

      <div class="tc-card" id="account">
        <span class="section-badge">Section 3</span>
        <h2><i class="bi bi-person-circle me-2" style="color:var(--qc-green)"></i>Account Responsibilities</h2>
        <p>When you create an account with QuickCart, you are responsible for:</p>
        <ul>
          <li>Maintaining the confidentiality of your username and password.</li>
          <li>All activities that occur under your account.</li>
          <li>Notifying us immediately of any unauthorized use of your account.</li>
        </ul>
        <p>QuickCart is not liable for any loss resulting from unauthorized use of your account credentials.</p>
      </div>

      <div class="tc-card" id="orders">
        <span class="section-badge">Section 4</span>
        <h2><i class="bi bi-bag-check me-2" style="color:var(--qc-green)"></i>Orders &amp; Payment</h2>
        <p>All orders placed through QuickCart are subject to acceptance and availability. We reserve the right to refuse or cancel any order at any time.</p>
        <ul>
          <li>Prices are listed in Philippine Pesos (₱) and are subject to change without notice.</li>
          <li>Payment must be completed at the time of ordering.</li>
          <li>We accept GCash, Maya, credit/debit cards, and Cash on Delivery (where available).</li>
          <li>You agree not to use fraudulent or unauthorized payment methods.</li>
        </ul>
        <div class="highlight">Order confirmation via email does not constitute final acceptance. QuickCart reserves the right to cancel orders due to stock issues, pricing errors, or suspected fraud.</div>
      </div>

      <div class="tc-card" id="shipping">
        <span class="section-badge">Section 5</span>
        <h2><i class="bi bi-truck me-2" style="color:var(--qc-green)"></i>Shipping &amp; Delivery</h2>
        <p>QuickCart ships within the Philippines. Delivery times are estimates and may vary based on location, carrier availability, and demand. We are not responsible for delays caused by third-party logistics providers, natural calamities, or force majeure events.</p>
        <ul>
          <li>Standard Delivery: 3–7 business days</li>
          <li>Express Delivery: 1–2 business days</li>
          <li>Free shipping on orders above ₱1,500</li>
        </ul>
      </div>

      <div class="tc-card" id="returns">
        <span class="section-badge">Section 6</span>
        <h2><i class="bi bi-arrow-return-left me-2" style="color:var(--qc-green)"></i>Returns &amp; Refunds</h2>
        <p>Items may be returned within 7 days of delivery under the following conditions:</p>
        <ul>
          <li>Item is unused and in its original packaging.</li>
          <li>Proof of purchase is provided.</li>
          <li>Item is not listed as non-returnable (perishables, digital goods, personal hygiene items).</li>
        </ul>
        <p>Approved refunds will be processed within 7–10 business days to the original payment method. Shipping costs for returns are borne by the customer unless the item is defective or incorrectly delivered.</p>
      </div>

      <div class="tc-card" id="ip">
        <span class="section-badge">Section 7</span>
        <h2><i class="bi bi-shield-lock me-2" style="color:var(--qc-green)"></i>Intellectual Property</h2>
        <p>All content on the QuickCart platform — including logos, text, graphics, images, and software — is the property of QuickCart, Inc. and is protected by applicable intellectual property laws. You may not reproduce, distribute, or create derivative works without our express written permission.</p>
      </div>

      <div class="tc-card" id="privacy">
        <span class="section-badge">Section 8</span>
        <h2><i class="bi bi-lock me-2" style="color:var(--qc-green)"></i>Privacy</h2>
        <p>Your use of QuickCart is also governed by our Privacy Policy, which is incorporated into these Terms by reference. By using our services, you consent to the collection and use of your data as described therein. We are committed to protecting your personal information and will not sell it to third parties.</p>
      </div>

      <div class="tc-card" id="liability">
        <span class="section-badge">Section 9</span>
        <h2><i class="bi bi-exclamation-triangle me-2" style="color:var(--qc-green)"></i>Limitation of Liability</h2>
        <p>To the fullest extent permitted by law, QuickCart, Inc. shall not be liable for any indirect, incidental, special, or consequential damages arising from your use of — or inability to use — our services, even if we have been advised of the possibility of such damages.</p>
        <div class="highlight">Our total liability for any claim shall not exceed the amount you paid for the specific order or service giving rise to the claim.</div>
      </div>

      <div class="tc-card" id="changes">
        <span class="section-badge">Section 10</span>
        <h2><i class="bi bi-arrow-clockwise me-2" style="color:var(--qc-green)"></i>Changes to These Terms</h2>
        <p>QuickCart reserves the right to update or modify these Terms at any time. Changes will be effective immediately upon posting to this page. Continued use of our services after any changes constitutes your acceptance of the new Terms. We encourage you to review this page periodically.</p>
      </div>

      <div class="tc-card" id="contact">
        <span class="section-badge">Section 11</span>
        <h2><i class="bi bi-envelope me-2" style="color:var(--qc-green)"></i>Contact Us</h2>
        <p>If you have questions or concerns about these Terms, please reach out to us:</p>
        <ul>
          <li><strong>Email:</strong> <a href="mailto:legal@quickcart.ph" style="color:var(--qc-green)">legal@quickcart.ph</a></li>
          <li><strong>Support:</strong> <a href="mailto:support@quickcart.ph" style="color:var(--qc-green)">support@quickcart.ph</a></li>
          <li><strong>Address:</strong> QuickCart, Inc., Philippines</li>
        </ul>
      </div>

    </main>
  </div>
</div>

<!-- Footer -->
<footer class="qc-footer">
  <div class="container">
    <div class="mb-2">
      <a href="/WST-QuickCart/public/faqs.php">FAQs</a>
      <a href="/WST-QuickCart/public/terms.php">Terms &amp; Conditions</a>
      <?php if(!isset($_SESSION['user_id'])): ?>
      <a href="/WST-QuickCart/public/login.php">Login</a>
      <a href="/WST-QuickCart/public/registration.php">Register</a>
      <?php endif; ?>
    </div>
    &copy; 2026 QuickCart, Inc. All Rights Reserved.
  </div>
</footer>

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
// Highlight active TOC link on scroll
const sections = document.querySelectorAll('.tc-card');
const tocLinks = document.querySelectorAll('.tc-toc a');
window.addEventListener('scroll', () => {
  let current = '';
  sections.forEach(s => {
    if(window.scrollY >= s.offsetTop - 120) current = s.id;
  });
  tocLinks.forEach(a => {
    a.classList.toggle('active', a.getAttribute('href') === '#' + current);
  });
});
</script>
</body>
</html>