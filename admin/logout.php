<?php
require_once '../includes/config.php';

// Destroy session
session_destroy();

// Redirect to login page
header('Location: index.php');
exit();