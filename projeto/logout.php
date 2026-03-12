<?php
session_start();
session_unset();
session_destroy();
header('Location: login.php?saiu=1');
exit;
