<?php session_start() ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Login Page</title>

  <!-- Bootstrap CSS -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">

  <!-- Custom CSS -->
  <link href="assets/css/style.css" rel="stylesheet">

</head>

<body>

  <section class="background-radial-gradient overflow-hidden d-flex align-items-center">
    <div class="container px-4 px-md-5 text-center text-lg-start">
      <div class="row gx-lg-5 align-items-center">

        <!-- LEFT TEXT -->
        <div class="col-lg-6 mb-5 mb-lg-0 d-flex flex-column justify-content-center align-items-center text-center"
          style="z-index: 10;">

          <h1 class="fw-bold quickcart-title">QuickCart</h1>
          <h2 class="quickcart-subtitle">All Your Shopping Needs in One Cart</h2>
          <img src="assets/img/QC-Icon.png" alt="QuickCart Logo" class="img-fluid mt-4 quickcart-logo">

        </div>

        <!-- RIGHT FORM -->
        <div class="col-lg-6 mb-5 mb-lg-0 position-relative">
          <div id="radius-shape-1" class="position-absolute rounded-circle shadow"></div>
          <div id="radius-shape-2" class="position-absolute shadow"></div>

          <div class="card bg-glass">
            <div class="card-body px-4 py-5 px-md-5">

              <h4 class="text-center mt-2 mb-2 fw-bold">Welcome back!</h4>
              <h5 class="text-center mb-3 text-muted">Please login to your account.</h5>

              <form action="/WST-QuickCart/app/controllers/loginController.php" method="POST"
                enctype="multipart/form-data" autocomplete="off">

                <!-- Username -->
                <div class="mb-3">
                  <label class="form-label" for="username">Username</label>
                  <input type="text" id="username" name="username" class="form-control" required>
                </div>

                <!-- Password -->
                <div class="mb-3">
                  <label class="form-label" for="password">Password</label>
                  <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <!-- Submit Button -->
                <button type="submit" name="loginButton" class="btn btn-quickcart w-100 mt-4 mb-3">
                  Login
                </button>

                <div class="text-center">
                  <p class="mb-0">Don't have an account?
                    <a href="registration" class="auth-link">Create an account</a>
                  </p>
                </div>

              </form>

            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- Bootstrap JS -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <?php include_once(__DIR__ . '/../app/helpers/flashMessage.php'); 
  flashMessage();
  ?>
</body>

</html>