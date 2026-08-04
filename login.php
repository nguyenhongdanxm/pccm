<?php
require_once 'includes/functions.php';

$next = BASE_URL . 'index.php';
header('Location: /login.php?next=' . urlencode($next));
exit;
