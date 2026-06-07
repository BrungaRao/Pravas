<?php
$page_title = 'Search Buses';
require_once 'includes/header.php';

$from = sanitize($conn, $_GET['from'] ?? '');
$to = sanitize($conn, $_GET['to'] ?? '');
$date = sanitize($conn, $_GET['date'] ?? date('Y-m-d'));
$passengers = (int)($_GET['passengers'] ?? 1);

$buses = [];
$error = '';
$searched = false;

if ($from && $to && $date) {
    $searched = true;

    if ($from === $to) {
        $error = 'Origin and destination cannot be the same!';
    } else {
        // STEP 1: find route
        $routeStmt = $conn->prepare("
            SELECT id, origin, destination 
            FROM routes 
            WHERE LOWER(TRIM(origin)) = LOWER(TRIM(?)) 
              AND LOWER(TRIM(destination)) = LOWER(TRIM(?))
            LIMIT 1
        ");

        $routeStmt->bind_param("ss", $from, $to);
        $routeStmt->execute();
        $route = $routeStmt->get_result()->fetch_assoc();

        if (!$route) {
            $error = "❌ ROUTE NOT FOUND";
        } else {

            $route_id = $route['id'];

            // STEP 2: fetch schedules (TEMP WITHOUT FILTERS)
            $stmt = $conn->prepare("
    SELECT
        s.*,
        b.bus_name,
        b.bus_number,
        b.bus_type,
        b.amenities,
        b.total_seats,
        r.origin,
        r.destination,
        r.distance_km
    FROM schedules s
    JOIN buses b ON b.id = s.bus_id
    JOIN routes r ON r.id = s.route_id
    WHERE s.route_id = ?
      AND s.travel_date = ?
      AND s.status = 'scheduled'
      AND s.available_seats >= ?
    ORDER BY s.departure_time
");
$stmt->bind_param(
    "isi",
    $route_id,
    $date,
    $passengers
);

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $buses[] = $row;
}

        }
    }
}

// Fetch cities
$cities_result = $conn->query("SELECT DISTINCT origin as city FROM routes UNION SELECT DISTINCT destination FROM routes ORDER BY city");
$cities = [];
while ($row = $cities_result->fetch_assoc()) $cities[] = $row['city'];
?>

<!-- PAGE HEADER -->
<div class="page-header">
  <div class="container">
    <h1 class="page-title mt-2">
      <?php if ($from && $to): ?>
        <?= htmlspecialchars($from) ?> <span style="color:var(--primary)">→</span> <?= htmlspecialchars($to) ?>
      <?php else: ?>
        Search Buses
      <?php endif; ?>
    </h1>
    <?php if ($date): ?>
      <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.3rem;">
        <i class="fas fa-calendar me-1" style="color: var(--primary);"></i>
        <?= date('l, d F Y', strtotime($date)) ?>
      </p>
    <?php endif; ?>
  </div>
</div>

<div class="container pb-5">

  <!-- INLINE SEARCH BAR -->
  <div class="search-box mb-4">
    <form action="search.php" method="GET">
      <div class="row g-3 align-items-end">
        <div class="col-md-3">
          <label class="form-label">From</label>
          <select name="from" class="form-select" required>
            <option value="">Select Origin</option>
            <?php foreach ($cities as $city): ?>
              <option value="<?= $city ?>" <?= $city === $from ? 'selected' : '' ?>><?= $city ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">To</label>
          <select name="to" class="form-select" required>
            <option value="">Select Destination</option>
            <?php foreach ($cities as $city): ?>
              <option value="<?= $city ?>" <?= $city === $to ? 'selected' : '' ?>><?= $city ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Date</label>
          <input type="date" name="date" class="form-control" value="<?= $date ?>" min="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Passengers</label>
          <select name="passengers" class="form-select">
            <?php for ($i = 1; $i <= 6; $i++): ?>
              <option value="<?= $i ?>" <?= $i === $passengers ? 'selected' : '' ?>><?= $i ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn-primary-custom w-100 justify-content-center">
            <i class="fas fa-search"></i> Search
          </button>
        </div>
      </div>
    </form>
  </div>

  <?php if ($error): ?>
    <div class="alert-custom alert-danger-custom mb-4">
      <i class="fas fa-exclamation-circle"></i> <?= $error ?>
    </div>
  <?php elseif ($searched): ?>

    <!-- RESULTS HEADER -->
     <div id="bus-results">
      <?php if (empty($buses)): ?>
        <div class="text-center py-5">
          <h4 style="font-family: var(--font-heading);">No Buses Available</h4>
          <p style="color: var(--text-muted);">No buses found for this route on <?= date('d M Y', strtotime($date)) ?></p>
          <a href="index.php" class="btn-primary-custom mt-3 d-inline-flex">
            <i class="fas fa-arrow-left"></i> Back to Home
          </a>
        </div>
      <?php else: ?>

        <?php foreach ($buses as $bus):
          $amenities = array_map('trim', explode(',', $bus['amenities'] ?? ''));
          $seatsLeft = $bus['available_seats'];
          $seatClass = $seatsLeft <= 5 ? 'seats-low' : 'seats-left';
          $typeLabel = $bus['bus_type'];
          $badgeClass = 'badge-nonac';
          if (str_contains($typeLabel, 'AC') && !str_contains($typeLabel, 'Non')) $badgeClass = 'badge-ac';
          if (str_contains($typeLabel, 'Sleeper')) $badgeClass = 'badge-sleeper';
          if ($typeLabel === 'Luxury') $badgeClass = 'badge-luxury';
        ?>
          <div class="bus-card"
               data-type="<?= htmlspecialchars($bus['bus_type']) ?>"
               data-price="<?= $bus['fare'] ?>"
               data-departure="<?= $bus['departure_time'] ?>"
               data-seats="<?= $seatsLeft ?>">
            <div class="row align-items-center g-3">
              <div class="col-md-4">
                <div class="d-flex align-items-center gap-3 mb-2">
                  <div style="width:42px; height:42px; background:rgba(255,107,53,0.1); border-radius:10px; display:flex; align-items:center; justify-content:center; color:var(--primary); font-size:1.1rem; flex-shrink:0;">
                    <i class="fas fa-bus"></i>
                  </div>
                  <div>
                    <div class="bus-name"><?= htmlspecialchars($bus['bus_name']) ?></div>
                    <div style="font-size:0.78rem; color:var(--text-muted);"><?= $bus['bus_number'] ?></div>
                  </div>
                </div>
                <span class="bus-type-badge <?= $badgeClass ?>"><?= $typeLabel ?></span>
                <div class="d-flex flex-wrap gap-1 mt-2">
                  <?php foreach (array_slice($amenities, 0, 3) as $amenity): ?>
                    <span class="amenity-tag"><i class="fas fa-check" style="color:var(--success);font-size:0.65rem;"></i><?= trim($amenity) ?></span>
                  <?php endforeach; ?>
                </div>
              </div>

              <div class="col-md-4 text-center">
                <div class="d-flex align-items-center justify-content-center gap-3">
                  <div>
                    <div class="time-display"><?= date('h:i', strtotime($bus['departure_time'])) ?></div>
                    <div style="font-size:0.72rem; color:var(--text-muted);"><?= date('A', strtotime($bus['departure_time'])) ?></div>
                    <div style="font-size:0.78rem; color:var(--text-secondary); font-weight:600;"><?= htmlspecialchars($bus['origin']) ?></div>
                  </div>
                  <div style="flex:1;">
                    <div class="duration-line">
                      <i class="fas fa-bus" style="color:var(--primary);font-size:0.7rem;"></i>
                    </div>
                    <div style="font-size:0.72rem; color:var(--text-muted); text-align:center;"><?= $bus['distance_km'] ?> km</div>
                  </div>
                  <div>
                    <div class="time-display"><?= date('h:i', strtotime($bus['arrival_time'])) ?></div>
                    <div style="font-size:0.72rem; color:var(--text-muted);"><?= date('A', strtotime($bus['arrival_time'])) ?></div>
                    <div style="font-size:0.78rem; color:var(--text-secondary); font-weight:600;"><?= htmlspecialchars($bus['destination']) ?></div>
                  </div>
                </div>
              </div>

              <div class="col-md-4 text-md-end">
                <div class="price-tag">₹<?= number_format($bus['fare'], 0) ?> <span>/ seat</span></div>
                <div class="<?= $seatClass ?> mt-1">
                  <i class="fas fa-chair me-1"></i>
                  <?php if ($seatsLeft <= 5): ?>⚠️ Only <?= $seatsLeft ?> seats left!
                  <?php else: ?><?= $seatsLeft ?> seats available<?php endif; ?>
                </div>
                <div class="mt-3">
                  <?php if (isLoggedIn()): ?>
                    <a href="booking.php?schedule=<?= $bus['id'] ?>&passengers=<?= $passengers ?>"
                       class="btn-primary-custom">
                      <i class="fas fa-ticket-alt"></i> Book Now
                    </a>
                  <?php else: ?>
                    <a href="login.php?redirect=<?= urlencode('booking.php?schedule='.$bus['id'].'&passengers='.$passengers) ?>"
   class="btn-primary-custom">
  <i class="fas fa-sign-in-alt"></i> Login to Book
</a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>

      <?php endif; ?>
    </div>

  <?php else: ?>
    <div class="text-center py-5">
      <div style="font-size: 4rem; margin-bottom: 1rem;">🔍</div>
      <h4 style="font-family: var(--font-heading);">Search for a Bus</h4>
      <p style="color: var(--text-muted);">Fill in the details above to find available buses.</p>
    </div>
  <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>