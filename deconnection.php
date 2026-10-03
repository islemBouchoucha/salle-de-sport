<?php
// deconnexion.php
session_start();
session_destroy();
header('Location: proj.html');
exit;
?>