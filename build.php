<?php
chdir(__DIR__);

if (!file_exists('vendor/autoload.php')) {
    echo '[ERROR] It\'s required to run "composer install" before building PhpMetrics!' . PHP_EOL;
    exit(1);
}
$longopts = array(
    "version:", // Valeur requise
);
$options  = getopt("", $longopts);

if (!isset($options['version'])) {
    echo "ERROR : ".'The parameter "version" must be set' ;
    throw new RuntimeException('The parameter "version" must be set');
}
$version = $options["version"];
echo "Compiling version " . $version . "\n";


$filename = 'dissect.phar';
if (file_exists($filename)) {
    echo "Removing older build\n";
    unlink($filename);
}

echo "Creating phar object\n";
$phar = new \Phar($filename, 0, 'dissect.phar');
$phar->setSignatureAlgorithm(\Phar::SHA1);
$phar->startBuffering();
$phar->convertToExecutable(Phar::ZIP);

echo "Looking for files to include\n";
$files   = array_merge(rglob('*.*'));
$exclude = '!(\\.git)|(\\.svn)!i';
echo "Adding files to phar\n";
$nb = count($files);
$c =0;
foreach ($files as $file) {
    echo "$c/$nb    $file";
    $c++;
    if (preg_match($exclude, $file)) {
        echo "...skip\n";
        continue;
    }
    $path = str_replace(__DIR__ . '/', '', $file);
    $phar->addFromString($path, file_get_contents($file));
    echo "...done\n";
}

echo "Adding Stub to phar\n";
$phar->setStub(<<<STUB
#!/usr/bin/env php
<?php

Phar::mapPhar('dissect.phar');

require_once 'phar://dissect.phar/vendor/autoload.php';

\Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace('JMS\Serializer\Annotation', "phar://dissect.phar/vendor/jms/serializer/src");
\$application = new \Riper\Dissect\Application("$version");
\$application->run();

__HALT_COMPILER();
STUB
);
$phar->stopBuffering();

echo "Setting phar file executable\n";
chmod($filename, 0755);

echo "Done \n";

function rglob($pattern = '*', $flags = 0, $path = '')
{
    $paths = glob($path . '*', GLOB_MARK | GLOB_ONLYDIR | GLOB_NOSORT);
    $files = glob($path . $pattern, $flags);
    foreach ($paths as $path) {
        $files = array_merge($files, rglob($pattern, $flags, $path));
    }

    return $files;
}