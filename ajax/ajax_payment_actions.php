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
    if (!empty($_POST['id']) && !empty($_POST['amount']) && !empty($_POST['method_id']) && !empty($_POST['status_id'])) {
        $id = $_POST['id'];
        $amount = $_POST['amount'];
        $method_id = $_POST['method_id'];
        $status_id = $_POST['status_id'];

        // Validate method ID exists
        $mCheck = $conn->prepare("SELECT PYMT_METH_ID FROM PAYMENT_METHOD WHERE PYMT_METH_ID = ?");
        $mCheck->execute([$method_id]);
        if ($mCheck->rowCount() === 0) {
            $response = ['success' => false, 'message' => '⚠️ Invalid payment method selected.'];
            echo json_encode($response);
            exit;
        }

        // Validate status ID exists
        $sCheck = $conn->prepare("SELECT PYMT_STAT_ID FROM PAYMENT_STATUS WHERE PYMT_STAT_ID = ?");
        $sCheck->execute([$status_id]);
        if ($sCheck->rowCount() === 0) {
            $response = ['success' => false, 'message' => '⚠️ Invalid payment status selected.'];
            echo json_encode($response);
            exit;
        }

        // Use today's date for the update
        $date = date('Y-m-d');

        $success = $payment->updatePayment($id, $amount, $date, $method_id, $status_id);
        $response = $success
            ? ['success' => true, 'message' => '✅ Payment updated successfully!']
            : ['success' => false, 'message' => '❌ Failed to update payment.'];
    } else {
        $response = ['success' => false, 'message' => '⚠️ Missing or invalid fields.'];
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
