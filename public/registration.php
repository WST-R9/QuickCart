<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Registration Page</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <style>
    .background-radial-gradient {
      min-height: 100vh;
      background: url("assets/img/QC-bkrd.png") no-repeat center center;
      background-size: cover;
    }
    .bg-glass {
      background-color: rgba(255, 255, 255, 0.9) !important;
      backdrop-filter: blur(10px);
      border-radius: 15px;
    }
    .btn-quickcart { background-color: #005d21; border-color: #005d21; color: white; }
    .btn-quickcart:hover { background-color: #004a1a; border-color: #004a1a; color: white; }
    html, body { min-height: 100%; margin: 0; }
    .form-step { display: none; }
    .form-step-active { display: block; }
    .progress-container { display: flex; justify-content: space-between; gap: 8px; margin-bottom: 20px; }
    .progress-step { flex: 1; height: 5px; background-color: #d3d3d3; border-radius: 5px; }
    .progress-step.active { background-color: #005d21; }
  </style>
</head>

<body>

  <section class="background-radial-gradient d-flex align-items-center py-5">
    <div class="container px-4 px-md-5 text-center text-lg-start">
      <div class="row gx-lg-5 align-items-center">

        <!-- LEFT TEXT -->
        <div class="col-lg-6 mb-5 mb-lg-0 d-flex flex-column justify-content-center align-items-center text-center" style="z-index: 10;">
          <h1 class="fw-bold" style="color: hsl(0, 0%, 100%); font-size: 80px; line-height: 1;">QuickCart</h1>
          <h2 style="color: hsl(191, 41%, 95%); font-size: 28px; font-weight: 400;">All Your Shopping Needs in One Cart</h2>
          <img src="assets/img/QC-Icon.png" alt="QuickCart Logo" class="img-fluid mt-4" style="max-width: 410px;">
        </div>

        <!-- RIGHT SIDE FORM -->
        <div class="col-lg-6 mb-5 mb-lg-0 position-relative">
          <div class="card bg-glass">
            <div class="card-body px-4 py-5 px-md-5">

              <h4 class="text-center fw-bold">Create an Account</h4>
              <p class="text-center text-muted mb-4">Enter your personal details to create account</p>

              <!-- Progress bar -->
              <div class="progress-container">
                <div class="progress-step active"></div>
                <div class="progress-step"></div>
                <div class="progress-step"></div>
              </div>

              <!-- FORM -->
              <form id="registrationForm" action="/WST-QuickCart/app/controllers/loginController.php" method="POST" enctype="multipart/form-data" autocomplete="off">

                <!-- STEP 1: Personal Info -->
                <div class="form-step form-step-active">
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">First Name <span style="color:red;">*</span></label>
                      <input type="text" name="firstName" id="firstName" class="form-control" placeholder="Juan">
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Middle Name</label>
                      <input type="text" name="middleName" class="form-control">
                    </div>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Last Name <span style="color:red;">*</span></label>
                    <input type="text" name="lastName" id="lastName" class="form-control" placeholder="Dela Cruz">
                  </div>
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Birthday <span style="color:red;">*</span></label>
                      <input type="date" name="birthday" id="birthday" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label d-block">Gender <span style="color:red;">*</span></label>
                      <div class="d-flex gap-4 mt-2">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="gender" id="genderMale" value="Male">
                          <label class="form-check-label" for="genderMale">Male</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="gender" id="genderFemale" value="Female">
                          <label class="form-check-label" for="genderFemale">Female</label>
                        </div>
                      </div>
                    </div>
                  </div>
                  <button type="button" class="btn btn-quickcart w-100 mt-2" id="nextBtn1">Next</button>
                </div>

                <!-- STEP 2: Contact & Address -->
                <div class="form-step">
                  <div class="mb-3">
                    <label class="form-label">Email Address <span style="color:red;">*</span></label>
                    <input type="email" name="emailAddress" id="emailAddress" class="form-control" placeholder="example@email.com">
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Phone Number <span style="color:red;">*</span></label>
                    <input type="text" name="phoneNumber" id="phoneNumber" class="form-control" placeholder="09XXXXXXXXX" maxlength="15">
                    <div class="form-text text-muted">10–15 digits only, e.g. 09171234567</div>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Street <span style="color:red;">*</span></label>
                    <input type="text" name="street" id="street" class="form-control" placeholder="Velez St.">
                  </div>
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Barangay <span style="color:red;">*</span></label>
                      <input type="text" name="barangay" id="barangay" class="form-control" placeholder="Baikingon">
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">City <span style="color:red;">*</span></label>
                      <input type="text" name="city" id="city" class="form-control" placeholder="Quezon City">
                    </div>
                  </div>
                  <div class="d-flex justify-content-between mt-2">
                    <button type="button" class="btn btn-secondary" id="prevBtn2">Previous</button>
                    <button type="button" class="btn btn-quickcart" id="nextBtn2">Next</button>
                  </div>
                </div>

                <!-- STEP 3: Account Setup -->
                <div class="form-step">
                  <div class="mb-3">
                    <label class="form-label">Username <span style="color:red;">*</span></label>
                    <input type="text" name="username" id="username" class="form-control">
                  </div>
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Password <span style="color:red;">*</span></label>
                      <input type="password" name="password" id="password" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Confirm Password <span style="color:red;">*</span></label>
                      <input type="password" name="confirmPassword" id="confirmPassword" class="form-control">
                    </div>
                  </div>
                  <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="acceptTerms">
                    <label class="form-check-label" for="acceptTerms">
                      I agree and accept the <a href="#">terms and conditions</a>
                    </label>
                  </div>
                  <div class="d-flex justify-content-between mt-2">
                    <button type="button" class="btn btn-secondary" id="prevBtn3">Previous</button>
                    <button type="submit" name="registerButton" class="btn btn-quickcart">Create Account</button>
                  </div>
                </div>

              </form>

              <div class="text-center mt-3">
                <p class="mb-0">Already have an account? <a href="login">Log in</a></p>
              </div>

            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    // Toast — defined once, used everywhere (validation + PHP flash messages)
    const Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
      didOpen: (toast) => {
        toast.onmouseenter = Swal.stopTimer;
        toast.onmouseleave = Swal.resumeTimer;
      }
    });

    // Multi-step logic
    const formSteps     = document.querySelectorAll('.form-step');
    const progressSteps = document.querySelectorAll('.progress-step');
    let currentStep = 0;

    function updateFormSteps() {
      formSteps.forEach((step, i)    => step.classList.toggle('form-step-active', i === currentStep));
      progressSteps.forEach((bar, i) => bar.classList.toggle('active', i <= currentStep));
    }

    // Step 1 validation
    document.getElementById('nextBtn1').addEventListener('click', () => {
      const firstName = document.getElementById('firstName').value.trim();
      const lastName  = document.getElementById('lastName').value.trim();
      const birthday  = document.getElementById('birthday').value.trim();
      const gender    = document.querySelector('input[name="gender"]:checked');

      if (!firstName) return Toast.fire({ icon: 'warning', title: 'First name is required.' });
      if (!lastName)  return Toast.fire({ icon: 'warning', title: 'Last name is required.' });
      if (!birthday)  return Toast.fire({ icon: 'warning', title: 'Birthday is required.' });
      if (!gender)    return Toast.fire({ icon: 'warning', title: 'Please select a gender.' });

      currentStep = 1;
      updateFormSteps();
    });

    // Step 2 validation
    document.getElementById('nextBtn2').addEventListener('click', () => {
      const email  = document.getElementById('emailAddress').value.trim();
      const phone  = document.getElementById('phoneNumber').value.trim();
      const street = document.getElementById('street').value.trim();
      const brgy   = document.getElementById('barangay').value.trim();
      const city   = document.getElementById('city').value.trim();

      if (!email)                                     return Toast.fire({ icon: 'warning', title: 'Email address is required.' });
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return Toast.fire({ icon: 'warning', title: 'Please enter a valid email address.' });
      if (!phone)                                     return Toast.fire({ icon: 'warning', title: 'Phone number is required.' });
      if (!/^\d{10,15}$/.test(phone))                 return Toast.fire({ icon: 'warning', title: 'Phone number must be 10-15 digits only.' });
      if (!street)                                    return Toast.fire({ icon: 'warning', title: 'Street is required.' });
      if (!brgy)                                      return Toast.fire({ icon: 'warning', title: 'Barangay is required.' });
      if (!city)                                      return Toast.fire({ icon: 'warning', title: 'City is required.' });

      currentStep = 2;
      updateFormSteps();
    });

    // Step 3 validation on submit
    document.getElementById('registrationForm').addEventListener('submit', (e) => {
      const username        = document.getElementById('username').value.trim();
      const password        = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirmPassword').value;
      const terms           = document.getElementById('acceptTerms').checked;

      if (!username || !password) {
        e.preventDefault();
        return Toast.fire({ icon: 'warning', title: 'Please fill in all account fields.' });
      }
      if (password !== confirmPassword) {
        e.preventDefault();
        return Toast.fire({ icon: 'warning', title: 'Passwords do not match.' });
      }
      if (!terms) {
        e.preventDefault();
        return Toast.fire({ icon: 'warning', title: 'You must accept the terms and conditions.' });
      }
    });

    // Navigation
    document.getElementById('prevBtn2').addEventListener('click', () => { currentStep = 0; updateFormSteps(); });
    document.getElementById('prevBtn3').addEventListener('click', () => { currentStep = 1; updateFormSteps(); });

    // PHP session flash message
    <?php if (isset($_SESSION['message']) && !empty($_SESSION['code'])): ?>
    Toast.fire({
      icon: '<?php echo $_SESSION['code']; ?>',
      title: '<?php echo addslashes($_SESSION['message']); ?>'
    });
    <?php unset($_SESSION['message'], $_SESSION['code']); ?>
    <?php endif; ?>
  </script>

</body>
</html>