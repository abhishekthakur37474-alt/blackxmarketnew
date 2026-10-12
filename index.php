<?php
$pageTitle = 'Home';
$pageDescription = 'Discover premium digital gaming products at BLACK X MARKET. Verified products, secure checkout and instant digital delivery.';
$activePage = 'home';
require_once __DIR__ . '/includes/header.php';

$activeProducts = bxm_products('active');
$featuredProducts = array_slice($activeProducts, 0, 8);
$productCount = count($activeProducts);
$deliveredCount = (int) bxm_val("SELECT COUNT(*) FROM orders WHERE status IN ('approved','completed')");
$slides = bxm_carousels('active');
$homeReviews = bxm_reviews('approved', null, 3);
$homeData = [
    'products' => $featuredProducts,
    'carousel' => $slides,
    'reviews' => $homeReviews,
    'productCount' => $productCount,
    'deliveredCount' => $deliveredCount,
];
?>
<div class="container" id="bxmConfigNotice"></div>

<section class="bxm-hero">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-7">
        <span class="bxm-badge mb-3"><i class="bi bi-lightning-charge-fill"></i> Instant Digital Delivery</span>
        <h1 class="bxm-hero-title mb-3">Digital Gaming<br>Stuff, <span class="text-secondary">Delivered.</span></h1>
        <p class="bxm-hero-sub mb-4">Discover premium digital products at BLACK X MARKET. Verified sellers, secure checkout and automated delivery after approval.</p>
        <div class="d-flex flex-wrap gap-2">
          <a href="products.php" class="bxm-btn bxm-btn-primary"><i class="bi bi-bag"></i> Shop Now</a>
          <a href="products.php" class="bxm-btn bxm-btn-outline"><i class="bi bi-grid"></i> Explore Products</a>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="bxm-card p-4">
          <div class="row g-3 text-center">
            <div class="col-6">
              <div class="bxm-stat-value" id="heroProducts"><?= (int) $productCount ?>+</div>
              <div class="bxm-stat-label">Products</div>
            </div>
            <div class="col-6">
              <div class="bxm-stat-value" id="heroOrders"><?= (int) $deliveredCount ?>+</div>
              <div class="bxm-stat-label">Delivered</div>
            </div>
          </div>
          <hr class="bxm-divider my-4">
          <ul class="list-unstyled mb-0 d-grid gap-2">
            <li class="d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill text-success"></i><span class="text-secondary">Verified digital products</span></li>
            <li class="d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill text-success"></i><span class="text-secondary">Manual payment verification</span></li>
            <li class="d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill text-success"></i><span class="text-secondary">Secure order delivery card</span></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<?php if (!empty($slides)): ?>
<section class="bxm-section-sm" id="carouselSection">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-3">
      <div>
        <h2 class="bxm-section-title">Featured</h2>
        <p class="bxm-section-sub mb-0">Handpicked offers from the marketplace.</p>
      </div>
    </div>
    <div id="homeCarousel" class="carousel slide" data-bs-ride="carousel">
      <div class="carousel-inner" id="carouselInner">
        <?php foreach ($slides as $index => $slide): ?>
        <div class="carousel-item<?= $index === 0 ? ' active' : '' ?>">
          <div class="bxm-carousel-slide">
            <img src="<?= bxm_e($slide['imageUrl']) ?>" alt="<?= bxm_e($slide['title'] ?: 'Featured') ?>">
            <div class="bxm-carousel-caption">
              <h3 class="mb-1"><?= bxm_e($slide['title']) ?></h3>
              <?php if ($slide['description'] !== ''): ?><p class="text-secondary mb-3"><?= bxm_e($slide['description']) ?></p><?php endif; ?>
              <?php if ($slide['link'] !== ''): ?><a href="<?= bxm_e($slide['link']) ?>" class="bxm-btn bxm-btn-primary"><?= bxm_e($slide['buttonText'] ?: 'Explore Now') ?></a><?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="d-flex gap-2 mt-3">
        <button class="bxm-icon-btn" type="button" data-bs-target="#homeCarousel" data-bs-slide="prev"><i class="bi bi-arrow-left"></i></button>
        <button class="bxm-icon-btn" type="button" data-bs-target="#homeCarousel" data-bs-slide="next"><i class="bi bi-arrow-right"></i></button>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="bxm-section">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4">
      <div>
        <h2 class="bxm-section-title">Featured Products</h2>
        <p class="bxm-section-sub mb-0">Top digital products ready for instant delivery.</p>
      </div>
      <a href="products.php" class="bxm-btn bxm-btn-outline bxm-btn-sm d-none d-sm-inline-flex">View All</a>
    </div>
    <div class="row g-3" id="featuredProducts"></div>
  </div>
</section>

<section class="bxm-section-sm">
  <div class="container">
    <div class="row g-3">
      <div class="col-6 col-lg-3">
        <div class="bxm-feature-card">
          <div class="bxm-feature-icon"><i class="bi bi-lightning-charge"></i></div>
          <h6>Fast Digital Delivery</h6>
          <p class="text-secondary small mb-0">Get access right after payment approval.</p>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="bxm-feature-card">
          <div class="bxm-feature-icon"><i class="bi bi-shield-check"></i></div>
          <h6>Secure Checkout</h6>
          <p class="text-secondary small mb-0">Verified payments and protected orders.</p>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="bxm-feature-card">
          <div class="bxm-feature-icon"><i class="bi bi-patch-check"></i></div>
          <h6>Verified Products</h6>
          <p class="text-secondary small mb-0">Only lawful, authorized digital goods.</p>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="bxm-feature-card">
          <div class="bxm-feature-icon"><i class="bi bi-headset"></i></div>
          <h6>Customer Support</h6>
          <p class="text-secondary small mb-0">We are here to help with every order.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="bxm-section">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4">
      <div>
        <h2 class="bxm-section-title">What Customers Say</h2>
        <p class="bxm-section-sub mb-0">Verified reviews from real buyers.</p>
      </div>
      <a href="reviews.php" class="bxm-btn bxm-btn-outline bxm-btn-sm d-none d-sm-inline-flex">All Reviews</a>
    </div>
    <div class="row g-3" id="homeReviews"></div>
  </div>
</section>

<section class="bxm-section-sm">
  <div class="container">
    <div class="bxm-cta">
      <h2 class="mb-2">Ready to get started?</h2>
      <p class="text-secondary mb-4">Browse our digital collection and get instant access after approval.</p>
      <a href="products.php" class="bxm-btn bxm-btn-primary"><i class="bi bi-bag"></i> Shop Products</a>
    </div>
  </div>
</section>
<?php
$encodedHome = json_encode($homeData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$inlineScript = <<<HTML
<script>
(function () {
  var data = {$encodedHome};

  function stars(rating) {
    var out = '';
    for (var i = 1; i <= 5; i++) {
      out += '<i class="bi ' + (i <= rating ? 'bi-star-fill text-warning' : 'bi-star text-secondary') + '"></i>';
    }
    return out;
  }

  var featured = document.getElementById('featuredProducts');
  window.BXM.renderProducts(featured, data.products || []);
  document.getElementById('heroProducts').textContent = (data.productCount || 0) + '+';
  document.getElementById('heroOrders').textContent = (data.deliveredCount || 0) + '+';

  var host = document.getElementById('homeReviews');
  var reviews = (data.reviews || []).slice().reverse();
  if (!reviews.length) {
    host.innerHTML = '<div class="col-12"><div class="bxm-state-box text-center"><i class="bi bi-chat-quote bxm-state-icon"></i><p class="text-secondary mb-0">No reviews yet.</p></div></div>';
  } else {
    host.innerHTML = reviews.map(function (r) {
      return '<div class="col-md-4"><div class="bxm-feature-card h-100">' +
        '<div class="mb-2">' + stars(Number(r.rating) || 0) + '</div>' +
        '<p class="text-secondary">' + window.BXM.escapeHtml(r.comment || '') + '</p>' +
        '<div class="small text-muted">- ' + window.BXM.escapeHtml(r.userName || 'Customer') + '</div>' +
        '</div></div>';
    }).join('');
  }
})();
</script>
HTML;
include __DIR__ . '/includes/footer.php';
?>
