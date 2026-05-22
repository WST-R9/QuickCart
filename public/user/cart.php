<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');
?>

<body>
    <div class="pagetitle">
        <h1>My Cart</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index">Home</a></li>
                <li class="breadcrumb-item active">My Cart</li>
            </ol>
        </nav>
    </div>
</body>
</html>

<?php
include('includes/footer.php');
?>