<?php
include_once("../../app/middleware/guest.php");
include_once("../../app/config/config.php");
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');

$catId    = 6;
$catTitle = 'Personal Care';
$catIcon  = 'ri-hand-sanitizer-line';

include('includes/categoryPage.php');
include('includes/footer.php');
