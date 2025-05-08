<?php
if(!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'administrador') {
    header("Location: index.php");
    exit;
}
?>