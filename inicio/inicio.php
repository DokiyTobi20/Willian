<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<div class="welcome-message" style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;min-height:200px;text-align:center;">
    <div class="welcome-icon" style="font-size:48px;color:var(--primary-color,#667eea);">
        <i class="bx bx-home-alt"></i>
    </div>
    <h2 style="margin:0;">¡Bienvenido!</h2>
    <p style="margin:0;color:#6b7280;">Este es el módulo de inicio. Usa el menú lateral para navegar.</p>
</div>