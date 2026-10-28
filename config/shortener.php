<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dominio corto
    |--------------------------------------------------------------------------
    |
    | Dominio con el que se arman los links cortos. Se usa también para
    | rechazar URLs que apunten al propio acortador (evita loops).
    |
    */

    'domain' => env('SHORT_DOMAIN', 'zep.com.ar'),

    /*
    |--------------------------------------------------------------------------
    | Administrador
    |--------------------------------------------------------------------------
    |
    | La cuenta con este email queda aprobada y como administradora al
    | registrarse (con contraseña o con Google).
    |
    */

    'admin_email' => env('ADMIN_EMAIL'),

    /*
    |--------------------------------------------------------------------------
    | Códigos
    |--------------------------------------------------------------------------
    |
    | Los códigos aleatorios arrancan en "min_length" caracteres. Si después de
    | "attempts_per_length" intentos siguen chocando con códigos existentes,
    | se agrega un caracter más. El alfabeto excluye caracteres ambiguos
    | (0, O, 1, l, I) para que un link se pueda dictar.
    |
    */

    'alphabet' => 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789',

    'min_length' => (int) env('SHORT_CODE_MIN_LENGTH', 3),

    'max_length' => 12,

    'attempts_per_length' => 5,

    /*
    |--------------------------------------------------------------------------
    | Alias personalizados
    |--------------------------------------------------------------------------
    */

    'alias' => [
        'min' => 3,
        'max' => 50,
        'pattern' => '/^[A-Za-z0-9_-]+$/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Palabras reservadas
    |--------------------------------------------------------------------------
    |
    | Nunca pueden ser un código porque chocan (o podrían chocar a futuro) con
    | rutas de la app. Se comparan sin distinguir mayúsculas.
    |
    */

    'reserved' => [
        'admin', 'api', 'app', 'auth', 'build', 'dashboard', 'email', 'favicon',
        'forgot-password', 'help', 'links', 'login', 'logout', 'panel', 'passkeys',
        'pendiente', 'privacidad', 'register', 'reset-password', 'robots', 'sanctum',
        'settings', 'stats', 'storage', 'terminos', 'two-factor-challenge', 'up',
        'user', 'user-confirmed-password-status', 'verify-email', 'www', 'zep',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dominios bloqueados
    |--------------------------------------------------------------------------
    |
    | Otros acortadores: no se permite acortar un link ya acortado.
    |
    */

    'blocked_hosts' => [
        'bit.ly', 'goo.gl', 'tinyurl.com', 't.co', 'ow.ly', 'is.gd', 'buff.ly',
        'cutt.ly', 'rebrand.ly', 'shorturl.at', 'rb.gy', 'tiny.cc',
    ],

    /*
    |--------------------------------------------------------------------------
    | Límites
    |--------------------------------------------------------------------------
    */

    'rate_limits' => [
        'web_create_per_minute' => 30,
        'api_per_minute' => 60,
    ],

];
