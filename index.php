<?php
require_once 'includes/config.php';
?>

<?php
$page_title = 'Book Bus Tickets Online';
require_once 'includes/header.php';

// Fetch unique cities for dropdowns
$cities_result = $conn->query("SELECT DISTINCT origin as city FROM routes UNION SELECT DISTINCT destination FROM routes ORDER BY city");
$cities = [];
while ($row = $cities_result->fetch_assoc()) {
    $cities[] = $row['city'];
}
?>

<!-- HERO SECTION -->
<section class="hero">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6 hero-content">
        <h1 class="hero-title">
          Book Your<br>
          Bus Tickets<br>
          <span class="highlight">Instantly Online</span>
        </h1>
        <p class="hero-subtitle">
          Travel across India with ease. Choose across various routes, AC/Non-AC options, and seat options — all at the best prices.
        </p>
      </div>

      <div class="col-lg-6">
        <!-- SEARCH BOX -->
        <div class="search-box animate-fadeInUp">
          <div class="search-box-title">Find Your Bus</div>
          <form action="search.php" method="GET">
            <div class="row g-3">
              <div class="col-md-5">
                <label class="form-label"><i class="fas fa-map-marker-alt me-1" style="color: var(--primary);"></i> From</label>
                <select name="from" id="from" class="form-select" required>
                  <option value="" disabled selected>Select Origin</option>
                  <?php foreach ($cities as $city): ?>
                    <option value="<?= $city ?>"><?= $city ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-md-2 d-flex align-items-end justify-content-center">
                <button type="button" id="swap-cities" style="
                  width: 40px; height: 40px; border-radius: 50%; border: 1.5px solid var(--border);
                  background: var(--bg-card2); color: var(--primary); cursor: pointer;
                  display: flex; align-items: center; justify-content: center; font-size: 1rem;
                  transition: all 0.3s; margin-bottom: 2px;
                " title="Swap cities">
                  <i class="fas fa-exchange-alt"></i>
                </button>
              </div>

              <div class="col-md-5">
                <label class="form-label"><i class="fas fa-map-marker me-1" style="color: var(--accent);"></i> To</label>
                <select name="to" id="to" class="form-select" required>
                  <option value="" disabled selected>Select Destination</option>
                  <?php foreach ($cities as $city): ?>
                    <option value="<?= $city ?>"><?= $city ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label"><i class="fas fa-calendar-alt me-1" style="color: var(--success);"></i> Journey Date</label>
                <input type="date" name="date" id="journey-date" class="form-control" required>
              </div>

              <div class="col-md-6">
                <label class="form-label"><i class="fas fa-users me-1" style="color: var(--warning);"></i> Passengers</label>
                <select name="passengers" class="form-select">
                  <?php for ($i = 1; $i <= 6; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?></option>
                  <?php endfor; ?>
                </select>
              </div>

              <div class="col-12">
                <button type="submit" class="btn-primary-custom w-100 justify-content-center" style="padding: 0.9rem;">
                  <i class="fas fa-search"></i> Search Available Buses
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FEATURES SECTION -->
<section style="padding: 4rem 0; background: linear-gradient(180deg, transparent, rgba(255,107,53,0.03), transparent);">
  <div class="container">
    <div class="text-center mb-5">
      <h2 style="font-size: 2.2rem; font-family: var(--font-display);">Why Choose <span style="color: var(--primary)">Pravas</span>?</h2>
    </div>
    <div class="row g-4">
      <?php
      $features = [
        ['icon' => 'fas fa-bolt', 'color' => 'var(--warning)', 'title' => 'Instant Booking', 'desc' => 'Book tickets in under 60 seconds. Receive instant confirmation.'],
        ['icon' => 'fas fa-shield-alt', 'color' => 'var(--success)', 'title' => 'Secure Payments', 'desc' => 'Multiple payment options — UPI, Cards, Net Banking. All encrypted & safe.'],
        ['icon' => 'fas fa-ticket-alt', 'color' => 'var(--primary)', 'title' => 'Easy Cancellation', 'desc' => 'Cancel up to 4 hours before departure. Get hassle-free refunds.'],
        ['icon' => 'fas fa-headset', 'color' => 'var(--accent)', 'title' => '24/7 Support', 'desc' => 'Our team is always available to help you with any queries or issues.'],
      ];
      foreach ($features as $f): ?>
        <div class="col-sm-6 col-lg-3">
          <div class="card-custom text-center h-100">
            <div style="width: 58px; height: 58px; border-radius: 16px; background: rgba(255,255,255,0.04); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.2rem; font-size: 1.4rem; color: <?= $f['color'] ?>;">
              <i class="<?= $f['icon'] ?>"></i>
            </div>
            <h5 style="font-weight: 700; margin-bottom: 0.6rem;"><?= $f['title'] ?></h5>
            <p style="color: var(--text-secondary); font-size: 0.87rem; line-height: 1.6;"><?= $f['desc'] ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>