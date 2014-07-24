<?php
chdir(__DIR__);

if (!file_exists('vendor/autoload.php')) {
    echo '[ERROR] It\'s required to run "composer install" before building PhpMetrics!' . PHP_EOL;
    exit(1);
}

$filename = 'dissect.phar';
if (file_exists($filename)) {
    unlink($filename);
}

$phar = new \Phar($filename, 0, 'dissect.phar');
$phar->setSignatureAlgorithm(\Phar::SHA1);
$phar->startBuffering();


$files = array_merge(rglob('*.php'),  rglob('*.json'), rglob('*.pp'), rglob('*.*'));
$exclude = '!(\\.git)|(\\.svn)!';
foreach($files as $file) {
    if(preg_match($exclude, $file)) continue;
    $path = str_replace(__DIR__.'/', '', $file);
    $phar->addFromString($path, file_get_contents($file));
}

$phar->setStub(<<<STUB
#!/usr/bin/env php
<?php

Phar::mapPhar('dissect.phar');

require_once 'phar://dissect.phar/vendor/autoload.php';

\Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace('JMS\Serializer\Annotation', "phar://dissect.phar/vendor/jms/serializer/src");
\$application = new \Riper\Dissect\Application();
\$application->run();

__HALT_COMPILER();
STUB
);
$phar->stopBuffering();

chmod($filename, 0755);

function rglob($pattern='*', $flags = 0, $path='')
{
    $paths=glob($path.'*', GLOB_MARK|GLOB_ONLYDIR|GLOB_NOSORT);
    $files=glob($path.$pattern, $flags);
    foreach ($paths as $path) { $files=array_merge($files,rglob($pattern, $flags, $path)); }
    return $files;
}