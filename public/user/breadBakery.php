<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');

$catId    = 5;
$catTitle = 'Bread & Bakery';
$catIcon  = 'ri-bread-line';

include('includes/categoryPage.php');
include('includes/footer.php');