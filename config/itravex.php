<?php
return [
    'endpoint' => getenv('ITRAVEX_ENDPOINT') ?: 'http://pre-htt-lib.dome-consulting.com/LIBSRV/xmlincomingservice.srv',
    'codsys'   => getenv('ITRAVEX_CODSYS')   ?: 'XML',
    'codage'   => getenv('ITRAVEX_CODAGE')   ?: 'NNN', // Lo reemplazarás
    'user'     => getenv('ITRAVEX_USER')     ?: 'XXX',
    'pass'     => getenv('ITRAVEX_PASS')     ?: 'XXX',
    'codtou'   => getenv('ITRAVEX_CODTOU')   ?: 'LIB',  // 👈 valor por defecto
    'codnac'   => getenv('ITRAVEX_CODNAC')   ?: 'ESP',  // 👈 valor por defecto (España)
];
