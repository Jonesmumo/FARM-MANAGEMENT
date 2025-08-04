<?php
include 'accessToken.php'; // Include the access token file

// Ensure proper timezone
date_default_timezone_set('Africa/Nairobi');

// Safaricom API endpoint and callback URL
$processrequestUrl = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
$callbackurl = 'https://webhook.site/15f0872c-8da1-438d-882e-39d50f12b22d'; // Replace with your callback URL

// Business Details
$passkey = "bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919";
$BusinessShortCode = '174379';
$Timestamp = date('YmdHis');

// Generate the password
$Password = base64_encode($BusinessShortCode . $passkey . $Timestamp);

// Retrieve data from the form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $delivery_address = $_POST['delivery_address'];
    $phone = $_POST['phone_number'];
    $money = $_POST['gtotal'];

    // Format phone number to start with 254
    if (substr($phone, 0, 1) === '0') {
        $phone = '254' . substr($phone, 1);
    }

    $PartyA = $phone; // The phone number making the payment
    $PartyB = $BusinessShortCode; // BusinessShortCode
    $AccountReference = 'Agroconnect';
    $TransactionDesc = 'Agroconnect Payment';
    $Amount = $money;

    // Set headers for the request
    $stkpushheader = [
        'Content-Type:application/json',
        'Authorization:Bearer ' . $access_token
    ];

    // Initialize cURL
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $processrequestUrl);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $stkpushheader); // Setting custom header
    $curl_post_data = [
        'BusinessShortCode' => $BusinessShortCode,
        'Password' => $Password,
        'Timestamp' => $Timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $Amount,
        'PartyA' => $PartyA,
        'PartyB' => $BusinessShortCode,
        'PhoneNumber' => $PartyA,
        'CallBackURL' => $callbackurl,
        'AccountReference' => $AccountReference,
        'TransactionDesc' => $TransactionDesc
    ];

    $data_string = json_encode($curl_post_data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

    // Execute the request
    $curl_response = curl_exec($curl);

    // Handle response
    $data = json_decode($curl_response);
    $ResponseCode = $data->ResponseCode ?? null;
    if ($ResponseCode == "0") {
        $CheckoutRequestID = $data->CheckoutRequestID;
        echo "Transaction initiated successfully. CheckoutRequestID: " . $CheckoutRequestID;
    } else {
        $errorMessage = $data->errorMessage ?? 'Unknown error occurred.';
        echo "Transaction failed. Error: " . $errorMessage;
    }
}
?>
