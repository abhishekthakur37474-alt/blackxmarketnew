<?php
$pageTitle = 'Reviews';
$pageDescription = 'Read verified customer reviews of digital products at BLACK X MARKET.';
$activePage = 'reviews';
require_once __DIR__ . '/includes/header.php';

$approvedReviews = bxm_reviews('approved');
$reviewUser = bxm_session_user();
$purchasedProducts = [];
if ($reviewUser) {
    foreach (bxm_orders_by_user($reviewUser['uid']) as $reviewOrder) {
        if (!in_array($reviewOrder['status'], ['approved', 'completed'], true)) {
            continue;
        }
        $orderItems = $reviewOrder['items'] ?? [];
        if (is_array($orderItems) && !empty($orderItems)) {
            foreach ($orderItems as $orderItem) {
                $pid = $orderItem['productId'] ?? '';
                if ($pid !== '') {
                    $purchasedProducts[$pid] = $orderItem['title'] ?? 'Product';
                }
            }
        } elseif (!empty($reviewOrder['productId'])) {
            $purchasedProducts[$reviewOrder['productId']] = $reviewOrder['productTitle'] ?: 'Product';
        }
    }
}
$reviewsData = [
    'reviews' => $approvedReviews,
    'purchased' => $purchasedProducts,
    'loggedIn' => $reviewUser !== null,
];
?>
<div class="container" id="bxmConfigNotice"></div>

<section class="bxm-section-sm">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
      <div>
        <h1 class="bxm-section-title">Customer Reviews</h1>
        <p class="bxm-section-sub mb-0">Only verified buyers can leave reviews. All reviews are moderated.</p>
      </div>
      <button class="bxm-btn bxm-btn-primary" id="writeReviewBtn"><i class="bi bi-pencil-square"></i> Write a Review</button>
    </div>
    <div class="row g-3" id="reviewsGrid"></div>
  </div>
</section>

<div class="modal fade" id="reviewModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bxm-modal-content">
      <div class="modal-header"><h5 class="modal-title">Write a Review</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Product</label>
          <select class="form-select" id="reviewProduct"><option value="">Select a purchased product</option></select>
          <div class="form-text">Only products you have purchased can be reviewed.</div>
        </div>
        <div class="mb-3">
          <label class="form-label">Rating</label>
          <div id="ratingStars" class="fs-4">
            <i class="bi bi-star bxm-rate" data-value="1" role="button"></i>
            <i class="bi bi-star bxm-rate" data-value="2" role="button"></i>
            <i class="bi bi-star bxm-rate" data-value="3" role="button"></i>
            <i class="bi bi-star bxm-rate" data-value="4" role="button"></i>
            <i class="bi bi-star bxm-rate" data-value="5" role="button"></i>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Review</label>
          <textarea class="form-control" id="reviewComment" rows="4" placeholder="Share your experience..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="bxm-btn bxm-btn-outline" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="bxm-btn bxm-btn-primary" id="submitReviewBtn">Submit Review</button>
      </div>
    </div>
  </div>
</div>
<?php
$encodedReviews = json_encode($reviewsData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$inlineScript = <<<HTML
<script>
(function () {
  var data = {$encodedReviews} || { reviews: [], purchased: {}, loggedIn: false };
  var rating = 0;
  var purchased = data.purchased || {};
  var urlProduct = new URLSearchParams(window.location.search).get('product');

  function stars(r) {
    var out = '';
    for (var i = 1; i <= 5; i++) out += '<i class="bi ' + (i <= r ? 'bi-star-fill text-warning' : 'bi-star text-secondary') + '"></i>';
    return out;
  }

  function paintStars() {
    document.querySelectorAll('#ratingStars .bxm-rate').forEach(function (el) {
      var v = Number(el.getAttribute('data-value'));
      el.className = 'bi ' + (v <= rating ? 'bi-star-fill text-warning' : 'bi-star') + ' bxm-rate';
    });
  }

  function renderReviews() {
    var reviews = (data.reviews || []).slice().sort(function (a, b) { return (b.createdAt || 0) - (a.createdAt || 0); });
    var grid = document.getElementById('reviewsGrid');
    if (!reviews.length) {
      grid.innerHTML = '<div class="col-12"><div class="bxm-state-box text-center"><i class="bi bi-chat-quote bxm-state-icon"></i><h5>No reviews yet</h5><p class="text-secondary mb-0">Be the first to share your experience.</p></div></div>';
      return;
    }
    grid.innerHTML = reviews.map(function (r) {
      return '<div class="col-md-4"><div class="bxm-feature-card h-100">' +
        '<div class="mb-2">' + stars(Number(r.rating) || 0) + '</div>' +
        '<h6 class="mb-1">' + window.BXM.escapeHtml(r.productTitle || 'Product') + '</h6>' +
        '<p class="text-secondary small">' + window.BXM.escapeHtml(r.comment || '') + '</p>' +
        '<div class="small text-muted mt-auto">- ' + window.BXM.escapeHtml(r.userName || 'Customer') + '</div>' +
        '</div></div>';
    }).join('');
  }

  function loadPurchasedOptions() {
    var sel = document.getElementById('reviewProduct');
    Object.keys(purchased).forEach(function (id) {
      var opt = document.createElement('option');
      opt.value = id;
      opt.textContent = purchased[id];
      sel.appendChild(opt);
    });
    if (urlProduct && purchased[urlProduct]) sel.value = urlProduct;
  }

  document.querySelectorAll('#ratingStars .bxm-rate').forEach(function (el) {
    el.addEventListener('click', function () {
      rating = Number(el.getAttribute('data-value'));
      paintStars();
    });
  });

  document.getElementById('writeReviewBtn').addEventListener('click', function () {
    if (!data.loggedIn) { window.BXM.requireLogin(); return; }
    if (!Object.keys(purchased).length) {
      window.BXM.toast('You can review only products you have purchased and received.', 'warning');
      return;
    }
    new bootstrap.Modal(document.getElementById('reviewModal')).show();
  });

  document.getElementById('submitReviewBtn').addEventListener('click', function () {
    var productId = document.getElementById('reviewProduct').value;
    var comment = document.getElementById('reviewComment').value.trim();
    if (!productId) { window.BXM.toast('Please select a product', 'warning'); return; }
    if (!rating) { window.BXM.toast('Please select a rating', 'warning'); return; }
    if (!comment) { window.BXM.toast('Please write a review', 'warning'); return; }
    window.BXM.apiFetch('reviews.php', { method: 'POST', json: { productId: productId, rating: rating, comment: comment } })
      .then(function (res) {
        if (!res || !res.ok) throw new Error((res && res.error) || 'Could not submit review');
        bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();
        rating = 0; paintStars();
        document.getElementById('reviewComment').value = '';
        window.BXM.toast('Review submitted for moderation', 'success');
      }).catch(function (err) {
        window.BXM.toast(err.message || 'Could not submit review', 'danger');
      });
  });

  renderReviews();
  loadPurchasedOptions();
})();
</script>
HTML;
include __DIR__ . '/includes/footer.php';
?>
