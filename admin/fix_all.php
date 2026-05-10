<?php
$dir = __DIR__;
$files = glob($dir . '/*.html');

foreach ($files as $file) {
    if (basename($file) == 'index.html') continue;
    
    $content = file_get_contents($file);
    
    // 1. Ensure theme.js is included before </body>
    if (strpos($content, '<script src="js/theme.js"></script>') === false) {
        $content = str_replace('</body>', "    <script src=\"js/theme.js\"></script>\n</body>", $content);
    }
    
    // 2. Ensure theme-toggle button exists in header-right
    if (strpos($content, 'id="theme-toggle"') === false) {
        $content = str_replace(
            '<div class="header-right">', 
            '<div class="header-right">' . "\n                <button id=\"theme-toggle\" class=\"theme-toggle-btn\" title=\"Toggle Theme\">☀️</button>", 
            $content
        );
    } else {
        // Fix mangled buttons if any
        $content = preg_replace(
            '/<button id="theme-toggle"[^>]*>.*?<\/button>/s',
            '<button id="theme-toggle" class="theme-toggle-btn" title="Toggle Theme">☀️</button>',
            $content
        );
    }
    
    file_put_contents($file, $content);
}
echo "Done";
?>
