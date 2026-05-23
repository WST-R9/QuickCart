<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');

$catId    = 14; // add this category to DB when ready
$catTitle = 'Pet Supplies';

include('includes/categoryPage.php');
include('includes/footer.php');