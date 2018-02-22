<?php
/**
 * @use php extractCursorEvents.php --src=events.xml --dst=events.new.xml > cursor.events
 */
require __DIR__ . '/autoload.php';
require __DIR__ . '/functions.php';

$options = getopt('', ['src:', 'dst:']);
$srcFileName = realpath($options['src']);
$dstFileName = $options['dst'];

if (!is_readable($srcFileName)) {
    halt('File does not exist or is not readable');
}

$events = new \ProfIT\Bbb\EventsFile($srcFileName);

try {
    $fragments = $events->extractFragments(
        '~<event.+eventname="CursorMoveEvent">~',
        '~</event>~',
        $dstFileName
    );
    
    foreach ($fragments as $fragment) {
        $eventParams = [];

        if (preg_match('~<event\s+timestamp="(\d+)".+>~U', $fragment, $m)) {
            $eventParams[0] = $m[1];
        }
        if (preg_match('~<xOffset>([\d\.]+)</xOffset>~', $fragment, $m)) {
            $eventParams[1] = $m[1];
        }
        if (preg_match('~<yOffset>([\d\.]+)</yOffset>~', $fragment, $m)) {
            $eventParams[2] = $m[1];
        }

        fputcsv(STDOUT, $eventParams);
    }
} catch (\ProfIT\Bbb\Exception $e) {
    halt($e->getMessage() . PHP_EOL);
}

