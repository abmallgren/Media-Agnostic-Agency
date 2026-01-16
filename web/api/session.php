<?php

function ensure_session_started(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            // 'secure' => true, // enable under HTTPS
        ]);
        session_start();
    }
}