<?php
include_once("../../app/middleware/guest.php");
include_once("../../app/config/config.php");
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');

$catId    = 4;
$catTitle = 'Dairy & Eggs';
$catIcon  = 'ri-drop-line';

include('includes/categoryPage.php');
include('includes/footer.php');
