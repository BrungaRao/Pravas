<?php

$page_title = 'Your Ticket';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php');
}

$ref = sanitize($conn, $_GET['ref'] ?? '');
if (!$ref) redirect(SITE_URL . '/my-bookings.php');

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT bk.*, r.origin, r.destination, r.distance_km,
           s.travel_date AS journey_date, s.departure_time, s.arrival_time,
           b.bus_name, b.bus_type, b.bus_number, b.amenities
    FROM bookings bk
    JOIN schedules s ON s.id = bk.schedule_id
    JOIN routes r ON r.id = s.route_id
    JOIN buses b ON b.id = s.bus_id
    WHERE bk.booking_ref = ? AND bk.user_id = ?
");
$stmt->bind_param("si", $ref, $user_id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();

if (!$ticket) {
    $_SESSION['flash_message'] = 'Ticket not found.';
    $_SESSION['flash_type'] = 'danger';
    redirect(SITE_URL . '/my-bookings.php');
}
?>

<style>
@media print {
  .navbar, .page-header, .no-print, footer { display: none !important; }
  body { background: #fff !important; color: #000 !important; }
  .ticket-card { box-shadow: none !important; border: 1px solid #ccc !important; }
}
</style>

<div class="page-header no-print">
  <div class="container">
    <div class="breadcrumb-custom">
      <a href="index.php">Home</a><span class="sep">/</span>
      <a href="my-bookings.php">My Bookings</a><span class="sep">/</span>
      <span>Ticket</span>
    </div>
    <h1 class="page-title mt-2">Your Ticket</h1>
  </div>
</div>

<div class="container pb-5">
  <!-- ACTION BUTTONS -->
  <div class="d-flex gap-3 justify-content-end mb-4 no-print">
    <button onclick="window.print()" class="btn-outline-custom">
      <i class="fas fa-print"></i> Print Ticket
    </button>
    <a href="my-bookings.php" class="btn-outline-custom">
      <i class="fas fa-arrow-left"></i> Back to Bookings
    </a>
  </div>

  <?php if ($ticket['booking_status'] === 'confirmed'): ?>
  <div class="alert-custom alert-success-custom mb-4 no-print">
    <i class="fas fa-check-circle"></i>
    <div>Booking confirmed! Your seat is reserved. Have a safe journey!</div>
</div>
  <?php elseif ($ticket['booking_status'] === 'cancelled'): ?>
  <div class="alert-custom alert-danger-custom mb-4 no-print">
    <i class="fas fa-times-circle"></i>
    <div>This booking has been cancelled.</div>
  </div>
  <?php endif; ?>

  <!-- TICKET CARD -->
  <div class="ticket-card">

    <!-- TICKET HEADER -->
    <div class="ticket-header">
      <div>
        <div style="font-family:var(--font-display); font-size:1.6rem; font-weight:700; color:#fff;">
          <i class="fas fa-bus me-2"></i>Pravas
        </div>
        <div style="color:rgba(255,255,255,0.8); font-size:0.8rem; margin-top:2px;">E-Ticket / Booking Confirmation</div>
      </div>
      <div style="text-align:right;">
        <div style="font-size:0.75rem; color:rgba(255,255,255,0.7); text-transform:uppercase; letter-spacing:1px;">Booking Ref</div>
        <div style="font-family:var(--font-display); font-size:1.4rem; font-weight:700; color:#fff; letter-spacing:3px;"><?= $ticket['booking_ref'] ?></div>
        <div style="margin-top:6px;">
          <?php
          $status_styles = [
            'confirmed' => 'background:rgba(16,185,129,0.9); color:#fff;',
            'cancelled'  => 'background:rgba(239,68,68,0.9); color:#fff;',
            'pending'    => 'background:rgba(245,158,11,0.9); color:#fff;',
          ];
          $style = $status_styles[$ticket['booking_status']] ?? '';
          ?>
          <span style="<?= $style ?> padding:0.3rem 0.9rem; border-radius:50px; font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:1px;">
            <?= ucfirst($ticket['booking_status']) ?>
          </span>
        </div>
      </div>
    </div>

    <div class="ticket-body">

      <!-- ROUTE & TIME -->
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:2rem; flex-wrap:wrap; gap:1rem;">
        <div style="text-align:center;">
          <div class="time-display"><?= date('h:i A', strtotime($ticket['departure_time'])) ?></div>
          <div style="font-family:var(--font-heading); font-size:1.3rem; font-weight:700;"><?= htmlspecialchars($ticket['origin']) ?></div>
          <div style="font-size:0.8rem; color:var(--text-muted);">Departure</div>
        </div>
        <div style="flex:1; text-align:center; padding:0 1.5rem; min-width:120px;">
          <div style="height:2px; background:linear-gradient(90deg, var(--primary), var(--accent)); border-radius:1px; position:relative; margin:12px 0;">
            <i class="fas fa-bus" style="position:absolute; right:0; top:-9px; color:var(--primary);"></i>
          </div>
          <div style="font-size:0.78rem; color:var(--text-muted);"><?= $ticket['distance_km'] ?> km</div>
          <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;"><?= date('d M Y', strtotime($ticket['journey_date'])) ?></div>
        </div>
        <div style="text-align:center;">
          <div class="time-display"><?= date('h:i A', strtotime($ticket['arrival_time'])) ?></div>
          <div style="font-family:var(--font-heading); font-size:1.3rem; font-weight:700;"><?= htmlspecialchars($ticket['destination']) ?></div>
          <div style="font-size:0.8rem; color:var(--text-muted);">Arrival</div>
        </div>
      </div>

      <!-- DIVIDER WITH CIRCLES -->
      <div class="ticket-divider">
        <div class="ticket-divider-circle"><i class="fas fa-scissors" style="font-size:0.7rem;"></i></div>
      </div>

      <!-- INFO GRID -->
      <div class="ticket-info-grid mt-3">
        <div class="ticket-info-item">
          <div class="ti-label"><i class="fas fa-user me-1"></i> Passenger</div>
          <div class="ti-value"><?= htmlspecialchars($ticket['passenger_name']) ?></div>
        </div>
        <div class="ticket-info-item">
          <div class="ti-label"><i class="fas fa-birthday-cake me-1"></i> Age / Gender</div>
          <div class="ti-value"><?= $ticket['passenger_age'] ?> / <?= $ticket['passenger_gender'] ?></div>
        </div>
        <div class="ticket-info-item">
          <div class="ti-label"><i class="fas fa-chair me-1"></i> Seat(s)</div>
          <div class="ti-value" style="color:var(--primary);"><?= $ticket['seat_numbers'] ?></div>
        </div>
        <div class="ticket-info-item">
          <div class="ti-label"><i class="fas fa-users me-1"></i> Passengers</div>
          <div class="ti-value"><?= $ticket['num_seats'] ?></div>
        </div>
        <div class="ticket-info-item">
          <div class="ti-label"><i class="fas fa-bus me-1"></i> Bus</div>
          <div class="ti-value"><?= htmlspecialchars($ticket['bus_name']) ?></div>
        </div>
        <div class="ticket-info-item">
          <div class="ti-label"><i class="fas fa-hashtag me-1"></i> Bus No.</div>
          <div class="ti-value"><?= $ticket['bus_number'] ?></div>
        </div>
        <div class="ticket-info-item">
          <div class="ti-label"><i class="fas fa-tag me-1"></i> Bus Type</div>
          <div class="ti-value"><?= $ticket['bus_type'] ?></div>
        </div>
        <div class="ticket-info-item">
          <div class="ti-label"><i class="fas fa-credit-card me-1"></i> Payment</div>
          <div class="ti-value"><?= $ticket['payment_method'] ?></div>
        </div>
      </div>

      <!-- TOTAL FARE -->
      <div style="background:rgba(255,107,53,0.06); border:1px solid rgba(255,107,53,0.2); border-radius:12px; padding:1rem 1.5rem; margin-top:1.5rem; display:flex; justify-content:space-between; align-items:center;">
        <div>
          <div style="font-size:0.78rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.8px;">Total Fare</div>
          <div style="font-size:0.75rem; color:var(--text-muted);"><?= $ticket['num_seats'] ?> seat(s) × ₹<?= number_format($ticket['total_fare'] / $ticket['num_seats'], 0) ?></div>
        </div>
        <div class="price-tag">₹<?= number_format($ticket['total_fare'], 0) ?></div>
      </div>

      <!-- FOOTER NOTE -->
      <div style="margin-top:1.5rem; padding-top:1rem; border-top:1px solid var(--border); font-size:0.78rem; color:var(--text-muted); text-align:center; line-height:1.7;">
        <i class="fas fa-info-circle me-1" style="color:var(--primary);"></i>
        Please carry a valid photo ID during the journey. Boarding time: 15 minutes before departure.
        <br>For support: support@Pravas.com | +91 98765 43210
      </div>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
