<?php
$page_title = 'My Bookings';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php');
}

$user_id = $_SESSION['user_id'];

$bookings = $conn->query("
    SELECT bk.*, r.origin, r.destination, s.travel_date AS journey_date, s.departure_time, s.arrival_time,
           b.bus_name, b.bus_type, b.bus_number
    FROM bookings bk
    JOIN schedules s ON s.id = bk.schedule_id
    JOIN routes r ON r.id = s.route_id
    JOIN buses b ON b.id = s.bus_id
    WHERE bk.user_id = $user_id
    ORDER BY bk.booked_at DESC
");
?>

<div class="page-header">
  <div class="container">
    <h1 class="page-title mt-2">My Bookings</h1>
  </div>
</div>

<div class="container pb-5">
  <?php
  $all_bookings = [];
  while ($row = $bookings->fetch_assoc()) $all_bookings[] = $row;
  ?>

  <?php if (empty($all_bookings)): ?>
    <div class="text-center py-5">
      <div style="font-size:4rem; margin-bottom:1rem;">🎫</div>
      <h4 style="font-family:var(--font-heading);">No Bookings Yet</h4>
      <p style="color:var(--text-muted); margin-bottom:1.5rem;">You haven't made any bookings yet. Start your journey!</p>
      <a href="search.php" class="btn-primary-custom">
        <i class="fas fa-search"></i> Search Buses
      </a>
    </div>
  <?php else: ?>

    <!-- STATS BAR -->
    <?php
    $total = count($all_bookings);
    $confirmed = count(array_filter($all_bookings, fn($b) => $b['booking_status'] === 'confirmed'));
    $cancelled = count(array_filter($all_bookings, fn($b) => $b['booking_status'] === 'cancelled'));
    ?>
    <div class="row g-3 mb-3">
      <?php
      $stats = [
        ['icon' => 'fas fa-ticket-alt', 'color' => 'orange', 'value' => $total, 'label' => 'Total Bookings'],
        ['icon' => 'fas fa-check-circle', 'color' => 'green', 'value' => $confirmed, 'label' => 'Confirmed'],
        ['icon' => 'fas fa-times-circle', 'color' => 'blue', 'value' => $cancelled, 'label' => 'Cancelled'],
      ];
      foreach ($stats as $s): ?>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon stat-icon-<?= $s['color'] ?>"><i class="<?= $s['icon'] ?>"></i></div>
            <div>
              <div class="stat-value" style="font-size:1.4rem;"><?= $s['value'] ?></div>
              <div class="stat-label"><?= $s['label'] ?></div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- BOOKING CARDS -->
    <?php foreach ($all_bookings as $bk):
      $is_upcoming = strtotime($bk['journey_date']) >= strtotime('today');
      $can_cancel = $bk['booking_status'] === 'confirmed' && $is_upcoming;
    ?>
      <div class="card-custom mb-4" style="cursor:default;">
        <!-- CARD HEADER -->
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
          <div>
            <span class="booking-ref-code" style="font-size:1.1rem;"><?= $bk['booking_ref'] ?></span>
            <div style="font-size:0.78rem; color:var(--text-muted); margin-top:3px;">
              Booked on <?= date('d M Y, h:i A', strtotime($bk['booked_at'])) ?>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="status-badge status-<?= $bk['booking_status'] ?>">
              <?php
              $status_icons = ['confirmed' => 'check-circle', 'cancelled' => 'times-circle', 'pending' => 'clock'];
              $icon = $status_icons[$bk['booking_status']] ?? 'circle';
              ?>
              <i class="fas fa-<?= $icon ?>"></i> <?= ucfirst($bk['booking_status']) ?>
            </span>
          </div>
        </div>

        <!-- JOURNEY INFO -->
        <div class="row g-3 align-items-center mb-3">
          <div class="col-md-5">
            <div class="d-flex align-items-center gap-3">
              <div>
                <div class="time-display" style="font-size:1.4rem;"><?= date('h:i A', strtotime($bk['departure_time'])) ?></div>
                <div style="font-weight:700; font-size:0.9rem;"><?= htmlspecialchars($bk['origin']) ?></div>
              </div>
              <div style="flex:1; text-align:center;">
                <div style="height:2px; background:linear-gradient(90deg,var(--primary),var(--accent)); border-radius:1px; position:relative; margin:8px 0;">
                  <i class="fas fa-bus" style="position:absolute;right:0;top:-8px;color:var(--primary);font-size:0.8rem;"></i>
                </div>
                <div style="font-size:0.72rem; color:var(--text-muted);"><?= htmlspecialchars($bk['bus_type']) ?></div>
              </div>
              <div>
                <div class="time-display" style="font-size:1.4rem;"><?= date('h:i A', strtotime($bk['arrival_time'])) ?></div>
                <div style="font-weight:700; font-size:0.9rem;"><?= htmlspecialchars($bk['destination']) ?></div>
              </div>
            </div>
          </div>
          <div class="col-md-7">
            <div class="row g-2 text-sm" style="font-size:0.85rem;">
              <div class="col-6 col-md-3">
                <div style="color:var(--text-muted); font-size:0.72rem; text-transform:uppercase; letter-spacing:0.5px;">Date</div>
                <div style="font-weight:600;"><?= date('d M Y', strtotime($bk['journey_date'])) ?></div>
              </div>
              <div class="col-6 col-md-3">
                <div style="color:var(--text-muted); font-size:0.72rem; text-transform:uppercase; letter-spacing:0.5px;">Seats</div>
                <div style="font-weight:600; color:var(--primary);"><?= $bk['seat_numbers'] ?></div>
              </div>
              <div class="col-6 col-md-3">
                <div style="color:var(--text-muted); font-size:0.72rem; text-transform:uppercase; letter-spacing:0.5px;">Passenger</div>
                <div style="font-weight:600;"><?= htmlspecialchars($bk['passenger_name']) ?></div>
              </div>
              <div class="col-6 col-md-3">
                <div style="color:var(--text-muted); font-size:0.72rem; text-transform:uppercase; letter-spacing:0.5px;">Fare</div>
                <div style="font-weight:700; color:var(--success);">₹<?= number_format($bk['total_fare'], 0) ?></div>
              </div>
            </div>
          </div>
        </div>

        <!-- CARD FOOTER -->
        <div class="divider"></div>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div style="font-size:0.82rem; color:var(--text-muted);">
            <i class="fas fa-bus me-1" style="color:var(--primary);"></i>
            <?= htmlspecialchars($bk['bus_name']) ?> &bull; <?= $bk['bus_number'] ?>
            &bull; Payment: <?= $bk['payment_method'] ?>
          </div>
          <div class="d-flex gap-2">
            <?php if ($bk['booking_status'] === 'confirmed'): ?>
              <a href="ticket.php?ref=<?= $bk['booking_ref'] ?>" class="btn-outline-custom" style="padding:0.5rem 1rem; font-size:0.85rem;">
                <i class="fas fa-ticket-alt"></i> View Ticket
              </a>
            <?php endif; ?>
            <?php if ($can_cancel): ?>
              <a href="cancel-booking.php?ref=<?= $bk['booking_ref'] ?>"
                 class="cancel-booking-btn btn-danger-custom"
                 data-ref="<?= $bk['booking_ref'] ?>"
                 style="font-size:0.85rem; padding:0.5rem 1rem; border-radius:8px; display:inline-flex; align-items:center; gap:6px;">
                <i class="fas fa-times"></i> Cancel
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
