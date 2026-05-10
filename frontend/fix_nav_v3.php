<?php
$dirs = [
    __DIR__ => true,   // isRoot
    __DIR__ . '/pages' => false
];

function buildNavHtml($isRoot) {
    $home    = $isRoot ? 'index.html' : '../index.html';
    $prods   = $isRoot ? 'pages/products.html' : 'products.html';
    $about   = $isRoot ? 'pages/about.html' : 'about.html';
    $contact = $isRoot ? 'pages/contact.html' : 'contact.html';
    $orders  = $isRoot ? 'pages/order_history.html' : 'order_history.html';
    $cart    = $isRoot ? 'pages/cart.html' : 'cart.html';
    $profile = $isRoot ? 'pages/profile.html' : 'profile.html';

    return <<<HTML
<ul class="nav-links">
        <li><button class="theme-toggle" onclick="toggleTheme()" title="Toggle Dark Mode" aria-label="Toggle theme"></button></li>
        <li><a href="{$home}">Home</a></li>
        <li><a href="{$prods}">Products</a></li>
        <li><a href="{$about}">About</a></li>
        <li><a href="{$contact}">Contact</a></li>
        <li><a href="{$orders}">Orders</a></li>
        <li><a href="{$cart}">Cart</a></li>
        <li><a href="{$profile}" id="authLink" style="display:inline-flex;align-items:center;gap:5px;"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg> Profile</a></li>
      </ul>
HTML;
}

foreach ($dirs as $dir => $isRoot) {
    foreach (glob($dir . '/*.html') as $file) {
        $raw = file_get_contents($file);
        $nav = buildNavHtml($isRoot);
        $new = preg_replace('/<ul class="nav-links">.*?<\/ul>/s', $nav, $raw);
        if ($new && $new !== $raw) {
            file_put_contents($file, $new);
            echo "Fixed: " . basename($file) . "\n";
        } else {
            echo "Skipped (no match): " . basename($file) . "\n";
        }
    }
}
echo "All done.\n";
?>
