<?php
require 'db.php';
session_destroy();
header('Location: login.php');
exit;
?>