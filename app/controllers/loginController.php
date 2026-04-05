<?php
session_start();
include_once(__DIR__ . "/../config/config.php");

function generate_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

if (isset($_POST['loginButton'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Use prepared statement to prevent SQL injection
    $loginQuery = "SELECT `id`, `firstName`, `lastName`, `username`, `password`, `role`
                   FROM `users`
                   WHERE `username` = ?
                   LIMIT 1";
    $stmt = $conn->prepare($loginQuery);

    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && mysqli_num_rows($result) > 0) {
            $data = mysqli_fetch_assoc($result);

            // Plain-text password check
            if ($data['password'] !== $password) {
                $_SESSION['message'] = "Invalid username or password.";
                $_SESSION['code']    = "warning";
                header("Location: /WST-QuickCart/public/login");
                exit();
            }

            $_SESSION['user_id']  = $data['id'];
            $_SESSION['userRole'] = $data['role'];
            $_SESSION['authUser'] = [
                'user_id'  => $data['id'],
                'fullName' => $data['firstName'] . ' ' . $data['lastName'],
                'username' => $data['username'],
            ];

            $_SESSION['message'] = "Welcome " . $data['firstName'] . ' ' . $data['lastName'];
            $_SESSION['code']    = "success";

            if ($data['role'] === 'admin') {
                header("Location: /WST-QuickCart/public/admin/index");
            } else {
                header("Location: /WST-QuickCart/public/user/index");
            }
            exit();

        } else {
            $_SESSION['message'] = "Invalid username or password.";
            $_SESSION['code']    = "warning";
            header("Location: /WST-QuickCart/public/login");
            exit();
        }
    } else {
        $_SESSION['message'] = "Something went wrong. Please try again.";
        $_SESSION['code']    = "error";
        header("Location: /WST-QuickCart/public/login");
        exit();
    }
}

if (isset($_POST['registerButton'])) {

    // trim all text inputs
    $firstName   = trim(mysqli_real_escape_string($conn, $_POST['firstName']));
    $middleName  = trim(mysqli_real_escape_string($conn, $_POST['middleName']));
    $lastName    = trim(mysqli_real_escape_string($conn, $_POST['lastName']));
    $birthday    = trim($_POST['birthday']);   
    $gender      = trim($_POST['gender']);
    $emailAddress = trim(mysqli_real_escape_string($conn, $_POST['emailAddress']));
    $phoneNumber = trim($_POST['phoneNumber']);                   
    $street      = trim(mysqli_real_escape_string($conn, $_POST['street']));
    $barangay    = trim(mysqli_real_escape_string($conn, $_POST['barangay']));
    $city        = trim(mysqli_real_escape_string($conn, $_POST['city']));
    $username    = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $password    = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $role        = 'customer';
    $uuid        = generate_uuid();

    // Validations
    // Required fields
    if (!$firstName || !$lastName || !$birthday || !$gender ||
        !$emailAddress || !$phoneNumber || !$street || !$barangay || !$city || !$username || !$password) {
        $_SESSION['message'] = "Please fill in all required fields.";
        $_SESSION['code']    = "error";
        header("Location: /WST-QuickCart/public/registration");
        exit();
    }

    // Valid email
    if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "Invalid email format.";
        $_SESSION['code']    = "error";
        header("Location: /WST-QuickCart/public/registration");
        exit();
    }

    // Phone: digits only, 10–15 chars (matches decimal(15,0) column)
    if (!preg_match('/^\d{10,15}$/', $phoneNumber)) {
        $_SESSION['message'] = "Phone number must contain 10–15 digits only (no '+' or spaces).";
        $_SESSION['code']    = "error";
        header("Location: /WST-QuickCart/public/registration");
        exit();
    }

    // Valid date format YYYY-MM-DD
    $dateParts = explode('-', $birthday);
    if (count($dateParts) !== 3 || !checkdate((int)$dateParts[1], (int)$dateParts[2], (int)$dateParts[0])) {
        $_SESSION['message'] = "Invalid birthday format.";
        $_SESSION['code']    = "error";
        header("Location: /WST-QuickCart/public/registration");
        exit();
    }

    // Valid gender (must match enum)
    if (!in_array($gender, ['Male', 'Female'])) {
        $_SESSION['message'] = "Invalid gender value.";
        $_SESSION['code']    = "error";
        header("Location: /WST-QuickCart/public/registration");
        exit();
    }

    // Passwords match
    if ($password !== $confirmPassword) {
        $_SESSION['message'] = "Passwords do not match.";
        $_SESSION['code']    = "error";
        header("Location: /WST-QuickCart/public/registration");
        exit();
    }

    // Duplicate username
    $checkUsername = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $checkUsername->bind_param("s", $username);
    $checkUsername->execute();
    $checkUsername->store_result();
    if ($checkUsername->num_rows > 0) {
        $_SESSION['message'] = "Username already taken.";
        $_SESSION['code']    = "error";
        header("Location: /WST-QuickCart/public/registration");
        exit();
    }
    $checkUsername->close();

    // Duplicate email
    $checkEmail = $conn->prepare("SELECT id FROM users WHERE emailAddress = ? LIMIT 1");
    $checkEmail->bind_param("s", $emailAddress);
    $checkEmail->execute();
    $checkEmail->store_result();
    if ($checkEmail->num_rows > 0) {
        $_SESSION['message'] = "Email address already registered.";
        $_SESSION['code']    = "error";
        header("Location: /WST-QuickCart/public/registration");
        exit();
    }
    $checkEmail->close();

    // --- Insert using a prepared statement ---
    $insertQuery = "INSERT INTO `users`
        (`uuid`, `firstName`, `middleName`, `lastName`, `birthday`, `gender`,
         `emailAddress`, `username`, `password`, `phoneNumber`, `street`, `barangay`, `city`, `role`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($insertQuery);
    if ($stmt) {
        $stmt->bind_param(
            "ssssssssssssss",
            $uuid, $firstName, $middleName, $lastName, $birthday, $gender,
            $emailAddress, $username, $password, $phoneNumber, $street, $barangay, $city, $role
        );

        if ($stmt->execute()) {
            $_SESSION['message'] = "Registration successful. Please log in.";
            $_SESSION['code']    = "success";
            header("Location: /WST-QuickCart/public/login");
            exit();
        } else {
            $_SESSION['message'] = "Database error: " . $stmt->error;
            $_SESSION['code']    = "error";
            header("Location: /WST-QuickCart/public/registration");
            exit();
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Query preparation failed: " . $conn->error;
        $_SESSION['code']    = "error";
        header("Location: /WST-QuickCart/public/registration");
        exit();
    }
}