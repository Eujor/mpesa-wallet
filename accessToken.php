<?php
// Enable error reporting (optional during development)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// MPESA API KEYS (Consider using environment variables in production)
$consumerKey = getenv('MPESA_CONSUMER_KEY') ?: "vpWQjH9MNocFfB3HdtklSLsEOpIHOWTCcWzej53V0Y97YOxM";
$consumerSecret = getenv('MPESA_CONSUMER_SECRET') ?: "movwD0cnAGASm4hnoZf7OTeUX5O77nAk3Ri9vTDM6qGuKS8vvnY3koE4IFnT3xm1";

// Access Token URL
$access_token_url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

// Initialize cURL session
$curl = curl_init($access_token_url);
if ($curl === false) {
    die("Failed to initialize cURL session.");
}

// Set cURL options
$headers = ['Content-Type: application/json; charset=utf8'];
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl, CURLOPT_HEADER, FALSE);
curl_setopt($curl, CURLOPT_USERPWD, "$consumerKey:$consumerSecret");
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);  // Disable SSL verification (not recommended for production)
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // Disable SSL verification (not recommended for production)

// Execute the cURL request
$result = curl_exec($curl);

// Check if the request was successful
if ($result === false) {
    die("cURL Error: " . curl_error($curl));
}

// Get the HTTP status code
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
if ($status != 200) {
    die("HTTP Error: " . $status . " - Response: " . $result);
}

// Decode the JSON response
$result_json = json_decode($result);
if (json_last_error() !== JSON_ERROR_NONE) {
    die("JSON Decode Error: " . json_last_error_msg());
}

// Check if the access token is present
if (!isset($result_json->access_token)) {
    die("Access token not found in response.");
}

$result = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$result = json_decode($result);
echo $access_token = $result->access_token;

// Store the access token for later use
$access_token = $result_json->access_token;

// Close the cURL session
curl_close($curl);
?>
