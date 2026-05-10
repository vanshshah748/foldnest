$categories = @("Chairs", "Tables", "Beds", "Storage", "Sofas", "Laptop Tables", "Cupboards", "Dinning", "Coffee Tables", "Desks", "Doors")
$adjectives = @("Premium", "Compact", "Modern", "Classic", "Elegant", "Smart", "Luxury", "Minimalist", "Ergonomic")

$products = @()

for ($i = 1; $i -le 55; $i++) {
    $category = $categories[($i - 1) % $categories.Length]
    
    $basePrice = (Get-Random -Minimum 1500 -Maximum 9500)
    if ($category -eq "Sofas" -or $category -eq "Beds") {
        $price = $basePrice * 2
    } else {
        $price = $basePrice
    }
    
    $oldPrice = $price + (Get-Random -Minimum 500 -Maximum 2500)
    
    $rating = [math]::Round((Get-Random -Minimum 3.5 -Maximum 5.0), 1)
    
    $stock = if ((Get-Random -Minimum 0 -Maximum 100) -gt 10) { (Get-Random -Minimum 1 -Maximum 50) } else { 0 }
    
    $adj = Get-Random -InputObject $adjectives
    
    # Slice the 's' off category name for singular
    $singularCategory = $category.Substring(0, $category.Length - 1)
    if ($category -eq "Storage") {
        $singularCategory = "Storage Unit"
    } elseif ($category -eq "Laptop Tables") {
        $singularCategory = "Laptop Table"
    } elseif ($category -eq "Dinning") {
        $singularCategory = "Dinning Table"
    }
    
    $title = "$adj Foldable $singularCategory"
    $desc = "A highly versatile and space-saving $singularCategory designed for modern compact living."
    
    $product = @{
        id = $i
        title = $title
        category = $category
        price = $price
        oldPrice = $oldPrice
        rating = $rating
        image = ""
        description = $desc
        stock = $stock
    }
    
    $products += $product
}

$products | ConvertTo-Json -Depth 3 | Out-File -FilePath "data/products.json" -Encoding utf8
