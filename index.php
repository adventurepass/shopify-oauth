<?php

$apiKey = getenv('API_CLIENT_ID'); //from partner account app settings
$secret = getenv('API_CLIENT_SECRET'); //from partner account app settings
$scope = getenv('API_SCOPE'); //required permissions
$redirectUri = getenv('API_REDIRECT_URL'); //this URL has to be in "Whitelisted redirection URL(s)" in shopify partner account app setting

$access_token_url_format = "https://%s/admin/oauth/access_token";
$auth_url_format = "https://%s/admin/oauth/authorize?client_id=%s&scope=%s&redirect_uri=%s";

pr($_REQUEST, false);

//oauth callback url call from shopify
if (isset($_GET['code']) && isset($_GET['shop'])) {
  try {
      $response = getAccessToken($_GET['shop'], $_GET['code']);
      pr($response, false);
  } catch (Exception $e) {
      echo 'Curl call for access token error: ' . $e->getMessage() . '<br>';
  }
}

//first call (app not installed in store) - app has to redirect to shopify oauth url, if store owner clicks install app then app gets installed in store
else if (isset($_GET['hmac']) && isset($_GET['shop'])) {
    redirecToShopifyAuth($_GET['shop']);
}

echo "Basic Shopify oAuth example app";

function redirecToShopifyAuth($shop) {
    global $auth_url_format, $apiKey, $scope, $redirectUri;
    $auth_url = sprintf($auth_url_format, $shop, $apiKey, $scope, $redirectUri);

    header("HTTP/1.1 301 Moved Permanently");
    header('Location:  '.  $auth_url);
    exit;
}

function getAccessToken($shop, $code) {
    // pr($_REQUEST, false);
    global $access_token_url_format, $apiKey, $secret;

    $query = array(
      'client_id' => $apiKey,
      'client_secret' => $secret,
      'code' => $code
    );

    $access_token_url = sprintf($access_token_url_format, $shop);

    // Configure curl client and execute request
    $curl = curl_init();
    $curlOptions = array(
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_URL => $access_token_url,
      CURLOPT_POSTFIELDS => http_build_query($query), // CURLOPT_POSTFIELDS sets the method to POST automatically
      CURLOPT_VERBOSE => true,
      CURLOPT_FAILONERROR => true,
    );
    curl_setopt_array($curl, $curlOptions);
    $jsonResponse = json_decode(curl_exec($curl), TRUE);

    if ($error_no = curl_errno($curl)) {
      $error_msg = curl_error($curl);
      throw new Exception("Curl error no: $error_no - error: $error_msg");
    }
    curl_close($curl);

    return $jsonResponse;
}

//utility function
function pr($whatever, $exit = true) {
    echo '<pre>';
    print_r($whatever);
    echo '</pre>';

    if ($exit) exit;
}
