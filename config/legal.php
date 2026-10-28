<?php

return [

    /*
     * Versión vigente de los términos y la política de privacidad, juntos.
     *
     * Subirla vuelve a pedir la aceptación a todos en su próxima visita: es lo
     * que hay que hacer cada vez que cambia algo importante de los textos de
     * `resources/legal/`. Un cambio de redacción que no cambia nada no la sube.
     */
    'version' => '2026-10-27',

    /*
     * A dónde escribir por privacidad, términos o para ejercer derechos.
     */
    'contact_email' => env('LEGAL_CONTACT_EMAIL', 'pablo.mandile@gmail.com'),

];
