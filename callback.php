<?php
include 'connection.php'; // Ensure your connection.php properly connects to the database
header("Content-Type: application/json");

// Read the raw POST data from M-PESA
$stkCallbackResponse = file_get_contents('php://input');

// Log the raw response for debugging
$logFile = "Mpesastkresponse.json";
$log = fopen($logFile, "a");
fwrite($log, $stkCallbackResponse . PHP_EOL); // Append the response to the log file with a newline
fclose($log);

// Decode the JSON response
$data = json_decode($stkCallbackResponse);

// Check if the data was decoded properly
if (json_last_error() !== JSON_ERROR_NONE) {
    // Handle JSON decoding error
    http_response_code(400);
    echo json_encode(['status' => 'failure', 'message' => 'Invalid JSON received']);
    exit();
}

// Extract necessary fields from the response
$MerchantRequestID = $data->Body->stkCallback->MerchantRequestID ?? null;
$CheckoutRequestID = $data->Body->stkCallback->CheckoutRequestID ?? null;
$ResultCode = $data->Body->stkCallback->ResultCode ?? null;
$ResultDesc = $data->Body->stkCallback->ResultDesc ?? null;
$Amount = $data->Body->stkCallback->CallbackMetadata->Item[0]->Value ?? null;
$TransactionId = $data->Body->stkCallback->CallbackMetadata->Item[1]->Value ?? null;
$UserPhoneNumber = $data->Body->stkCallback->CallbackMetadata->Item[4]->Value ?? null;

// Check if the transaction was successful
if ($ResultCode == 0) {
    // Fetch the current account balance
    $getTheAccountBalance = mysqli_query($db, "SELECT * FROM accounbalance");
    
    if ($getTheAccountBalance && mysqli_num_rows($getTheAccountBalance) > 0) {
        $row = mysqli_fetch_array($getTheAccountBalance);
        $balance = $row['balance'];
        $newBalance = $balance + $Amount;

        // Update the account balance
        if (!mysqli_query($db, "UPDATE accounbalance SET balance='$newBalance'")) {
            // Handle the error on balance update
            error_log("Error updating balance: " . mysqli_error($db));
        }

        // Store the transaction details in the database
        if (!mysqli_query($db, "INSERT INTO transactions (MerchantRequestID, CheckoutRequestID, ResultCode, Amount, MpesaReceiptNumber, PhoneNumber) VALUES ('$MerchantRequestID', '$CheckoutRequestID', '$ResultCode', '$Amount', '$TransactionId', '$UserPhoneNumber')")) {
            // Handle the error on transaction insert
            error_log("Error inserting transaction: " . mysqli_error($db));
        }
    } else {
        // Handle case where the account balance couldn't be fetched
        error_log("No account balance found.");
    }
} else {
    // Handle unsuccessful transaction
    error_log("Transaction was not successful. ResultCode: $ResultCode, ResultDesc: $ResultDesc");
}

// Send a response back to M-PESA to acknowledge receipt of the callback
echo json_encode(['status' => 'success', 'message' => 'Callback processed successfully']);
?>
