<?php

if (!file_exists('vendor/autoload.php')) {
  exit('Autoloader not present (you forgot to run "composer install")');
}

// Set up Composer-based autoloading.
require 'vendor/autoload.php';

// Include test variables.
require 'test_data.php';

// Make a new API client object.
$api = new DCET\ApiClient($test_url);

// Log in. This uses Drupal's standard cookie-based authentication.
$api->login($test_username, $test_password);

// Get information for a ticket.
print_r($api->getTicket($test_barcode));

// Log out.
$api->logout();
