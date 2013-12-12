<?php

const CLIENT_ID = '380700573108-ultlr1k746i390vguf8gpirsknpq4kp5.apps.googleusercontent.com';
const SERVICE_ACCOUNT_NAME = '380700573108-cehlj9649g5d22sts22qt8c3mhfkprdl@developer.gserviceaccount.com';
const KEY_FILE = '37ed36997c97819e019d8ef2e434e29cd4c4c031-privatekey.p12';

/*
set_include_path("google-api-php-client/src/" . PATH_SEPARATOR . get_include_path());
require_once 'google-api-php-client/src/Google/Client.php';
require_once 'google-api-php-client/src/Google/Service/Analytics.php';
*/
require_once 'google-api-php-client/src/Google_Client.php';
require_once 'google-api-php-client/src/contrib/Google_AnalyticsService.php';

session_start();

$client = new Google_Client();

$client->setApplicationName("WP Plugin");

if (isset($_SESSION['token'])) {
 $client->setAccessToken($_SESSION['token']);
}


$key = file_get_contents(KEY_FILE);
$client->setClientId(CLIENT_ID);
$client->setAssertionCredentials(new Google_AssertionCredentials(
  SERVICE_ACCOUNT_NAME,
  array('https://www.googleapis.com/auth/analytics'),
  $key)
);
$client->setScopes(array('https://www.googleapis.com/auth/analytics.readonly'));
$client->setUseObjects(true);

$service = new Google_AnalyticsService($client);


 try {
    // Success. Do something cool!

	$optParams = array(
		// 'dimensions' => 'ga:source,ga:keyword',
		// 'sort' => '-ga:visits,ga:source',
		// 'filters' => 'ga:medium==organic',
		// 'max-results' => '25'
	);

	$results = $service->data_ga->get(
		'ga:71980643',
		'2013-12-01',
		'2013-12-15',
		'ga:visits',
		$optParams
	);

	print_r($results);


  } catch (apiServiceException $e) {
    // Handle API service exceptions.
    $error = $e->getMessage();
  }


