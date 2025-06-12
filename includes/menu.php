<?php
$user_rol = $_SESSION['user_rol'];

// Menú para Administrador
if($user_rol === 'administrador') {
    ?>
    <a href="modulos/ups/index.php" class="list-group-item list-group-item-action">
        <i class="fas fa-battery-full"></i> UPS
    </a>
    <a href="modulos/informe_tecnico/index.php" class="list-group-item list-group-item-action">
        <i class="fas fa-clipboard-list"></i> Informe Técnico
    </a>
    <a href="modulos/acuse_recibo/index.php" class="list-group-item list-group-item-action">
        <i class="fas fa-file-alt"></i> Acuse de Recibo
    </a>
    <?php
}

// Menú para Técnico
if($user_rol === 'tecnico') {
    ?>
    <a href="modulos/informe_tecnico/index.php" class="list-group-item list-group-item-action">
        <i class="fas fa-clipboard-list"></i> Informe Técnico
    </a>
    <a href="modulos/acuse_recibo/index.php" class="list-group-item list-group-item-action">
        <i class="fas fa-file-alt"></i> Acuse de Recibo
    </a>
    <?php
}
?>