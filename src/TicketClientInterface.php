<?php
/**
 * @file
 * Interface for a ticket checking API client.
 */

namespace DCET;

use DCET\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;

interface TicketClientInterface {

  /**
   * Get information for a ticket.
   *
   * @param string $barcode
   *   A ticket barcode.
   *
   * @throws RequestException
   *
   * @return array
   *   An array of ticket information, containing at least these keys:
   *     - 'ticket_id' (int) The unique ID of the ticket.
   *     - 'barcode_token' (string) The ticket's barcode.
   *     - 'valid' (bool) Whether the ticket is valid at this time.
   *     - 'reason' (string) Why the ticket is invalid (if 'valid' is FALSE).
   *     - 'used' (bool) Whether the ticket has been used before.
   *     - 'position' (string) The ticket's position within the customer's
   *        ticket orders for this product, e.g. '1 of 2', or '2 of 2', etc.
   *     - 'created' (string) The date the ticket was created (ISO 8601).
   *     - 'changed' (string) The date the ticket was last changed (ISO 8601).
   *   and potentially further information, depending on the privileges of the
   *   logged in user.
   */
  public function getTicket($barcode);

  /**
   * Mark a ticket as used.
   *
   * @param string $barcode
   *   The ticket's barcode.
   * @param string $log
   *   A log message to save, if the ticket is marked as used. Optional,
   *   default: 'Validated via API client'.
   *
   * @throws RequestException
   * @throws ClientException
   *
   * @return array
   *   An array containing the keys 'validated' (bool) and, if the ticket was
   *   not validated, a 'reason' (string).
   */
  public function markTicketUsed($barcode, $log = 'Validated via API client');

  /**
   * Mark multiple tickets as used.
   *
   * @param array $tickets
   *   The tickets to validate (as an array of barcodes).
   * @param string $log
   *   The log message to save, if the tickets are marked as used. Optional,
   *   default: 'Validated via API client'.
   *
   * @throws RequestException
   *
   * @return array
   *   An array of validation results keyed by the tickets' barcodes, each
   *   result being an array containing the keys:
   *     - 'found' (bool)
   *     - 'validated' (bool)
   *   and, if the ticket was found yet not validated, a 'reason' (string).
   */
  public function markMultipleTicketsUsed(array $tickets, $log = 'Validated via API client');

  /**
   * Reset a ticket (mark it as unused).
   *
   * @param string $barcode
   *   The ticket's barcode.
   * @param string $log
   *   A log message to save, if the ticket is reset. Optional, default: 'Reset
   *   via API client'.
   *
   * @throws RequestException
   *
   * @return array
   *   An array containing the keys 'reset' (bool) and 'used' (bool).
   */
  public function resetTicket($barcode, $log = 'Reset via API client');

  /**
   * Get a list of nodes with tickets (AKA events).
   *
   * @param int $offset
   *   The offset from 0 for the nodes to retrieve. Optional, default: 0.
   * @param int $limit
   *   The limit for the number of nodes to retrieve. Optional, default: 50.
   * @param bool $date_filter
   *   Whether to filter nodes by the event dates, if this is possible on the
   *   server side. If enabled, only nodes with a start date equal to or later
   *   than today will be returned. Defaults to TRUE.
   * @param bool $date_sort
   *   Whether to sort nodes by the event dates, if possible.
   *
   * @return array
   *   An array of nodes, each one being an array containing the keys 'nid',
   *   'title', and potentially 'start_date' and 'end_date'.
   */
  public function getNodes($offset = 0, $limit = 50, $date_filter = TRUE, $date_sort = TRUE);

  /**
   * Get a list of tickets for a given node.
   *
   * @param int $nid
   *   The node ID.
   * @param int $offset
   *   The offset from 0 for the tickets to retrieve. Optional, default: 0.
   * @param int $limit
   *   The limit for the number of tickets to retrieve. Optional, default: 50.
   * @param int $changed_since
   *   A UNIX timestamp. Tickets will only be retrieved if they were modified or
   *   created after this timestamp (so you can use this to check for updates
   *   regularly). Optional, default: 0 (no restriction).
   *
   * @return array
   *   A list of tickets for the node, each ticket being an array of the same
   *   information the getTicket() method provides.
   */
  public function getNodeTickets($nid, $offset = 0, $limit = 50, $changed_since = 0);

}
