</main><!-- End #main -->

<!-- ======= Footer ======= -->
<footer id="footer" class="footer">
  <div class="copyright">
    © 2026 QuickCart, Inc. All Rights Reserved
  </div>
  <div class="mt-4 d-flex justify-content-center gap-3">
    <span class="mx-1">·</span>
    <a href="/WST-QuickCart/public/faqs.php">FAQs</a>
    <span class="mx-1">·</span>
    <a href="/WST-QuickCart/public/terms.php">Terms &amp; Conditions</a>
    <span class="mx-1">·</span>
    <a href="/WST-QuickCart/public/login.php">Login</a>
    <span class="mx-1">·</span>
    <a href="/WST-QuickCart/public/registration.php">Register</a>
  </div>
</footer>

<a href="#" class="back-to-top d-flex align-items-center justify-content-center">
  <i class="bi bi-arrow-up-short"></i>
</a>

<script src="../user/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../user/assets/vendor/simple-datatables/simple-datatables.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../user/assets/js/main.js"></script>

<!-- Guest Login Prompt -->
<script>
function showLoginPrompt() {
  Swal.fire({
    title: 'Login Required',
    html: `
      <p style="color:#444; font-size:15px; margin-bottom:0;">
        You need to <strong>login</strong> or <strong>create an account</strong>
        to add items to your cart or wishlist.
      </p>
    `,
    icon: 'info',
    showCloseButton: true,
    showDenyButton: true,
    showCancelButton: true,
    confirmButtonText: 'Login',
    denyButtonText: 'Register',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#005d21',
    denyButtonColor: '#2563eb',
    cancelButtonColor: '#6c757d',
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = '/WST-QuickCart/public/login';
    } else if (result.isDenied) {
      window.location.href = '/WST-QuickCart/public/registration';
    }
  });
}
</script>

<?php
include_once(__DIR__ . '/../../../app/helpers/flashMessage.php');
flashMessage();
?>

</body>
</html>