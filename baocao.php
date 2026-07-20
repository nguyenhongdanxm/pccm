<?php
require_once 'includes/config.php';
header('Location: ' . BASE_URL . 'ketqua.php' . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
exit;
