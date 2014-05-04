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
   * @return array
   *   An array of ticket information. This throws an ApiClientException on failure.
   */
  public function getTicket($barcode) {
    if (!$this->isBarcodeValid($barcode)) {
      throw new RequestException('Invalid barcode');
    }
    if (!$this->drupal->isLoggedIn()) {
      throw new RequestException('Not logged in');
    }
    $response = $this->drupal->get('event-ticket/' . $barcode);
    if ($response->getStatusCode() == 200) {
      return $response->json();
    }
    throw new ResponseException('Failed to get ticket information');
  }

  /**
   * Mark a ticket as used.
   *
   * @param string $barcode
   *   The ticket's barcode.
   * @param string $log
   *   A log message to save, if the ticket is marked as used.
   *
   * @return array
   *   An array containing the keys 'validated' (TRUE or FALSE) and, if the
   *   ticket was not validated, a 'reason' (string).
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
      // Deal with the 400 response status (when the ticket is not valid).
      $response = $e->getResponse();
      if ($response && $response->getStatusCode() == 400) {
        return $response->json();
      }
      throw $e;
    }
    if ($response->getStatusCode() == 200) {
      return $response->json();
    }
    throw new ResponseException('Failed to mark ticket used');
  }

  /**
   * Mark multiple tickets as used.
   *
   * @param array $tickets
   *   The tickets to validate (as an array of barcodes).
   * @param string $log
   *   A log message to save, if the tickets are marked as used.
   *
   * @return array
   *   An array of validation results keyed by the tickets' barcodes, each
   *   result being an array containing the keys:
   *     - 'found' (TRUE or FALSE)
   *     - 'validated' (TRUE or FALSE)
   *   and, if the ticket was found yet not validated, a 'reason' (string).
   */
  public function markMultipleTicketsUsed(array $tickets, $log = NULL) {
    if (!$this->drupal->isLoggedIn()) {
      throw new RequestException('Not logged in');
    }
    if (count($tickets) == 0) {
      throw new RequestException('No tickets specified');
    }
    elseif (count($tickets) > 100) {
      throw new RequestException('Too many tickets');
    }
    $options = [
      'body' => ['tickets' => $tickets, 'log' => $log],
      'headers' => ['Content-Type' => 'application/json'],
    ];
    $response = $this->drupal->post('event-ticket/validate-multiple', $options);
    if ($response->getStatusCode() == 200) {
      return $response->json();
    }
    throw new ResponseException('Failed to mark tickets used');
  }

  /**
   * Get a list of nodes with tickets (AKA events).
   *
   * @param int $offset
   * @param int $limit
   *
   * @return array
   *   An array of nodes, each one being an array containing the keys 'nid',
   *   'title', and potentially 'start_date' and 'end_date'.
   */
  public function getNodes($offset = 0, $limit = 50) {
    $options = ['query' => ['offset' => $offset, 'limit' => $limit]];
    $response = $this->drupal->get('event-ticket-nodes', $options);
    if ($response->getStatusCode() == 200) {
      return $response->json();
    }
    throw new ResponseException('Failed to get nodes');
  }

  /**
   * Get a list of tickets for a given node.
   *
   * @param int $nid
   *   The node ID.
   * @param int $offset
   * @param int $limit
   */
  public function getNodeTickets($nid, $offset = 0, $limit = 50) {
    $options = ['query' => ['offset' => $offset, 'limit' => $limit]];
    $response = $this->drupal->get('node/' . $nid . '/tickets', $options);
    if ($response->getStatusCode() == 200) {
      return $response->json();
    }
    throw new ResponseException('Failed to get tickets for node');
  }

  /**
   * Check whether a barcode is valid (before sending it to the API).
   *
   * @param string $barcode
   *
   * @return bool
   */
  protected function isBarcodeValid($barcode) {
    $length = strlen($barcode);
    return $length >= 5 && $length <= 30;
  }

}
