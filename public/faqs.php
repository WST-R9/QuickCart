<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FAQs – QuickCart</title>
  <link href="user/assets/img/qc-favicon.png" rel="icon">
  <link href="user/assets/img/qc-touch-icon.png" rel="qc-touch-icon">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <style>
    :root {
      --qc-green: #005d21;
      --qc-green-light: #e6f2ea;
    }

    body {
      background: #f5f7f5;
      font-family: 'Segoe UI', sans-serif;
    }

    .qc-topbar {
      background: var(--qc-green);
      padding: 12px 0;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 2px 8px rgba(0, 0, 0, .15);
    }

    .qc-topbar .brand {
      color: #fff;
      font-size: 1.4rem;
      font-weight: 700;
      text-decoration: none;
    }

    .qc-topbar .brand span {
      color: #7ddf9e;
    }

    .qc-topbar .nav-links a {
      color: rgba(255, 255, 255, .85);
      text-decoration: none;
      margin-left: 18px;
      font-size: .93rem;
      transition: color .2s;
    }

    .qc-topbar .nav-links a:hover {
      color: #fff;
    }

    .faqs-hero {
      background: linear-gradient(135deg, var(--qc-green) 0%, #007a2e 100%);
      color: #fff;
      padding: 56px 0 40px;
      text-align: center;
    }

    .faqs-hero h1 {
      font-size: 2.2rem;
      font-weight: 700;
      margin-bottom: 8px;
    }

    .faqs-hero p {
      opacity: .85;
      font-size: 1.05rem;
      max-width: 520px;
      margin: 0 auto 28px;
    }

    /* Search */
    .faq-search-wrap {
      max-width: 480px;
      margin: 0 auto;
      position: relative;
    }

    .faq-search-wrap input {
      border-radius: 50px;
      border: none;
      padding: 11px 46px 11px 20px;
      width: 100%;
      font-size: .95rem;
      box-shadow: 0 2px 12px rgba(0, 0, 0, .18);
    }

    .faq-search-wrap .bi-search {
      position: absolute;
      right: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--qc-green);
      font-size: 1rem;
    }

    /* Tab pills */
    .faq-tabs {
      background: #fff;
      border-bottom: 1px solid #e0e5e0;
      padding: 14px 0;
    }

    .faq-tab {
      display: inline-block;
      padding: 6px 20px;
      border: 1.5px solid #cdd8cd;
      border-radius: 50px;
      font-size: .87rem;
      cursor: pointer;
      margin: 4px;
      color: #444;
      transition: all .2s;
    }

    .faq-tab:hover,
    .faq-tab.active {
      background: var(--qc-green);
      color: #fff;
      border-color: var(--qc-green);
    }

    /* Accordion */
    .faq-section {
      padding: 40px 0 64px;
    }

    .faq-group-title {
      font-size: 1rem;
      font-weight: 700;
      color: var(--qc-green);
      text-transform: uppercase;
      letter-spacing: .06em;
      margin: 32px 0 12px;
      padding-bottom: 6px;
      border-bottom: 2px solid var(--qc-green-light);
    }

    .accordion-button {
      font-weight: 600;
      font-size: .96rem;
      color: #1a2e1a;
    }

    .accordion-button:not(.collapsed) {
      background: var(--qc-green-light);
      color: var(--qc-green);
    }

    .accordion-button:focus {
      box-shadow: 0 0 0 .2rem rgba(0, 93, 33, .2);
    }

    .accordion-item {
      border: 1px solid #dee2de;
      border-radius: 10px !important;
      margin-bottom: 10px;
      overflow: hidden;
    }

    .accordion-body {
      font-size: .93rem;
      color: #444;
      line-height: 1.7;
    }

    /* CTA banner */
    .faq-cta {
      background: var(--qc-green-light);
      border-radius: 14px;
      padding: 32px;
      text-align: center;
      margin-top: 40px;
    }

    .faq-cta h5 {
      font-weight: 700;
      color: #1a2e1a;
      margin-bottom: 8px;
    }

    .faq-cta p {
      color: #555;
      margin-bottom: 18px;
    }

    .btn-qc {
      background: var(--qc-green);
      color: #fff;
      border-radius: 8px;
      padding: 9px 26px;
      border: none;
      font-weight: 600;
    }

    .btn-qc:hover {
      background: #004819;
      color: #fff;
    }

    /* Footer */
    .qc-footer {
      background: #1a2e1a;
      color: rgba(255, 255, 255, .7);
      text-align: center;
      padding: 24px 0;
      font-size: .88rem;
    }

    .qc-footer a {
      color: rgba(255, 255, 255, .6);
      margin: 0 10px;
      text-decoration: none;
    }

    .qc-footer a:hover {
      color: #fff;
    }

    /* Hide faq items when filtered */
    .faq-item.hidden {
      display: none !important;
    }
  </style>
</head>

<body>

  <!-- Top bar -->
  <nav class="qc-topbar">
    <div class="container d-flex align-items-center justify-content-between">
      <a href="/WST-QuickCart/public/index.php" class="brand">Quick<span>Cart</span></a>
      <div class="nav-links">
        <a href="/WST-QuickCart/public/products.php"><i class="bi bi-grid me-1"></i>Products</a>
        <a href="/WST-QuickCart/public/faqs.php"><i class="bi bi-question-circle me-1"></i>FAQs</a>
        <a href="/WST-QuickCart/public/terms.php"><i class="bi bi-file-text me-1"></i>T&amp;C's</a>
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="/WST-QuickCart/public/user/index.php"><i class="bi bi-person-circle me-1"></i>My Account</a>
        <?php else: ?>
          <a href="/WST-QuickCart/public/login.php"><i class="bi bi-box-arrow-in-right me-1"></i>Login</a>
          <a href="/WST-QuickCart/public/registration.php"><i class="bi bi-person-plus me-1"></i>Register</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <!-- Hero -->
  <section class="faqs-hero">
    <div class="container">
      <h1><i class="bi bi-question-circle me-2"></i>Frequently Asked Questions</h1>
      <p>Got a question? We've got answers. Search below or browse by topic.</p>
      <div class="faq-search-wrap">
        <input type="text" id="faqSearch" placeholder="Search questions…">
        <i class="bi bi-search"></i>
      </div>
    </div>
  </section>

  <!-- Tab filter -->
  <div class="faq-tabs">
    <div class="container text-center">
      <span class="faq-tab active" data-group="all">All Topics</span>
      <span class="faq-tab" data-group="orders">Orders</span>
      <span class="faq-tab" data-group="shipping">Shipping</span>
      <span class="faq-tab" data-group="payment">Payment</span>
      <span class="faq-tab" data-group="account">Account</span>
      <span class="faq-tab" data-group="returns">Returns</span>
    </div>
  </div>

  <!-- FAQ Accordions -->
  <section class="faq-section">
    <div class="container" style="max-width:780px;">

      <!-- Orders -->
      <div class="faq-group-label" data-group="orders">
        <div class="faq-group-title"><i class="bi bi-bag-check me-2"></i>Orders</div>
        <div class="accordion" id="accordionOrders">
          <div class="accordion-item faq-item" data-group="orders">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                data-bs-target="#faq-o1">
                How do I place an order?
              </button>
            </h2>
            <div id="faq-o1" class="accordion-collapse collapse">
              <div class="accordion-body">
                Browse our products, add items to your cart, and proceed to checkout. You'll need to be logged in to
                complete your purchase. If you don't have an account yet, <a
                  href="/WST-QuickCart/public/registration.php" style="color:var(--qc-green);">register here</a> — it
                only takes a minute!
              </div>
            </div>
          </div>
          <div class="accordion-item faq-item" data-group="orders">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                data-bs-target="#faq-o2">
                Can I modify or cancel my order after placing it?
              </button>
            </h2>
            <div id="faq-o2" class="accordion-collapse collapse">
              <div class="accordion-body">
                You may cancel or modify your order within <strong>1 hour</strong> of placing it, provided it hasn't
                been processed yet. Go to <em>My Account → Orders</em> and select the order to make changes. After
                processing, cancellation is no longer available.
              </div>
            </div>
          </div>
          <div class="accordion-item faq-item" data-group="orders">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                data-bs-target="#faq-o3">
                How do I track my order?
              </button>
            </h2>
            <div id="faq-o3" class="accordion-collapse collapse">
              <div class="accordion-body">
                Once your order is shipped, you'll receive an email with a tracking number. You can also check the
                status in <em>My Account → Orders</em> at any time.
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Shipping -->
      <div class="faq-group-label" data-group="shipping">
        <div class="faq-group-title"><i class="bi bi-truck me-2"></i>Shipping</div>
        <div class="accordion" id="accordionShipping">
          <div class="accordion-item faq-item" data-group="shipping">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                data-bs-target="#faq-s1">
                What are the shipping options available?
              </button>
            </h2>
            <div id="faq-s1" class="accordion-collapse collapse">
              <div class="accordion-body">
                We offer Standard Shipping (3–7 business days), Express Shipping (1–2 business days), and same-day
                delivery in select areas. Shipping fees are calculated at checkout based on your location and chosen
                method.
              </div>
            </div>
          </div>
          <div class="accordion-item faq-item" data-group="shipping">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                data-bs-target="#faq-s2">
                Do you offer free shipping?
              </button>
            </h2>
            <div id="faq-s2" class="accordion-collapse collapse">
              <div class="accordion-body">
                Yes! Orders above <strong>₱1,500</strong> qualify for free standard shipping within the Philippines.
                Look out for special promotions that may lower this threshold.
              </div>
            </div>
          </div>
          <div class="accordion-item faq-item" data-group="shipping">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                data-bs-target="#faq-s3">
                Do you ship outside the Philippines?
              </button>
            </h2>
            <div id="faq-s3" class="accordion-collapse collapse">
              <div class="accordion-body">
                Currently, QuickCart ships within the Philippines only. International shipping is on our roadmap — stay
                tuned for announcements!
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment -->
      <div class="faq-group-label" data-group="payment">
        <div class="faq-group-title"><i class="bi bi-credit-card me-2"></i>Payment</div>
        <div class="accordion" id="accordionPayment">
          <div class="accordion-item faq-item" data-group="payment">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                data-bs-target="#faq-p1">
                What payment methods are accepted?
              </button>
            </h2>
            <div id="faq-p1" class="accordion-collapse collapse">
              <div class="accordion-body">
                We accept GCash, Maya, credit/debit cards (Visa, Mastercard), and Cash on Delivery (COD) for eligible
                areas.
              </div>
            </div>
          </div>
          <div class="accordion-item faq-item" data-group="payment">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                data-bs-target="#faq-p2">
                Is my payment information secure?
              </button>
            </h2>
            <div id="faq-p2" class="accordion-collapse collapse">
              <div class="accordion-body">
                Absolutely. All transactions are encrypted using SSL/TLS. We do not store your card details on our
                servers. Payments are processed through certified, PCI-compliant gateways.
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Account -->
      <div class="faq-group-label" data-group="account">
        <div class="faq-group-title"><i class="bi bi-person-circle me-2"></i>Account</div>
        <div class="accordion" id="accordionAccount">
          <div class="accordion-item faq-item" data-group="account">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                data-bs-target="#faq-a1">
                How do I create an account?
              </button>
            </h2>
            <div id="faq-a1" class="accordion-collapse collapse">
              <div class="accordion-body">
                Click <a href="/WST-QuickCart/public/registration.php" style="color:var(--qc-green);">Register</a> and
                fill in your details. Registration is free and takes less than a minute.
              </div>
            </div>
          </div>
          <div class="accordion-item faq-item" data-group="account">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                data-bs-target="#faq-a2">
                I forgot my password. What do I do?
              </button>
            </h2>
            <div id="faq-a2" class="accordion-collapse collapse">
              <div class="accordion-body">
                On the login page, click <em>"Forgot Password?"</em> and enter your registered email. You'll receive a
                reset link within a few minutes.
              </div>
            </div>
          </div>
          <div class="accordion-item faq-item" data-group="account">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                data-bs-target="#faq-a3">
                Can I browse as a guest?
              </button>
            </h2>
            <div id="faq-a3" class="accordion-collapse collapse">
              <div class="accordion-body">
                Yes! You can browse all products as a guest, but you'll need to log in or register to add items to your
                cart, place orders, or save items to your wishlist.
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Returns -->
      <div class="faq-group-label" data-group="returns">
        <div class="faq-group-title"><i class="bi bi-arrow-return-left me-2"></i>Returns &amp; Refunds</div>
        <div class="accordion" id="accordionReturns">
          <div class="accordion-item faq-item" data-group="returns">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                data-bs-target="#faq-r1">
                What is your return policy?
              </button>
            </h2>
            <div id="faq-r1" class="accordion-collapse collapse">
              <div class="accordion-body">
                Items may be returned within <strong>7 days</strong> of delivery, provided they are unused, in original
                packaging, and accompanied by proof of purchase. Certain items (perishables, digital products,
                undergarments) are non-returnable.
              </div>
            </div>
          </div>
          <div class="accordion-item faq-item" data-group="returns">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                data-bs-target="#faq-r2">
                How do I get a refund?
              </button>
            </h2>
            <div id="faq-r2" class="accordion-collapse collapse">
              <div class="accordion-body">
                Once your returned item is received and inspected (3–5 business days), your refund will be processed to
                the original payment method within 7–10 business days.
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- CTA banner -->
      <div class="faq-cta">
        <h5><i class="bi bi-chat-dots me-2"></i>Still have questions?</h5>
        <p>Our support team is happy to help. Reach out to us anytime.</p>
        <a href="mailto:support@quickcart.ph" class="btn btn-qc">
          <i class="bi bi-envelope me-1"></i>Contact Support
        </a>
      </div>

    </div>
  </section>

  <!-- Footer -->
  <footer class="qc-footer">
    <div class="container">
      <div class="mb-2">
        <a href="/WST-QuickCart/public/products.php">Products</a>
        <a href="/WST-QuickCart/public/faqs.php">FAQs</a>
        <a href="/WST-QuickCart/public/terms.php">Terms &amp; Conditions</a>
        <?php if (!isset($_SESSION['user_id'])): ?>
          <a href="/WST-QuickCart/public/login.php">Login</a>
          <a href="/WST-QuickCart/public/registration.php">Register</a>
        <?php endif; ?>
      </div>
      &copy; 2026 QuickCart, Inc. All Rights Reserved.
    </div>
  </footer>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script>
    // Tab filter
    document.querySelectorAll('.faq-tab').forEach(tab => {
      tab.addEventListener('click', function () {
        document.querySelectorAll('.faq-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        const g = this.dataset.group;
        document.querySelectorAll('.faq-group-label').forEach(grp => {
          grp.style.display = (g === 'all' || grp.dataset.group === g) ? '' : 'none';
        });
      });
    });

    // Search
    document.getElementById('faqSearch').addEventListener('input', function () {
      const q = this.value.toLowerCase();
      // Reset tabs
      document.querySelectorAll('.faq-tab').forEach(t => t.classList.remove('active'));
      document.querySelector('[data-group="all"]').classList.add('active');
      document.querySelectorAll('.faq-group-label').forEach(g => g.style.display = '');

      document.querySelectorAll('.faq-item').forEach(item => {
        const text = item.textContent.toLowerCase();
        item.classList.toggle('hidden', q !== '' && !text.includes(q));
      });
    });
  </script>
</body>

</html>