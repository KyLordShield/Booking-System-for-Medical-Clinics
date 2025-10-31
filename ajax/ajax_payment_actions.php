<?php
require_once __DIR__ . '/../classes/Payment.php';
require_once __DIR__ . '/../classes/PaymentMethod.php';
require_once __DIR__ . '/../classes/PaymentStatus.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request.'];

try {
    $payment = new Payment();
    $method = new PaymentMethod();
    $status = new PaymentStatus();
    $conn = (new Database())->connect();

    if (empty($_POST['action'])) {
        echo json_encode(['success' => false, 'message' => 'No action specified.']);
        exit;
    }

    $action = $_POST['action'];

    switch ($action) {
        // =====================================================
        // 🔹 ADD PAYMENT
        // =====================================================
        case 'addPayment':
            if (isset($_POST['amount'], $_POST['method_id'], $_POST['status_id'], $_POST['appt_id'])) {
                $amount = trim($_POST['amount']);
                $method_id = (int) $_POST['method_id'];
                $status_id = (int) $_POST['status_id'];
                $appt_id = trim($_POST['appt_id']);

                // 🔸 Validation
                if (!is_numeric($amount) || $amount <= 0) {
                    $response = ['success' => false, 'message' => '❌ Invalid amount. Please enter a valid number.'];
                    break;
                }

                $conn->beginTransaction(); // 🔒 Start transaction

                try {
                    $success = $payment->addPayment($amount, date('Y-m-d'), $method_id, $status_id, $appt_id);

                    if ($success) {
                        $conn->commit(); // ✅ Commit only if valid
                        $response = ['success' => true, 'message' => '✅ Payment added successfully!'];
                    } else {
                        $conn->rollBack();
                        $response = ['success' => false, 'message' => '❌ Failed to add payment.'];
                    }
                } catch (PDOException $e) {
                    $conn->rollBack(); // ❌ Cancel transaction on any error

                    if (str_contains($e->getMessage(), '1452')) {
                        $response = ['success' => false, 'message' => '⚠️ Invalid Appointment ID. Please check and try again.'];
                    } else {
                        $response = ['success' => false, 'message' => 'Database Error: ' . $e->getMessage()];
                    }
                }
            } else {
                $response = ['success' => false, 'message' => '⚠️ Missing required fields.'];
            }
            break;

        // =====================================================
        // 🔹 ADD METHOD
        // =====================================================
        case 'addMethod':
            if (!empty($_POST['name'])) {
                $success = $method->addMethod($_POST['name']);
                $response = $success
                    ? ['success' => true, 'message' => '✅ Payment method added successfully!']
                    : ['success' => false, 'message' => '❌ Failed to add payment method.'];
            } else {
                $response = ['success' => false, 'message' => '⚠️ Please enter a valid method name.'];
            }
            break;

        // =====================================================
        // 🔹 UPDATE METHOD
        // =====================================================
        case 'updateMethod':
            if (!empty($_POST['id']) && !empty($_POST['name'])) {
                $success = $method->updateMethod($_POST['id'], $_POST['name']);
                $response = $success
                    ? ['success' => true, 'message' => '✅ Payment method updated successfully!']
                    : ['success' => false, 'message' => '❌ Failed to update method.'];
            } else {
                $response = ['success' => false, 'message' => '⚠️ Missing or invalid input.'];
            }
            break;

        // =====================================================
        // 🔹 ADD STATUS
        // =====================================================
        case 'addStatus':
            if (!empty($_POST['name'])) {
                $success = $status->addStatus($_POST['name']);
                $response = $success
                    ? ['success' => true, 'message' => '✅ Payment status added successfully!']
                    : ['success' => false, 'message' => '❌ Failed to add payment status.'];
            } else {
                $response = ['success' => false, 'message' => '⚠️ Please enter a valid status name.'];
            }
            break;

        // =====================================================
        // 🔹 UPDATE STATUS
        // =====================================================
        case 'updateStatus':
            if (!empty($_POST['id']) && !empty($_POST['name'])) {
                $success = $status->updateStatus($_POST['id'], $_POST['name']);
                $response = $success
                    ? ['success' => true, 'message' => '✅ Payment status updated successfully!']
                    : ['success' => false, 'message' => '❌ Failed to update payment status.'];
            } else {
                $response = ['success' => false, 'message' => '⚠️ Missing or invalid input.'];
            }
            break;

        default:
            $response = ['success' => false, 'message' => '❌ Unknown action.'];
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Unexpected Error: ' . $e->getMessage()];
}

echo json_encode($response);
