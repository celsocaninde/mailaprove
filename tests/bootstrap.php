<?php

$glpiRoot = dirname(__DIR__, 3);

if (!file_exists($glpiRoot . '/vendor/autoload.php')) {
    throw new \RuntimeException("GLPI não encontrado em '{$glpiRoot}'. Execute dentro do container glpi-fpm.");
}

chdir($glpiRoot);

require_once $glpiRoot . '/src/Glpi/Application/ResourcesChecker.php';
(new \Glpi\Application\ResourcesChecker($glpiRoot))->checkResources();
require_once $glpiRoot . '/vendor/autoload.php';

$pluginAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($pluginAutoload)) {
    require_once $pluginAutoload;
}

// PSR-4 manual para o namespace de testes (necessário ao rodar via phar)
spl_autoload_register(static function (string $class): void {
    $prefix = 'GlpiPlugin\\Mailaprove\\Tests\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

$kernel = new \Glpi\Kernel\Kernel('production');
$kernel->boot();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$auth = new Auth();
if (!$auth->login('glpi', 'glpi', true)) {
    throw new \RuntimeException("Falha no login GLPI para testes. Verifique as credenciais do usuário 'glpi'.");
}
