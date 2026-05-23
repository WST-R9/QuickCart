<?php
include_once("../../app/middleware/guest.php");
include_once("../../app/config/config.php");
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');
?>

<div class="pagetitle">
  <h1>Support Tickets</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item active">Support Tickets</li>
    </ol>
  </nav>
</div>

<section class="section">
  <div class="row justify-content-center">
    <div class="col-lg-6">
      <div class="card text-center">
        <div class="card-body py-5">

          <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
               style="width:80px; height:80px; background:#e8f5e9;">
            <i class="bi bi-headset" style="font-size:36px; color:#005d21;"></i>
          </div>

          <h4 class="fw-bold mb-2" style="color:#003d16;">Login Required</h4>
          <p class="text-muted mb-4">
            You need to be logged in to submit or view support tickets.
            Create an account to get access to our full customer support.
          </p>

          <div class="d-flex flex-column gap-2 align-items-center">
            <a href="/WST-QuickCart/public/login"
               class="btn btn-primary w-100" style="max-width:280px;">
              <i class="bi bi-box-arrow-in-right me-1"></i> Login
            </a>
            <a href="/WST-QuickCart/public/registration"
               class="btn btn-outline-primary w-100" style="max-width:280px;">
              <i class="bi bi-person-plus me-1"></i> Create Account
            </a>
            <a href="index" class="btn btn-outline-secondary w-100" style="max-width:280px;">
              <i class="bi bi-arrow-left me-1"></i> Back to Home
            </a>
          </div>

          <hr class="my-4">

          <p class="text-muted small mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Already submitted a ticket? Login to view your ticket history and replies from our support team.
          </p>

        </div>
      </div>
    </div>
  </div>
</section>

<?php include('includes/footer.php'); ?>
