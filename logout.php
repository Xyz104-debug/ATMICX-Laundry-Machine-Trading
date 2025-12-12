<?php
session_start();
session_destroy();
header('Location: atmicxLOGIN.html');
exit;
?>