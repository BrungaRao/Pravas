<?php
require_once 'includes/config.php';
if (!isLoggedIn()) redirect(SITE_URL . '/login.php');

$ref = sanitize($conn, $_GET['ref'] ?? '');
if (!$ref) redirect(SITE_URL . '/my-bookings.php');

$user_id = $_SESSION['user_id'];

// Verify ownership and get booking
$stmt = $conn->prepare("
    SELECT bk.*, s.journey_date, s.id as schedule_id
    FROM bookings bk JOIN schedules s ON s.id = bk.schedule_id
    WHERE bk.booking_ref = ? AND bk.user_id = ? AND bk.booking_status = 'confirmed'
");
$stmt->bind_param("si", $ref, $user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    $_SESSION['flash_message'] = 'Booking not found or already cancelled.';
    $_SESSION['flash_type'] = 'danger';
    redirect(SITE_URL . '/my-bookings.php');
}

// Cancel it
$conn->begin_transaction();
try {
    $conn->query("UPDATE bookings SET booking_status='cancelled', payment_status='refunded' WHERE booking_ref='$ref'");
    $conn->query("UPDATE schedules SET available_seats = available_seats + {$booking['num_seats']} WHERE id = {$booking['schedule_id']}");
    $conn->commit();
    $_SESSION['flash_message'] = "Booking $ref has been cancelled.";
    $_SESSION['flash_type'] = 'success';
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['flash_message'] = 'Cancellation failed. Please try again.';
    $_SESSION['flash_type'] = 'danger';
}
redirect(SITE_URL . '/my-bookings.php');
?>