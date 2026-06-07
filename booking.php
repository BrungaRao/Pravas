
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$page_title = 'Book Your Seat';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php');
}

$schedule_id = (int)($_GET['schedule'] ?? 0);
$passengers = max(1, (int)($_GET['passengers'] ?? 1));

// Fetch schedule details
$stmt = $conn->prepare("
    SELECT s.*, b.bus_name, b.bus_number, b.bus_type, b.total_seats, b.amenities,
           r.origin, r.destination, r.distance_km
    FROM schedules s
    JOIN buses b ON b.id = s.bus_id
    JOIN routes r ON r.id = s.route_id
    WHERE s.id = ? AND s.status = 'scheduled'
");
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$schedule = $stmt->get_result()->fetch_assoc();

if (!$schedule) {
    $_SESSION['flash_message'] = 'Schedule not found or no longer available.';
    $_SESSION['flash_type'] = 'danger';
    redirect(SITE_URL . '/search.php');
}

$availableSeats = (int)$schedule['available_seats'];

// Fetch booked seats for this schedule
$booked_result = $conn->query("SELECT seat_numbers FROM bookings WHERE schedule_id = $schedule_id AND booking_status != 'cancelled'");
$booked_seats = [];
while ($row = $booked_result->fetch_assoc()) {
    $seats = explode(',', $row['seat_numbers']);
    foreach ($seats as $s) $booked_seats[] = trim($s);
}

// Generate seat map (rows x cols)
$total_seats = (int)$schedule['total_seats'];
$available_seats_db = (int)$schedule['available_seats'];
$cols = 4; // A, B, C, D
$rows = ceil($total_seats / $cols);

// Handle booking form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passenger_names =
    $_POST['passenger_name'] ?? [];

$passenger_ages =
    $_POST['passenger_age'] ?? [];

$passenger_genders =
    $_POST['passenger_gender'] ?? [];
   
    $seat_numbers = sanitize($conn, $_POST['seat_numbers'] ?? '');
    $num_seats = (int)($_POST['num_seats'] ?? 0);
    $payment_method = sanitize($conn, $_POST['payment_method'] ?? 'UPI');
    $user_id = $_SESSION['user_id'];
    $total_fare = $schedule['fare'] * $num_seats;
    $booking_ref = generateBookingRef();

    $errors = [];

if (!$seat_numbers || $num_seats < 1) {
    $errors[] = 'Please select seats.';
}

if (count($passenger_names) != $passengers) {
    $errors[] = 'Passenger details missing.';
}

foreach ($passenger_names as $i => $name) {

    if (trim($name) == '') {
        $errors[] = 'Passenger '.($i+1).' name required.';
    }

    if (
        !isset($passenger_ages[$i]) ||
        $passenger_ages[$i] < 1 ||
        $passenger_ages[$i] > 120
    ) {
        $errors[] = 'Invalid age for Passenger '.($i+1);
    }

    if (
        empty($passenger_genders[$i])
    ) {
        $errors[] = 'Gender required for Passenger '.($i+1);
    }
}

    if (empty($errors)) {

    $selected = explode(',', $seat_numbers);

    // Seat count must equal passenger count
    if (count($selected) != $passengers) {
        $errors[] = "Seat count must match passenger count.";
    }

    // Check seat conflicts
    $conflict = array_intersect($selected, $booked_seats);

    if (!empty($conflict)) {
        $errors[] = 'Seats ' . implode(', ', $conflict) . ' were just booked. Please re-select.';
    }

    // Check available seats in database
    if ($num_seats > $availableSeats) {
        $errors[] = "Selected seats are no longer available.";
    }
}

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            $firstPassengerName =
    trim($passenger_names[0]);

$firstPassengerAge =
    (int)$passenger_ages[0];

$firstPassengerGender =
    trim($passenger_genders[0]);

$stmt = $conn->prepare("
INSERT INTO bookings
(
booking_ref,
user_id,
schedule_id,
travel_date,
passenger_name,
passenger_age,
passenger_gender,
seat_numbers,
num_seats,
total_fare,
payment_method
)
VALUES
(
?,?,?,?,?,?,?,?,?,?,?
)
");

$stmt->bind_param(
    "siississids",
    $booking_ref,
    $user_id,
    $schedule_id,
    $schedule['travel_date'],
    $firstPassengerName,
    $firstPassengerAge,
    $firstPassengerGender,
    $seat_numbers,
    $num_seats,
    $total_fare,
    $payment_method
);

$stmt->execute();
            $booking_id = $conn->insert_id;
            $passengerStmt = $conn->prepare("
INSERT INTO booking_passengers
(
 booking_id,
 passenger_name,
 passenger_age,
 passenger_gender
)
VALUES
(
 ?, ?, ?, ?
)
");
for($i=0;$i<count($passenger_names);$i++){

$name =
    trim($passenger_names[$i]);

$age =
    (int)$passenger_ages[$i];

$gender =
    trim($passenger_genders[$i]);

$passengerStmt->bind_param(
    "isis",
    $booking_id,
    $name,
    $age,
    $gender
);

$passengerStmt->execute();
}

            // Update available seats
            $updateStmt = $conn->prepare("UPDATE schedules SET available_seats = available_seats - ? WHERE id = ? AND available_seats >= ?");
$updateStmt->bind_param("iii", $num_seats, $schedule_id, $num_seats);
$updateStmt->execute();
if ($updateStmt->affected_rows === 0) {
    throw new Exception("Seats no longer available.");
}

            $conn->commit();
            $_SESSION['flash_message'] = "Booking confirmed! Ref: $booking_ref";
            $_SESSION['flash_type'] = 'success';
            redirect(SITE_URL . "/ticket.php?ref=$booking_ref");
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Booking failed. Please try again.';
        }
    }
}
?>

<!-- PAGE HEADER -->
<!-- PAGE HEADER -->
<div class="page-header">
  <div class="container">
    <h1 class="page-title mt-2">Book Your Seat</h1>
  </div>
</div>

<div class="container pb-5">

  <?php if (!empty($errors)): ?>
    <div class="alert-custom alert-danger-custom mb-4">
      <i class="fas fa-exclamation-circle"></i>
      <div><?php foreach ($errors as $e) echo "<div>$e</div>"; ?></div>
    </div>
  <?php endif; ?>

  <div class="row g-4">

    <!-- LEFT: SEAT MAP + PASSENGER FORM -->
    <div class="col-lg-8">

      <!-- STEP 1: BUS INFO CARD -->
      <div class="card-custom mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div>
            <h4 style="font-family:var(--font-heading); font-weight:700; font-size:1.1rem; margin-bottom:2px;"><?= htmlspecialchars($schedule['bus_name']) ?></h4>
            <div style="font-size:0.8rem; color:var(--text-muted);"><?= $schedule['bus_number'] ?> &bull; <?= $schedule['bus_type'] ?></div>
          </div>
          <div class="text-center">
            <div class="time-display" style="font-size:1.2rem;"><?= date('h:i A', strtotime($schedule['departure_time'])) ?></div>
            <div style="font-size:0.78rem; color:var(--text-muted);"><?= htmlspecialchars($schedule['origin']) ?></div>
          </div>
          <div style="flex:1; text-align:center; padding:0 0.8rem;">
            <div style="height:2px; background:linear-gradient(90deg, var(--primary), var(--accent)); border-radius:1px; position:relative; margin:8px 0;">
              <i class="fas fa-bus" style="position:absolute; right:0; top:-8px; color:var(--primary);"></i>
            </div>
            <div style="font-size:0.72rem; color:var(--text-muted);"><?= $schedule['distance_km'] ?> km</div>
          </div>
          <div class="text-center">
            <div class="time-display" style="font-size:1.2rem;"><?= date('h:i A', strtotime($schedule['arrival_time'])) ?></div>
            <div style="font-size:0.78rem; color:var(--text-muted);"><?= htmlspecialchars($schedule['destination']) ?></div>
          </div>
          <div class="text-end">
            <div class="price-tag">₹<?= number_format($schedule['fare'], 0) ?></div>
            <div style="font-size:0.78rem; color:var(--text-muted);">per seat</div>
            <div style="font-size:0.78rem; color:green; font-weight:600;"><?= $availableSeats ?> available</div>
          </div>
        </div>
      </div>

      <!-- STEP 2: SEAT MAP (collapsible on mobile) -->
      <div class="card-custom mb-3" id="seat-map-container">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 style="font-weight:700; margin:0; font-size:0.95rem;">
            <i class="fas fa-th me-2" style="color:var(--primary);"></i>Step 1 — Select <?= $passengers ?> Seat<?= $passengers > 1 ? 's' : '' ?>
          </h5>
          <span id="seats-chosen-badge" style="font-size:0.78rem; background:rgba(255,107,53,0.1); color:var(--primary); padding:3px 10px; border-radius:20px; font-weight:600;">0 / <?= $passengers ?> selected</span>
        </div>

        <div style="background:var(--bg-card2); border:1px solid var(--border); border-radius:var(--radius-sm); padding:0.8rem; overflow-x:auto;">
          <!-- Column headers -->
          <div style="display:flex; align-items:center; gap:6px; margin-bottom:8px; padding-left:24px;">
            <?php foreach (['A','B','','C','D'] as $lbl): ?>
              <?php if($lbl===''): ?><div style="width:20px;"></div><?php continue; endif; ?>
              <div style="width:36px; text-align:center; font-size:0.7rem; font-weight:700; color:var(--text-muted);"><?= $lbl ?></div>
            <?php endforeach; ?>
          </div>
          <?php
          $seat_labels = ['A', 'B', '', 'C', 'D'];
          for ($r = 1; $r <= $rows; $r++):
          ?>
            <div style="display:flex; align-items:center; gap:6px; margin-bottom:5px;">
              <span style="font-size:0.68rem; color:var(--text-muted); width:18px; text-align:right;"><?= $r ?></span>
              <?php foreach ($seat_labels as $col):
                if ($col === '') { echo '<div style="width:20px;"></div>'; continue; }
                $seat_num = $r . $col;
                $seat_idx = ($r - 1) * 4 + (array_search($col, ['A','B','C','D']));
                if ($seat_idx >= $total_seats) continue;
                $is_booked = in_array($seat_num, $booked_seats);
                $seat_class = $is_booked ? 'seat-booked' : 'seat-available';
              ?>
                <div class="seat <?= $seat_class ?>"
                     data-seat="<?= $seat_num ?>"
                     title="Seat <?= $seat_num ?>"
                     style="width:36px; height:36px; display:flex; align-items:center; justify-content:center; font-size:0.68rem; border-radius:6px; cursor:<?= $is_booked ? 'not-allowed' : 'pointer' ?>;">
                  <?= $seat_num ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endfor; ?>
        </div>

        <div class="seat-legend" style="display:flex; gap:1.2rem; margin-top:0.8rem; flex-wrap:wrap;">
          <div class="legend-item" style="display:flex; align-items:center; gap:6px; font-size:0.75rem; color:var(--text-muted);">
            <div style="width:14px; height:14px; border-radius:4px; background:rgba(16,185,129,0.2); border:1.5px solid rgba(16,185,129,0.4);"></div> Available
          </div>
          <div class="legend-item" style="display:flex; align-items:center; gap:6px; font-size:0.75rem; color:var(--text-muted);">
            <div style="width:14px; height:14px; border-radius:4px; background:rgba(239,68,68,0.15); border:1.5px solid rgba(239,68,68,0.3);"></div> Booked
          </div>
          <div class="legend-item" style="display:flex; align-items:center; gap:6px; font-size:0.75rem; color:var(--text-muted);">
            <div style="width:14px; height:14px; border-radius:4px; background:var(--primary); border:1.5px solid var(--primary);"></div> Your selection
          </div>
        </div>
      </div>

      <!-- STEP 3: PASSENGER + PAYMENT FORM -->
      <div class="card-custom">
        <form method="POST" id="booking-form">

          <input type="hidden" name="seat_numbers" id="selected-seats-input">
          <input type="hidden" name="num_seats" id="num-seats-input">
          <input type="hidden" id="base-fare" value="<?= $schedule['fare'] ?>">

          <h5 style="font-weight:700; margin-bottom:1rem; font-size:0.95rem;">
            <i class="fas fa-users me-2" style="color:var(--primary);"></i>Step 2 — Passenger Details
          </h5>

          <div id="passenger-container">
            <?php for($i=1; $i<=$passengers; $i++): ?>
            <div class="border rounded p-3 mb-3" style="background:var(--bg-card2);">
              <h6 style="font-size:0.85rem; font-weight:700; margin-bottom:0.8rem; color:var(--primary);">Passenger <?= $i ?></h6>
              <div class="row g-2">
                <div class="col-md-6">
                  <label style="font-size:0.78rem; color:var(--text-muted); font-weight:600; margin-bottom:4px; display:block;">Full Name</label>
                  <input type="text" name="passenger_name[]" class="form-control form-control-sm" placeholder="Enter name" required>
                </div>
                <div class="col-md-3">
                  <label style="font-size:0.78rem; color:var(--text-muted); font-weight:600; margin-bottom:4px; display:block;">Age</label>
                  <input type="number" name="passenger_age[]" class="form-control form-control-sm" min="1" max="120" placeholder="Age" required>
                </div>
                <div class="col-md-3">
                  <label style="font-size:0.78rem; color:var(--text-muted); font-weight:600; margin-bottom:4px; display:block;">Gender</label>
                  <select name="passenger_gender[]" class="form-select form-select-sm" required>
                    <option value="">Select</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
              </div>
            </div>
            <?php endfor; ?>
          </div>

          <!-- PAYMENT -->
          <h5 style="font-weight:700; margin:1.2rem 0 0.8rem; font-size:0.95rem;">
            <i class="fas fa-credit-card me-2" style="color:var(--primary);"></i>Step 3 — Payment Method
          </h5>
          <div class="d-flex gap-2 flex-wrap mb-4">
            <?php
            $pm_icons = ['UPI'=>'fas fa-qrcode','Card'=>'fas fa-credit-card','Net Banking'=>'fas fa-university','Wallet'=>'fas fa-wallet'];
            foreach (['UPI','Card','Net Banking','Wallet'] as $method):
            ?>
              <label style="cursor:pointer;">
                <input type="radio" name="payment_method" value="<?= $method ?>" <?= $method==='UPI'?'checked':'' ?> style="display:none;" class="payment-radio">
                <div class="payment-option" style="border:1.5px solid var(--border); border-radius:10px; padding:0.5rem 1rem; font-size:0.82rem; font-weight:600; color:var(--text-secondary); transition:all 0.2s;">
                  <i class="<?= $pm_icons[$method] ?> me-1"></i><?= $method ?>
                </div>
              </label>
            <?php endforeach; ?>
          </div>

          <button type="submit" class="btn-primary-custom" id="proceed-btn" disabled
                  style="opacity:0.5; width:100%; justify-content:center; padding:0.85rem; font-size:0.95rem;">
            <i class="fas fa-lock me-2"></i>Confirm Booking
          </button>
          <p style="text-align:center; color:var(--text-muted); font-size:0.73rem; margin-top:0.6rem; margin-bottom:0;">
            <i class="fas fa-shield-alt me-1" style="color:var(--success);"></i>Your booking is protected &amp; secure
          </p>

        </form>
      </div><!-- /.card-custom passenger form -->

    </div><!-- /.col-lg-8 -->

    <!-- RIGHT: STICKY BOOKING SUMMARY -->
    <div class="col-lg-4">
      <div style="position:sticky; top:80px;">
        <div class="booking-summary">
          <h5 style="font-weight:700; margin-bottom:1.1rem; font-size:0.95rem;">
            <i class="fas fa-receipt me-2" style="color:var(--primary);"></i>Booking Summary
          </h5>

          <div class="summary-row" style="display:flex; justify-content:space-between; padding:7px 0; border-bottom:1px solid var(--border);">
            <span style="font-size:0.8rem; color:var(--text-muted);">Route</span>
            <span style="font-size:0.8rem; font-weight:600;"><?= htmlspecialchars($schedule['origin']) ?> → <?= htmlspecialchars($schedule['destination']) ?></span>
          </div>
          <div class="summary-row" style="display:flex; justify-content:space-between; padding:7px 0; border-bottom:1px solid var(--border);">
            <span style="font-size:0.8rem; color:var(--text-muted);">Date</span>
            <span style="font-size:0.8rem; font-weight:600;"><?= formatDate($schedule['travel_date']) ?></span>
          </div>
          <div class="summary-row" style="display:flex; justify-content:space-between; padding:7px 0; border-bottom:1px solid var(--border);">
            <span style="font-size:0.8rem; color:var(--text-muted);">Departure</span>
            <span style="font-size:0.8rem; font-weight:600;"><?= formatTime($schedule['departure_time']) ?></span>
          </div>
          <div class="summary-row" style="display:flex; justify-content:space-between; padding:7px 0; border-bottom:1px solid var(--border);">
            <span style="font-size:0.8rem; color:var(--text-muted);">Bus</span>
            <span style="font-size:0.8rem; font-weight:600;"><?= htmlspecialchars($schedule['bus_name']) ?></span>
          </div>
          <div class="summary-row" style="display:flex; justify-content:space-between; padding:7px 0; border-bottom:1px solid var(--border);">
            <span style="font-size:0.8rem; color:var(--text-muted);">Selected Seats</span>
            <span style="font-size:0.8rem; font-weight:600; color:var(--primary);" id="selected-seats-display">None</span>
          </div>
          <div class="summary-row" style="display:flex; justify-content:space-between; padding:7px 0; border-bottom:1px solid var(--border);">
            <span style="font-size:0.8rem; color:var(--text-muted);">Seat Count</span>
            <span style="font-size:0.8rem; font-weight:600;"><span id="seat-count">0</span> / <?= $passengers ?></span>
          </div>
          <div class="summary-row" style="display:flex; justify-content:space-between; padding:7px 0; border-bottom:1px solid var(--border);">
            <span style="font-size:0.8rem; color:var(--text-muted);">Fare per Seat</span>
            <span style="font-size:0.8rem; font-weight:600;">₹<?= number_format($schedule['fare'], 0) ?></span>
          </div>
          <div class="summary-row" style="display:flex; justify-content:space-between; padding:7px 0; border-bottom:1px solid var(--border);">
            <span style="font-size:0.8rem; color:var(--text-muted);">Available Seats</span>
            <span style="font-size:0.8rem; font-weight:600; color:green;"><?= $available_seats_db ?></span>
          </div>

          <div style="background:rgba(255,107,53,0.07); border:1px solid rgba(255,107,53,0.25); border-radius:10px; padding:0.9rem 1rem; margin-top:0.9rem; display:flex; justify-content:space-between; align-items:center;">
            <span style="font-weight:700; font-size:0.9rem;">Total Amount</span>
            <span class="summary-total" id="total-fare" style="font-size:1.2rem; font-weight:700; color:var(--primary);">₹0</span>
          </div>

          <p style="font-size:0.72rem; color:var(--text-muted); margin-top:0.7rem; text-align:center; margin-bottom:0;">All prices inclusive of taxes</p>
        </div>
      </div>
    </div><!-- /.col-lg-4 -->

  </div><!-- /.row -->
</div><!-- /.container -->

<script>
document.querySelectorAll('.payment-radio').forEach(radio => {
  radio.addEventListener('change', function() {
    document.querySelectorAll('.payment-option').forEach(opt => {
      opt.style.borderColor = 'var(--border)';
      opt.style.color = 'var(--text-secondary)';
      opt.style.background = 'transparent';
    });
    const opt = this.nextElementSibling;
    opt.style.borderColor = 'var(--primary)';
    opt.style.color = 'var(--primary)';
    opt.style.background = 'rgba(255,107,53,0.08)';
  });
});
document.querySelector('.payment-radio:checked')?.dispatchEvent(new Event('change'));

const maxPassengers = <?= $passengers ?>;
let selectedSeats = [];

document.querySelectorAll('.seat-available').forEach(seat => {
  seat.addEventListener('click', function() {
    const seatNo = this.dataset.seat;
    if (selectedSeats.includes(seatNo)) {
      selectedSeats = selectedSeats.filter(s => s !== seatNo);
      this.classList.remove('seat-selected');
    } else {
      if (selectedSeats.length >= maxPassengers) {
        alert('You can only select ' + maxPassengers + ' seat(s).');
        return;
      }
      selectedSeats.push(seatNo);
      this.classList.add('seat-selected');
    }
    updateSummary();
  });
});

function updateSummary() {
  const fare = parseFloat(document.getElementById('base-fare').value);
  const count = selectedSeats.length;

  document.getElementById('selected-seats-display').innerText = selectedSeats.join(', ') || 'None';
  document.getElementById('seat-count').innerText = count;
  document.getElementById('selected-seats-input').value = selectedSeats.join(',');
  document.getElementById('num-seats-input').value = count;
  document.getElementById('total-fare').innerText = '₹' + (fare * count).toLocaleString('en-IN');
  document.getElementById('seats-chosen-badge').innerText = count + ' / ' + maxPassengers + ' selected';

  const ready = count === maxPassengers;
  document.getElementById('proceed-btn').disabled = !ready;
  document.getElementById('proceed-btn').style.opacity = ready ? '1' : '0.5';
}
</script>
<?php require_once 'includes/footer.php'; ?>