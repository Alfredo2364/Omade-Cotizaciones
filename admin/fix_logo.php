<?php
$source = 'c:/xampp/htdocs/Pagina web/assets/images/logo/Logo_pagina.png';
$dest = 'c:/xampp/htdocs/Pagina web/admin/logo_admin.png';

echo "Intentando copiar: $source -> $dest\n";

if (file_exists($source)) {
    if (copy($source, $dest)) {
        echo "EXITO: Logo copiado correctamente.";
    } else {
        echo "ERROR: Falló copy().";
        print_r(error_get_last());
    }
} else {
    echo "ERROR: Archivo no existe en path: " . $source;
}
?>
