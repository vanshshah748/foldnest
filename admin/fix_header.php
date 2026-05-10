<?php
$dir = __DIR__;
$files = glob($dir . '/*.html');

foreach ($files as $file) {
    if (basename($file) == 'index.html') continue;
    
    $content = file_get_contents($file);
    
    // Completely wipe and replace header-right
    $pattern = '/<div class="header-right">.*?<\/div>/s';
    $replacement = '<div class="header-right">' . "\n                <button id=\"theme-toggle\" class=\"theme-toggle-btn\" title=\"Toggle Theme\">☀️</button>\n                <span class=\"admin-badge\" id=\"admin-name\">Super Admin</span>\n            </div>";
    
    $content = preg_replace($pattern, $replacement, $content);
    
    file_put_contents($file, $content);
}
echo "Done";
?>
