<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');

$catId    = 6;
$catTitle = 'Fruits & Vegetables';
$catIcon  = 'ri-plant-line';

include('includes/categoryPage.php');
include('includes/footer.php');