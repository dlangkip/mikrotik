<?php
// Define the callback directory for logging (ensure this directory exists)
define('CALLBACK_DIR', __DIR__ . '/callbackData/');

// Get the raw POST data from the request
$raw_data = file_get_contents("php://input");

// Log the incoming data for debugging purposes
$log_file = CALLBACK_DIR . 'callback_data_' . time() . '.log';
file_put_contents($log_file, $raw_data . PHP_EOL, FILE_APPEND);

// Decode the raw data from JSON to PHP array
$data = json_decode($raw_data, true);

// Check if decoding was successful
if ($data === null) {
    // If decoding fails, return an error response
    $response_data = array('ResultCode' => 500, 'ResultDesc' => 'Invalid JSON format or empty data');
    header("Content-Type: application/json");
    echo json_encode($response_data);
    exit;
}

// Validate the callback to check if it contains the necessary information
if (!isset($data['Body']['stkCallback'])) {
    $response_data = array('ResultCode' => 500, 'ResultDesc' => 'Missing stkCallback data');
    header("Content-Type: application/json");
    echo json_encode($response_data);
    exit;
}

// Extract relevant fields from the callback
$result_code = $data['Body']['stkCallback']['ResultCode'];
$result_desc = $data['Body']['stkCallback']['ResultDesc'];

// If the result code indicates an error
if ($result_code !== 0) {
    // Log error and return a response
    $response_data = array('ResultCode' => $result_code, 'ResultDesc' => $result_desc);
    header("Content-Type: application/json");
    echo json_encode($response_data);
    exit;
}

// Extract metadata (Amount, PhoneNumber, MpesaReceiptNumber)
$metadata = $data['Body']['stkCallback']['CallbackMetadata'] ?? null;
if (!$metadata) {
    // Missing metadata
    $response_data = array('ResultCode' => 500, 'ResultDesc' => 'Missing callback metadata');
    header("Content-Type: application/json");
    echo json_encode($response_data);
    exit;
}

// Process metadata items
$amountObj = array_values(array_filter($metadata['Item'], function($obj) {
    return $obj['Name'] === 'Amount';
}));
$amount = $amountObj[0]['Value'] ?? null;

$phoneObj = array_values(array_filter($metadata['Item'], function($obj) {
    return $obj['Name'] === 'PhoneNumber';
}));
$phoneNumber = $phoneObj[0]['Value'] ?? null;

$mpesaCodeObj = array_values(array_filter($metadata['Item'], function($obj) {
    return $obj['Name'] === 'MpesaReceiptNumber';
}));
$mpesaCode = $mpesaCodeObj[0]['Value'] ?? null;

// Example: Save to database or perform further processing (this is just a placeholder)
if ($amount && $phoneNumber && $mpesaCode) {
    // Save payment details (you can implement your database logic here)
    // save_payment($amount, $phoneNumber, $mpesaCode);

    // Log the successful transaction
    $transaction_log = [
        'Amount' => $amount,
        'PhoneNumber' => $phoneNumber,
        'MpesaReceiptNumber' => $mpesaCode
    ];
    file_put_contents(CALLBACK_DIR . 'transaction_' . time() . '.log', json_encode($transaction_log) . PHP_EOL, FILE_APPEND);
}

// Send success response back to M-Pesa (confirm the success of the callback)
$response_data = array('ResultCode' => 0, 'ResultDesc' => 'Success');
header("Content-Type: application/json");
echo json_encode($response_data);

?>

