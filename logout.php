<?php
require_once 'includes/functions.php';
logout();
header('Location: ' . BASE_URL . 'login.php');
exit;
