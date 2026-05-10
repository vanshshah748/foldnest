/* =========================
   SEARCH LOGIC
   ======================== */

function searchProducts() {
  const searchBar = document.getElementById('searchBar');
  if (!searchBar) return;
  
  const input = searchBar.value.toLowerCase().trim();
  const products = document.querySelectorAll('.product');
  
  products.forEach(product => {
    const titleElement = product.querySelector('h3');
    const descElement = product.querySelector('p');
    
    if (!titleElement || !descElement) return;
    
    const title = titleElement.innerText.toLowerCase();
    const description = descElement.innerText.toLowerCase();
    
    if (title.includes(input) || description.includes(input)) {
      product.style.display = '';
    } else {
      product.style.display = 'none';
    }
  });
}
