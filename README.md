API Client Library for Drupal Commerce Event Ticket
===================================================
Under construction.

This will be an API client library for Drupal's Commerce Event Ticket module:
  https://drupal.org/project/commerce_event_ticket

Getting started
---------------
Configuring the Drupal site:

  1. Enable the Commerce Event Ticket Services module (cet_services).
  2. In admin/structure/services, create a new REST endpoint, enabling the
     following resources:
       - user: login (API version 1.1), logout (API Version 1.1), token
       - event-ticket: retrieve, validate

See test.php for how to use the client.

More to follow...
