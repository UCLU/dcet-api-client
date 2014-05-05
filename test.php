<?php

if (!file_exists('vendor/autoload.php')) {
  exit('Autoloader not present (you forgot to run "composer install")');
}

// Set up Composer-based autoloading.
require 'vendor/autoload.php';

// Include test variables.
require 'test_data.php';

// Initialise a Drupal Services API client, with $test_url as the base URL. An
// example value of $test_url would be 'https://example.com/api'.
echo "Base (endpoint) URL: $test_url\n";
$drupal = new DCET\DrupalServicesClient($test_url, isset($test_options) ? $test_options : []);

// Log in. This uses Drupal's standard cookie-based authentication.
$drupal->login($test_username, $test_password);

// Print the user ID of the logged in user.
if ($drupal->isLoggedIn()) {
  echo "Logged in successfully (UID: " . $drupal->getUid() . ")\n";
}

// Initialise a Commerce Event Ticket API client, using the Drupal client.
$checker = new DCET\TicketClient($drupal);

// Get the next ticketed event.
$events = $checker->getNodes(0, 1);
if (!count($events)) {
  echo "No events found\n";
  exit;
}
$event = reset($events);

// Display some event information.
echo "Next available event: " . $event['title'];
if (isset($event['start_date'])) {
  $date = date('H:i, j F Y', strtotime($event['start_date']));
  echo " ($date)";
}
echo "\n";

// Get a list of tickets for the event.
echo "  Loading up to 3 event tickets\n";
$tickets = $checker->getNodeTickets($event['nid'], 0, 3);
$count = count($tickets);
echo "    $count found\n";
if (!$count) {
  exit;
}

// Display the ticket validity.
$barcodes = array();
foreach ($tickets as $ticket) {
  echo "      {$ticket['barcode_token']}: ";
  if ($ticket['valid']) {
    echo "valid";
  }
  else {
    echo "not valid ('{$ticket['reason']}')";
  }
  echo "\n";
}
