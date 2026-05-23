<?php
include_once("../../app/middleware/guest.php");
include_once("../../app/config/config.php");
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');

$catId    = 5;
$catTitle = 'Frozen Foods';
$catIcon  = 'ri-temp-cold-line';

include('includes/categoryPage.php');
include('includes/footer.php');
