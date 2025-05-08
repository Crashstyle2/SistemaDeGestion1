<?php
// Establecer tiempo de vida de la sesión a 30 minutos
ini_set('session.gc_maxlifetime', 1800);
session_set_cookie_params(1800);