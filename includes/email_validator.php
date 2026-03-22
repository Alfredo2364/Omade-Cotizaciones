<?php
// includes/email_validator.php

function is_valid_email_strict($email) {
    $email = trim($email);

    // 1. Basic Format Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'message' => 'Formato de correo inválido.'];
    }

    // Extract Domain
    $parts = explode('@', $email);
    $domain = strtolower(array_pop($parts));

    // 2. Disposable Email Blocklist
    $disposable_domains = [
        'mailinator.com', 'yopmail.com', '10minutemail.com', 'guerrillamail.com',
        'tempmail.com', 'temp-mail.org', 'throwawaymail.com', 'tempmailaddress.com',
        'mohmal.com', 'dispostable.com', 'getnada.com', 'sharklasers.com',
        'guerillamail.info', 'guerillamail.biz', 'guerillamail.com', 'guerillamail.de',
        'guerillamail.net', 'guerillamail.org', 'guerillamailblock.com', 
        'spam4.me', 'grr.la', 'spamgourmet.com', 'maildrop.cc', 'tempmailx.com'
    ];

    if (in_array($domain, $disposable_domains)) {
        return ['valid' => false, 'message' => 'No se permiten proveedores de correo temporal o desechable.'];
    }

    // 3. DNS MX Record Check
    // Check if the domain has a mail exchanger record
    if (function_exists('checkdnsrr')) {
        if (!checkdnsrr($domain, 'MX')) {
            // As a fallback, check for an A record if MX doesn't exist 
            // (Some systems allow mail receipt to A records if MX is absent, but usually no MX = no mail)
            if (!checkdnsrr($domain, 'A')) {
                return ['valid' => false, 'message' => 'El dominio del correo no existe o no puede recibir mensajes.'];
            }
        }
    }

    return ['valid' => true, 'message' => 'Correo válido.'];
}
?>
