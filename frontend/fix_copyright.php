<?php
$rootDir = __DIR__;
$pagesDir = __DIR__ . '/pages';

function fixCopyright($dir) {
    $files = glob($dir . '/*.html');
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $content = str_replace('Â©', '©', $content);
        file_put_contents($file, $content);
    }
}

fixCopyright($rootDir);
fixCopyright($pagesDir);
echo "Done";
?>
