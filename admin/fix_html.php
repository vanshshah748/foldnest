<?php
$dir = __DIR__;
$files = glob($dir . '/*.html');
foreach ($files as $file) {
    if (basename($file) == 'index.html') continue;
    $content = file_get_contents($file);
    // Replace the button regex
    $content = preg_replace(
        '/<button id="theme-toggle"[^>]*>.*?<\/button>/s',
        '<button id="theme-toggle" class="theme-toggle-btn" title="Toggle Theme">☀️</button>',
        $content
    );
    file_put_contents($file, $content);
}
?>
