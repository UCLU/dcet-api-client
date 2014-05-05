<?php
/**
 * @file
 * Ticket checking API client.
 */

namespace DCET;

use DCET\Exception\RequestException;
use DCET\Exception\ResponseException;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;

class TicketClient implements ClientInterface {

  protected $drupal;

  /**
   * @param DrupalServicesClientInterface $drupal_client
   */
  public function __construct(DrupalServicesClientInterface $drupal_client) {
    $this->drupal = $drupal_client;
  }

  /**
   * Get information for ticket.
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
  public function getTicket($barcode) {
    if (!$this->isBarcodeValid($barcode)) {
      throw new RequestException('Invalid barcode');
    }
    if (!$this->drupal->isLoggedIn()) {
      throw new RequestException('Not logged in');
    }
    $response = $this->drupal->get('event-ticket/' . $barcode);
    return $response->json();
  }

  /**
   * Check whether a barcode is valid (before sending it to the API).
   *
   * @param string $barcode
   *
   * @return bool
   */
  protected function isBarcodeValid($barcode) {
    return preg_match('/^[A-Z0-9]{6,12}$/i', $barcode);
  }

  /**
   * Mark a ticket as used.
   *
   * @param string $barcode
   *   The ticket's barcode.
   * @param string $log
   *   A log message to save, if the ticket is marked as used.
   *
   * @throws RequestException
   * @throws ResponseException
   *
   * @return array
   *   An array containing the keys 'validated' (bool) and, if the ticket was
   *   not validated, a 'reason' (string).
   */
  public function markTicketUsed($barcode, $log = NULL) {
    if (!$this->isBarcodeValid($barcode)) {
      throw new RequestException('Invalid barcode');
    }
    if (!$this->drupal->isLoggedIn()) {
      throw new RequestException('Not logged in');
    }
    $options = ['body' => ['log' => $log]];
    try {
      $response = $this->drupal->post('event-ticket/' . $barcode . '/validate', $options);
    }
    catch (GuzzleClientException $e) {
      // Deal with the 'Ticket not validated' error.
      if ($e->getResponse() && $e->getResponse()->getStatusCode() == 400) {
        return $e->getResponse()->json();
      }
      throw $e;
    }
    return $response->json();
  }

  /**
   * Mark multiple tickets as used.
   *
   * @param array $tickets
   *   The tickets to validate (as an array of barcodes).
   * @param string $log
   *   A log message to save, if the tickets are marked as used.
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
  public function markMultipleTicketsUsed(array $tickets, $log = NULL) {
    if (!$this->drupal->isLoggedIn()) {
      throw new RequestException('Not logged in');
    }
    if (count($tickets) == 0) {
      throw new RequestException('No tickets specified');
    } elseif (count($tickets) > 100) {
      throw new RequestException('Too many tickets');
    }
    $options = ['body' => ['tickets' => $tickets, 'log' => $log]];
    $response = $this->drupal->post('event-ticket/validate-multiple', $options);
    return $response->json();
  }

  /**
   * Get a list of nodes with tickets (AKA events).
   *
   * @param int $offset
   *   The offset from 0 for the nodes to retrieve. Optional, default: 0.
   * @param int $limit
   *   The limit for the number of nodes to retrieve. Optional, default: 50.
   *
   * @return array
   *   An array of nodes, each one being an array containing the keys 'nid',
   *   'title', and potentially 'start_date' and 'end_date'.
   */
  public function getNodes($offset = 0, $limit = 50) {
    $options = ['query' => ['offset' => $offset, 'limit' => $limit]];
    $response = $this->drupal->get('event-ticket-nodes', $options);
    return $response->json();
  }

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
   *   A UNIX timestamp. Tickets will only be retrieved if they were modified
   *   after this timestamp. Optional, default: 0 (no restriction).
   *
   * @return array
   *   A list of tickets for the node, each ticket being an array of the same
   *   information the getTicket() method provides.
   */
  public function getNodeTickets($nid, $offset = 0, $limit = 50, $changed_since = 0) {
    $options = ['query' => [
      'offset' => $offset,
      'limit' => $limit,
      'changed_since' => $changed_since,
    ]];
    $response = $this->drupal->get('node/' . $nid . '/tickets', $options);
    return $response->json();
  }

}
