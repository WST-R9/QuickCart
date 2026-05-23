<?php
include_once("../../app/middleware/guest.php");
include_once("../../app/config/config.php");
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');

$catId    = 13;
$catTitle = 'Baby & Kids';
$catIcon  = 'ri-user-smile-line';

include('includes/categoryPage.php');
include('includes/footer.php');
