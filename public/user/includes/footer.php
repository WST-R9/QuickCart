</main><!-- End #main -->

<!-- ======= Footer ======= -->
<footer id="footer" class="footer">
  <div class="copyright">
    © 2026 QuickCart, Inc. All Rights Reserved
  </div>
</footer><!-- End Footer -->

<a href="#" class="back-to-top d-flex align-items-center justify-content-center">
  <i class="bi bi-arrow-up-short"></i>
</a>
  
<!-- Vendor JS Files -->
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Expose PHP config BEFORE main.js so the globals exist when JS runs -->
<script>
  window.QC_SEARCH_EP = 'app/controllers/searchController.php';
</script>

<!-- Template Main JS File -->
<script src="assets/js/main.js"></script>

<?php
include_once(__DIR__ . '/../../../app/helpers/flashMessage.php');
flashMessage();
?>

</body>
</html>