<?php
require_once __DIR__ . '/../config/Database.php';
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

                if (!is_numeric($amount) || $amount <= 0) {
                    $response = ['success' => false, 'message' => '❌ Invalid amount.'];
                    break;
                }

                $conn->beginTransaction();
                try {
                    $success = $payment->addPayment($amount, date('Y-m-d'), $method_id, $status_id, $appt_id);
                    if ($success) {
                        $conn->commit();
                        $response = ['success' => true, 'message' => '✅ Payment added successfully!'];
                    } else {
                        $conn->rollBack();
                        $response = ['success' => false, 'message' => '❌ Failed to add payment.'];
                    }
                } catch (PDOException $e) {
                    $conn->rollBack();
                    if (str_contains($e->getMessage(), '1452')) {
                        $response = ['success' => false, 'message' => '⚠️ Invalid Appointment ID.'];
                    } else {
                        $response = ['success' => false, 'message' => 'Database Error: ' . $e->getMessage()];
                    }
                }
            } else {
                $response = ['success' => false, 'message' => '⚠️ Missing required fields.'];
            }
            break;

        // =====================================================
        // 🔹 UPDATE PAYMENT
        // =====================================================
        case 'updatePayment':
            $id = $_POST['payment_id'] ?? 0;
            $amount = $_POST['amount'] ?? 0;
            $method_id = $_POST['method_id'] ?? 0;
            $status_id = $_POST['status_id'] ?? 0;

            if (!$id || !$amount || !$method_id || !$status_id) {
                $response = ['success' => false, 'message' => '⚠️ Missing or invalid fields.'];
                break;
            }

            try {
                $stmt = $conn->prepare("
                    UPDATE payment 
                    SET PYMT_AMOUNT_PAID = ?, 
                        PYMT_METH_ID = ?, 
                        PYMT_STAT_ID = ? 
                    WHERE PYMT_ID = ?
                ");
                $success = $stmt->execute([$amount, $method_id, $status_id, $id]);

                $response = [
                    'success' => $success,
                    'message' => $success ? '✅ Payment updated successfully!' : '❌ Failed to update payment.'
                ];
            } catch (Exception $e) {
                $response = [
                    'success' => false,
                    'message' => '❌ Database error: ' . $e->getMessage()
                ];
            }
            break;

        // =====================================================
        // 🔹 DELETE PAYMENT
        // =====================================================
        case 'deletePayment':
            if (!empty($_POST['id'])) {
                $success = $payment->deletePayment($_POST['id']);
                $response = $success
                    ? ['success' => true, 'message' => '🗑️ Payment deleted successfully.']
                    : ['success' => false, 'message' => '❌ Failed to delete payment.'];
            } else {
                $response = ['success' => false, 'message' => '⚠️ Missing payment ID.'];
            }
            break;

        // =====================================================
        // 🔹 ADD METHOD
        // =====================================================
        case 'addMethod':
            if (!empty($_POST['name'])) {
                $success = $method->addMethod(trim($_POST['name']));
                $response = $success
                    ? ['success' => true, 'message' => '✅ Payment method added successfully!']
                    : ['success' => false, 'message' => '❌ Failed to add payment method.'];
            } else {
                $response = ['success' => false, 'message' => '⚠️ Please enter a valid method name.'];
            }
            break;

        case 'updateMethod':
            if (!empty($_POST['id']) && !empty($_POST['name'])) {
                $success = $method->updateMethod($_POST['id'], trim($_POST['name']));
                $response = $success
                    ? ['success' => true, 'message' => '✅ Payment method updated successfully!']
                    : ['success' => false, 'message' => '❌ Failed to update method.'];
            } else {
                $response = ['success' => false, 'message' => '⚠️ Missing or invalid input.'];
            }
            break;

        case 'deleteMethod':
            if (!empty($_POST['id'])) {
                $success = $method->deleteMethod($_POST['id']);
                $response = $success
                    ? ['success' => true, 'message' => '🗑️ Payment method deleted successfully.']
                    : ['success' => false, 'message' => '❌ Failed to delete payment method.'];
            } else {
                $response = ['success' => false, 'message' => '⚠️ Missing method ID.'];
            }
            break;

        // =====================================================
        // 🔹 ADD STATUS
        // =====================================================
        case 'addStatus':
            if (!empty($_POST['name'])) {
                $success = $status->addStatus(trim($_POST['name']));
                $response = $success
                    ? ['success' => true, 'message' => '✅ Payment status added successfully!']
                    : ['success' => false, 'message' => '❌ Failed to add payment status.'];
            } else {
                $response = ['success' => false, 'message' => '⚠️ Please enter a valid status name.'];
            }
            break;

        case 'updateStatus':
            if (!empty($_POST['id']) && !empty($_POST['name'])) {
                $success = $status->updateStatus($_POST['id'], trim($_POST['name']));
                $response = $success
                    ? ['success' => true, 'message' => '✅ Payment status updated successfully!']
                    : ['success' => false, 'message' => '❌ Failed to update payment status.'];
            } else {
                $response = ['success' => false, 'message' => '⚠️ Missing or invalid input.'];
            }
            break;

        case 'deleteStatus':
            if (!empty($_POST['id'])) {
                $success = $status->deleteStatus($_POST['id']);
                $response = $success
                    ? ['success' => true, 'message' => '🗑️ Payment status deleted successfully.']
                    : ['success' => false, 'message' => '❌ Failed to delete payment status.'];
            } else {
                $response = ['success' => false, 'message' => '⚠️ Missing status ID.'];
            }
            break;

        // =====================================================
        // ❌ DEFAULT
        // =====================================================
        default:
            $response = ['success' => false, 'message' => '❌ Unknown action.'];
    }

} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Unexpected Error: ' . $e->getMessage()];
}

echo json_encode($response);