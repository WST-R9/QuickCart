<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');

$catId    = 13;
$catTitle = 'Health & Wellness';
$catIcon  = 'ri-heart-pulse-line';

include('includes/categoryPage.php');
include('includes/footer.php');