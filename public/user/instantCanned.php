<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');

$catId    = 3;
$catTitle = 'Instant & Canned';

include('includes/categoryPage.php');
include('includes/footer.php');