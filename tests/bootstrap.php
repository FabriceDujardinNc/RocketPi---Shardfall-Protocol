<?php

/**
 * Bootstrap des tests — exécuté avant Pest/PHPUnit.
 *
 * Force les vars d'environnement critiques DANS $_SERVER avant que Laravel
 * ne boot. Sans ça, docker-compose injecte DB_*, REDIS_* dans $_SERVER et
 * Laravel les lit en priorité — les tests RefreshDatabase wipent alors la dev MySQL.
 *
 * PHPUnit `<server force="true">` n'est pas fiable en v12, donc on impose ici.
 */
$overrides = [
    'APP_ENV'                => 'testing',
    'APP_MAINTENANCE_DRIVER' => 'file',
    'BCRYPT_ROUNDS'          => '4',
    'BROADCAST_CONNECTION'   => 'null',
    'CACHE_STORE'            => 'array',
    'DB_CONNECTION'          => 'sqlite',
    'DB_DATABASE'            => ':memory:',
    'DB_URL'                 => '',
    'MAIL_MAILER'            => 'array',
    'QUEUE_CONNECTION'       => 'sync',
    'SESSION_DRIVER'         => 'array',
    'REDIS_HOST'             => 'redis',
    // REDIS_PASSWORD est volontairement absent : on laisse Laravel le lire
    // depuis .env (la même valeur qui authentifie l'app en dev). Le démon Redis
    // est démarré avec --requirepass donc le password est requis.
    'REDIS_DB'               => '15',
    'REDIS_CACHE_DB'         => '15',
    'PULSE_ENABLED'          => 'false',
    'TELESCOPE_ENABLED'      => 'false',
    'NIGHTWATCH_ENABLED'     => 'false',
];

foreach ($overrides as $key => $value) {
    $_SERVER[$key] = $value;
    $_ENV[$key]    = $value;
    putenv("{$key}={$value}");
}

require __DIR__ . '/../vendor/autoload.php';
