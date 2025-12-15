<?php
session_start();
session_destroy();
header('Location: clientLOGIN.html');
exit;
?>