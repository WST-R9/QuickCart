<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');

$catId    = 10;
$catTitle = 'Personal Care';

include('includes/categoryPage.php');
include('includes/footer.php');