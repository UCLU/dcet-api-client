API Client Library for Drupal Commerce Event Ticket
===================================================
This is a PHP client library for Drupal's [Commerce Event Ticket](https://drupal.org/project/commerce_event_ticket)
module.

Getting started
---------------
Configuring the Drupal site:

  1. Enable the Commerce Event Ticket Services module (cet_services).
  2. In admin/structure/services, create a new endpoint. Select REST as the
     server, and enable 'Session authentication' as the authentication scheme.
  3. In the endpoint's configuration, under 'Server', enable the 'json' response
     formatter and the 'application/json' request parser type.
  4. Under 'Resources', enable the following resources for the endpoint:
       - user: login, logout, token
       - event-ticket: retrieve, validate
       - event-ticket-nodes: index
       - node: tickets

Then see `test.php` for how to use the client.

Note that you will need to add a file called `test_data.php` to use test.php
as it is. The file should look like this:

```php
<?php

// Endpoint URL.
$test_url = 'https://example.com/api';

// The credentials.
$test_username = 'username';
$test_password = 'password';
```
