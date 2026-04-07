<?php
include_once(__DIR__ . '/../../app/middleware/user.php');
include_once(__DIR__ . '/../../app/helpers/flashMessage.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>

</head>
<body>
    <h1>Hello, <?php echo $_SESSION['authUser']['username']; ?>!</h1>

    <!-- Logout Form -->
    <form action="/WST-QuickCart/app/controllers/userController.php" method="POST">
        <button type="submit" name="logoutButton">Logout</button>
    </form>

        <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php
    // Display flash message here after SweetAlert2 is loaded
    flashMessage();
    ?>
</body>
</html>