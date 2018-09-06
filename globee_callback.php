<?php

require 'globee/PaymentApi.php';
require 'globee/remove_order.php';

$requestBody = file_get_contents('php://input');
$input = json_decode($requestBody, true);
$data = $input['data'];
$paymentRequest = \GloBee\PaymentApi\Models\PaymentRequest::fromResponse($data);
$orderId = $paymentRequest->custom_payment_id;

switch ($paymentRequest->status) {
    case 'paid':
    case 'confirmed':
    case 'overpaid':
    case 'paid_late':
    case 'completed':
        return tep_db_query("update " . TABLE_ORDERS . " set orders_status = " . MODULE_PAYMENT_GLOBEE_PAID_STATUS_ID . " where orders_id = " . intval($orderId));
        break;
    case 'invalid':
    case 'cancelled':
    case 'expired':
        return tep_remove_order($orderId, $restock = true);
        break;
    case 'new':
    case 'unpaid':
    case 'underpaid':
        break;
    default:
        error_log('GloBee: Received unknown IPN status of ' . $response['status'] . ' for order_id = ' . $orderId);
        break;
}
