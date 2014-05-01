<?php

if (!file_exists('vendor/autoload.php')) {
  exit('Autoloader not present (you forgot to run "composer install")');
}

// Set up Composer-based autoloading.
require 'vendor/autoload.php';

// Include test variables.
require 'test_data.php';

// Initialise a Drupal API client.
$drupal = new DCET\DrupalClient($test_url);

// Log in. This uses Drupal's standard cookie-based authentication.
$drupal->login($test_username, $test_password);

// Print the user ID of the logged in user.
if ($drupal->isLoggedIn()) {
  echo "Logged in successfully (user ID: " . $drupal->getUserId() . ")\n";
}

// Check a ticket's validity.
echo "Checking ticket barcode '$test_barcode'\n";
$checker = new DCET\TicketClient($drupal);
$ticket = $checker->getTicket($test_barcode);
if ($ticket['valid']) {
  echo "Valid\n";
}
else {
  echo "Invalid: " . $ticket['reason'] . "\n";
}
