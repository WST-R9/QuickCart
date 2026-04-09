<?php
session_start();
include_once(__DIR__ . "/../config/config.php");

function generate_uuid()
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

if (isset($_POST['loginButton'])) {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $loginQuery = "SELECT `userId`, `firstName`, `lastName`, `username`, `password`, `role`
                   FROM `users`
                   WHERE `username` = ?
                   LIMIT 1";

    $stmt = $conn->prepare($loginQuery);

    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $data = $result->fetch_assoc();

            // Plain-text password check (NOTE: should be hashed in real apps)
            if ($data['password'] !== $password) {
                $_SESSION['message'] = "Invalid username or password.";
                $_SESSION['code'] = "warning";
                header("Location: /WST-QuickCart/public/login");
                exit();
            }

            $_SESSION['user_id'] = $data['userId'];
            $_SESSION['userRole'] = $data['role'];

            $_SESSION['authUser'] = [
                'user_id' => $data['userId'],
                'fullName' => $data['firstName'] . ' ' . $data['lastName'],
                'username' => $data['username'],
            ];

            $_SESSION['message'] = "Welcome " . $data['firstName'] . " " . $data['lastName'];
            $_SESSION['code'] = "success";

            if ($data['role'] === 'admin') {
                header("Location: /WST-QuickCart/public/admin/index");
                exit();
            } else {
                header("Location: /WST-QuickCart/public/user/index");
                exit();
            }

        } else {
            $_SESSION['message'] = "Invalid username or password.";
            $_SESSION['code'] = "warning";
            header("Location: /WST-QuickCart/public/login");
            exit();
        }

    } else {
        $_SESSION['message'] = "Something went wrong. Please try again.";
        $_SESSION['code'] = "error";
        header("Location: /WST-QuickCart/public/login");
        exit();
    }
}


if (isset($_POST['registerButton'])) {

    // USER INFO
    $firstName = trim(mysqli_real_escape_string($conn, $_POST['firstName']));
    $middleName = trim(mysqli_real_escape_string($conn, $_POST['middleName']));
    $lastName = trim(mysqli_real_escape_string($conn, $_POST['lastName']));
    $birthday = trim($_POST['birthday']);
    $gender = trim($_POST['gender']);
    $emailAddress = trim(mysqli_real_escape_string($conn, $_POST['emailAddress']));
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $phoneNumber = trim($_POST['phoneNumber']);

    // ADDRESS INFO
    $street = trim(mysqli_real_escape_string($conn, $_POST['street']));
    $barangay = trim(mysqli_real_escape_string($conn, $_POST['barangay']));
    $city = trim(mysqli_real_escape_string($conn, $_POST['city']));
    $province = trim(mysqli_real_escape_string($conn, $_POST['province']));
    $zipCode = trim(mysqli_real_escape_string($conn, $_POST['zipCode']));

    // OPTIONAL address fields (not in your form yet)
    $province = NULL;
    $zipCode = NULL;

    $role = "customer";
    $uuid = generate_uuid();

    // REQUIRED CHECK
    if (
        !$firstName || !$lastName || !$birthday || !$gender ||
        !$emailAddress || !$phoneNumber ||
        !$street || !$barangay || !$city || !$province || !$zipCode ||
        !$username || !$password
    ) {
        $_SESSION['message'] = "Please fill in all required fields.";
        $_SESSION['code'] = "error";
        header("Location: /WST-QuickCart/public/registration");
        exit();
    }

    //ZIP VALIDATION
    if (!preg_match('/^\d{4,10}$/', $zipCode)) {
        $_SESSION['message'] = "Zip Code must be 4-10 digits only.";
        $_SESSION['code'] = "error";
        header("Location: /WST-QuickCart/public/registration");
        exit();
    }

    // VALID EMAIL
    if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "Invalid email format.";
        $_SESSION['code'] = "error";
        header("Location: /WST-QuickCart/public/registration");
        exit();
    }

    // VALID PHONE
    if (!preg_match('/^\d{10,15}$/', $phoneNumber)) {
        $_SESSION['message'] = "Phone number must contain 10–15 digits only.";
        $_SESSION['code'] = "error";
        header("Location: /WST-QuickCart/public/registration");
        exit();
    }

    // VALID GENDER
    if (!in_array($gender, ['Male', 'Female'])) {
        $_SESSION['message'] = "Invalid gender value.";
        $_SESSION['code'] = "error";
        header("Location: /WST-QuickCart/public/registration");
        exit();
    }

    // PASSWORD MATCH
    if ($password !== $confirmPassword) {
        $_SESSION['message'] = "Passwords do not match.";
        $_SESSION['code'] = "error";
        header("Location: /WST-QuickCart/public/registration");
        exit();
    }

    // CHECK DUPLICATE USERNAME
    $checkUsername = $conn->prepare("SELECT userId FROM users WHERE username = ? LIMIT 1");
    $checkUsername->bind_param("s", $username);
    $checkUsername->execute();
    $checkUsername->store_result();

    if ($checkUsername->num_rows > 0) {
        $_SESSION['message'] = "Username already taken.";
        $_SESSION['code'] = "error";
        header("Location: /WST-QuickCart/public/registration");
        exit();
    }
    $checkUsername->close();

    // CHECK DUPLICATE EMAIL
    $checkEmail = $conn->prepare("SELECT userId FROM users WHERE emailAddress = ? LIMIT 1");
    $checkEmail->bind_param("s", $emailAddress);
    $checkEmail->execute();
    $checkEmail->store_result();

    if ($checkEmail->num_rows > 0) {
        $_SESSION['message'] = "Email address already registered.";
        $_SESSION['code'] = "error";
        header("Location: /WST-QuickCart/public/registration");
        exit();
    }
    $checkEmail->close();


    // TRANSACTION (IMPORTANT)
    $conn->begin_transaction();

    try {

        // INSERT USER
        $insertUserQuery = "INSERT INTO `users`
            (`uuid`, `firstName`, `middleName`, `lastName`, `birthday`, `gender`,
             `emailAddress`, `username`, `password`, `phoneNumber`, `role`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmtUser = $conn->prepare($insertUserQuery);

        if (!$stmtUser) {
            throw new Exception("User insert prepare failed: " . $conn->error);
        }

        $stmtUser->bind_param(
            "sssssssssss",
            $uuid,
            $firstName,
            $middleName,
            $lastName,
            $birthday,
            $gender,
            $emailAddress,
            $username,
            $password,
            $phoneNumber,
            $role
        );

        if (!$stmtUser->execute()) {
            throw new Exception("User insert failed: " . $stmtUser->error);
        }

        $userId = $conn->insert_id;
        $stmtUser->close();


        // INSERT ADDRESS (required)
        $insertAddressQuery = "INSERT INTO `addresses`
            (`userId`, `label`, `recipientName`, `phoneNumber`,
             `street`, `barangay`, `city`, `province`, `zipCode`, `isDefault`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmtAddress = $conn->prepare($insertAddressQuery);

        if (!$stmtAddress) {
            throw new Exception("Address insert prepare failed: " . $conn->error);
        }

        $label = "Home";
        $recipientName = $firstName . " " . $lastName;
        $isDefault = 1;

        $stmtAddress->bind_param(
            "issssssssi",
            $userId,
            $label,
            $recipientName,
            $phoneNumber,
            $street,
            $barangay,
            $city,
            $province,
            $zipCode,
            $isDefault
        );

        if (!$stmtAddress->execute()) {
            throw new Exception("Address insert failed: " . $stmtAddress->error);
        }

        $stmtAddress->close();


        // COMMIT BOTH INSERTS
        $conn->commit();

        $_SESSION['message'] = "Registration successful. Please log in.";
        $_SESSION['code'] = "success";
        header("Location: /WST-QuickCart/public/login");
        exit();

    } catch (Exception $e) {

        // ROLLBACK IF ANY FAILS
        $conn->rollback();

        $_SESSION['message'] = "Registration failed: " . $e->getMessage();
        $_SESSION['code'] = "error";
        header("Location: /WST-QuickCart/public/registration");
        exit();
    }
}
?>