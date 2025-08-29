<?php
if(!isset($_SESSION['user_rol']) || !in_array($_SESSION['user_rol'], ['administrador', 'supervisor'])) {
    header("Location: index.php");
    exit;
}
?>