<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


// Get the input parameters
$id = $_GET['id'] ?? null;
$trackingNumber = $_GET['trackingNumber'] ?? null;
$trackingNumberUrl = $_GET['trackingNumberUrl'] ?? null;

// Check if the required parameters are present
if (!$id || !$trackingNumber || !$trackingNumberUrl) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

// Validate the input parameters
if (!is_numeric($id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

// Sanitize the input parameters
$id = intval($id);
$trackingNumber = htmlspecialchars($trackingNumber);
$trackingNumberUrl = htmlspecialchars($trackingNumberUrl);

// Set up the Shopify API credentials and URL
$shopifyApiKey = '68d7897c7f55247505dff225bdb3d4e8';
$shopifyApiSecret = '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';
$shopifyStoreUrl = 'https://test123-4955.myshopify.com';

// Get the fulfillment orders for the specified order ID
$url = $shopifyStoreUrl . '/admin/api/2023-04/orders/'.$id.'/fulfillment_orders.json';

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'X-Shopify-Access-Token: ' . $shopifyApiSecret
));

$response = curl_exec($ch);
//print_r($response);
// Check if the API call was successful
if (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to get fulfillment orders']);
    exit;
}

$responseArray = json_decode($response, true);
//print_r($responseArray);
//die;
$response = curl_exec($ch);

$responseArray = json_decode($response, true);

// Get the order ID from the response array
$allOrders = $responseArray['fulfillment_orders'];

$fulfillmentId = 0;
$arrayNew = [];
foreach ($allOrders as $order) {
    if ($order['order_id'] == $id) {
        $fulfillmentId = $order['id'];
    }

    foreach ($order['line_items'] as $key => $line) {
        $arrayNew[$key]['fulfillment_order_id'] = $line['fulfillment_order_id'];
        $arrayNew[$key]['fulfillment_order_line_items'] = [
            [
                "id" => $line['id'],
                "quantity" => 1
            ]
        ];
    }
}
$url = $shopifyStoreUrl. '/admin/api/2023-04/fulfillments.json';


// Create an array with the order data
$orderData = array(
    'fulfillment' => array(
        'message' => 'The package was shipped this morning.',
        'notify_customer' => 'false',
        'tracking_info' => array(
            'number' => $trackingNumber,
            'url' => $trackingNumberUrl,
        ),
        'line_items_by_fulfillment_order' => $arrayNew,
    )
);

$orderDataJson = json_encode($orderData);
//echo($orderDataJson);
//die;
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POSTFIELDS, $orderDataJson);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'X-Shopify-Access-Token: ' . $shopifyApiSecret
));

$response = curl_exec($ch);
print_r($response);
die;
// Check for errors
if ($response === false) {
    http_response_code(500);
    $response = array('error' => 'Failed to update shipping information');
    echo json_encode($response);
    exit;
}

curl_close($ch);

$response_array = json_decode($response, true);
if (isset($response_array['fulfillment'])) {
    http_response_code(200);
    $response = array('success' => 'Shipping information');
        echo json_encode($response);
    exit;
    }else{
      http_response_code(500);
    $response = array('error' => 'Failed to update shipping information');
    echo json_encode($response_array);exit;
    } 	
