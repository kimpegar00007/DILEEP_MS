<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';

$auth = new Auth();
$auth->logout();

header('Location: login.php?message=logged_out');
exit;
