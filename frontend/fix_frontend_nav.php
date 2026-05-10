<?php
$rootDir = __DIR__;
$pagesDir = __DIR__ . '/pages';

function getNavLinks($isRoot) {
    $home = $isRoot ? 'index.html' : '../index.html';
    $products = $isRoot ? 'pages/products.html' : 'products.html';
    $about = $isRoot ? 'pages/about.html' : 'about.html';
    $contact = $isRoot ? 'pages/contact.html' : 'contact.html';
    $orders = $isRoot ? 'pages/order_history.html' : 'order_history.html';
    $cart = $isRoot ? 'pages/cart.html' : 'cart.html';
    $profile = $isRoot ? 'pages/profile.html' : 'profile.html';

    return '<ul class="nav-links">
        <li><button class="theme-toggle" onclick="toggleTheme()" title="Toggle Dark Mode">🌙</button></li>
        <li><a href="' . $home . '">Home</a></li>
        <li><a href="' . $products . '">Products</a></li>
        <li><a href="' . $about . '">About</a></li>
        <li><a href="' . $contact . '">Contact</a></li>
        <li><a href="' . $orders . '">Orders</a></li>
        <li><a href="' . $cart . '">Cart</a></li>
        <li><a href="' . $profile . '" id="authLink" style="display:inline-flex; align-items:center; gap:5px;"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg> Profile</a></li>
      </ul>';
}

function processFiles($dir, $isRoot) {
    $files = glob($dir . '/*.html');
    foreach ($files as $file) {
        $content = file_get_contents($file);
        
        // Regex to replace everything from <ul class="nav-links"> to </ul>
        $pattern = '/<ul class="nav-links">.*?<\/ul>/s';
        $replacement = getNavLinks($isRoot);
        
        $newContent = preg_replace($pattern, $replacement, $content);
        
        if ($newContent !== null) {
            file_put_contents($file, $newContent);
        }
    }
}

// Process root index.html
processFiles($rootDir, true);

// Process pages directory
processFiles($pagesDir, false);

echo "Done";
?>
