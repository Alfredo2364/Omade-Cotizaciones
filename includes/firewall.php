<?php
// includes/firewall.php
// Protección sencilla a nivel de aplicación (WAF)

// 1. Limitar el tamaño del Payload para evitar inyecciones masivas (Max 256KB)
$max_payload_size = 256 * 1024;
if (isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > $max_payload_size) {
    http_response_code(413);
    die("Error 413: Petición demasiado grande.");
}

// 2. Limitar bucles inyectados (Post Max Vars Protection)
// Si algún atacante inyecta miles de variables en POST
if (is_array($_POST) && count($_POST) > 100) {
    http_response_code(400);
    die("Error 400: Sobrecarga de campos en formulario.");
}

// 3. Limitador de Peticiones (Rate Limiting) por IP
// Previene ataques DDoS de nivel 7 (HTTP Flood)
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Omitir local para no entorpecer pruebas
if ($ip !== '127.0.0.1' && $ip !== '::1' && $ip !== 'unknown') {
    $hash = md5($ip);
    // Usar la carpeta temporal del sistema
    $dir = sys_get_temp_dir() . '/omade_cache';
    
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    
    $file = $dir . '/' . $hash;
    $window = 60; // Ventana de 60 segundos
    $limit = 150; // Máximo 150 peticiones por minuto (Suficiente para uso normal, bloquea bots)
    
    $current_time = time();
    
    // Auto-limpieza probabilística de archivos viejos (1% de las veces)
    if (mt_rand(1, 100) === 1) {
        $files = glob($dir . '/*');
        if($files){
            foreach($files as $f) {
                if ($current_time - filemtime($f) > $window) {
                    @unlink($f);
                }
            }
        }
    }

    $requests = 1;
    if (file_exists($file)) {
        if ($current_time - filemtime($file) > $window) {
            // Reiniciar contador
            @file_put_contents($file, '1');
        } else {
            // Dentro de la ventana
            $requests = (int)@file_get_contents($file) + 1;
            if ($requests > $limit) {
                http_response_code(429);
                die("Error 429: Demasiadas peticiones. Por favor, espera un minuto para evitar la saturación del servidor.");
            }
            @file_put_contents($file, (string)$requests);
        }
    } else {
        @file_put_contents($file, '1');
    }
}
?>
