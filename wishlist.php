<?php
require_once __DIR__ . '/includes/auth-check.php';
$pageTitle = 'Wishlist';
$pageDescription = 'Your saved digital products.';
$noIndex = true;
require_once __DIR__ . '/includes/header.php';
?>
<div class="container" id="bxmConfigNotice"></div>

<section class="bxm-section-sm">
  <div class="container">
    <h1 class="bxm-section-title mb-4">My Wishlist</h1>
    <div class="row g-3" id="wishlistGrid">
      <div class="col-6 col-md-4 col-lg-3"><div class="bxm-skeleton" style="height:280px"></div></div>
      <div class="col-6 col-md-4 col-lg-3"><div class="bxm-skeleton" style="height:280px"></div></div>
    </div>
  </div>
</section>
<?php
$wishlistProducts = [];
foreach (bxm_products() as $wProduct) {
    $wishlistProducts[$wProduct['id']] = $wProduct;
}
$encodedWishProducts = json_encode($wishlistProducts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$inlineScript = <<<HTML
<script>
(function () {
  var products = {$encodedWishProducts} || {};
  var loaded = true;

  function render() {
    if (!loaded) return;
    var wish = window.BXM.state.wishlist || {};
    var grid = document.getElementById('wishlistGrid');
    var ids = Object.keys(wish);
    var items = ids.filter(function (id) { return products[id]; }).map(function (id) { return products[id]; });
    if (!items.length) {
      grid.innerHTML = '<div class="col-12"><div class="bxm-state-box text-center"><i class="bi bi-heart bxm-state-icon"></i><h5>No wishlist items yet</h5><p class="text-secondary mb-3">Save products you like and find them here.</p><a href="products.php" class="bxm-btn bxm-btn-primary">Browse Products</a></div></div>';
      return;
    }
    grid.innerHTML = items.map(function (p) {
      var card = window.BXM.productCardHTML(p);
      return '<div class="col-6 col-md-4 col-lg-3">' + card +
        '<button class="bxm-btn bxm-btn-outline bxm-btn-sm w-100 mt-2" data-move-cart="' + window.BXM.escapeHtml(p.id) + '"><i class="bi bi-lightning-charge"></i> Buy Now</button></div>';
    }).join('');
  }

  document.addEventListener('bxm:wishlist', render);
  document.addEventListener('DOMContentLoaded', render);

  document.addEventListener('click', function (e) {
    var move = e.target.closest('[data-move-cart]');
    if (move) {
      e.preventDefault();
      window.BXM.moveToCart(move.getAttribute('data-move-cart'));
    }
  });

  render();
})();
</script>
HTML;
include __DIR__ . '/includes/footer.php';
?>
