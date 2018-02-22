<?php
/**
 * @use php makeSound.php --src=voice.events --src-dir=./resources/audio --dst=sound.wav
 */
require __DIR__ . '/autoload.php';
require __DIR__ . '/functions.php';

$options = getopt('', ['src:', 'src-dir:', 'dst:']);
$srcFileName = realpath($options['src']);
$srcDirName  = realpath($options['src-dir']);
$dstFileName = realpath(dirname($options['dst'])) . DS . basename($options['dst']);

$src = fopen($srcFileName, 'r');
if (false === $src) {
    halt('Error while opening file: ' . $srcFileName . PHP_EOL);
}

$fragments = [];

while ($csv = fgetcsv($src, 1024)) {
    if ('start' === $csv[0]) {

        $fragmentSource = empty($srcDirName) ? $csv[2] : $srcDirName . DS . basename($csv[2]);

        if (isset($firstFragmentStart)) {
            $fragmentDelay = ((int)$csv[1] - $firstFragmentStart) / 1000;
            $delayPart = ' pad ' . $fragmentDelay . ' 0';
        } else {
            $firstFragmentStart = (int)$csv[1];
            $firstFragmentSource = $fragmentSource;
            $delayPart = '';
        }

        $fragments[] = ' -v 1 "|sox ' . $fragmentSource . ' -p' . $delayPart . '"';
    }
}

fclose($src);

if (isset($firstFragmentSource)) {
    exec('sox -m' . implode('', $fragments) . ' -b 16 -r 44100 -G ' .
        dirname($dstFileName) . DS . $firstFragmentStart . '.' . basename($dstFileName));
} else {
    halt('Voice fragments not found' . PHP_EOL);
}
