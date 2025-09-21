<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function generarTokenCsrf(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validarTokenCsrf(?string $token): bool {
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function limpiarCadena(?string $valor): string {
    return trim((string) $valor);
}

function escaparHtml(string $valor): string {
    return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
