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
$drupal = new DCET\DrupalServicesClient($test_url, isset($test_options) ? $test_options : []);

// Log in. This uses Drupal's standard cookie-based authentication.
$drupal->login($test_username, $test_password);

// Print the user ID of the logged in user.
if ($drupal->isLoggedIn()) {
  echo "Logged in successfully (user ID: " . $drupal->getUserId() . ")\n";
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

// Get a list of tickets for the event.
echo "Looking up tickets for the next available event, '{$event['title']}' ({$event['start_date']})...\n";
$tickets = $checker->getNodeTickets($event['nid']);
$count = count($tickets);
if ($count) {
  echo "$count ticket(s) found!\n";
}

// Log out.
$drupal->logout();
