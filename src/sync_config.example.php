<?php
/**
 * ORIGEN: servidor del cual se LEEN los datos.
 *
 * sync_tables.php LEE desde acá y ESCRIBE en la DB local (.env).
 *
 * En perfushopping.ar:
 *   origen = perfushopping.sytes.net (ERP con datos reales)
 *   destino = localhost (la DB de la web)
 */
return [
    'host' => 'perfushopping.sytes.net',
    'port' => '3306',
    'db'   => 'perfushopping',
    'user' => 'Javiery',
    'pass' => '',
];
