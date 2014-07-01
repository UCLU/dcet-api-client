<?php
/**
 * @file
 * Ticket checking API client.
 */

namespace DCET;

use DCET\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;

class TicketClient implements TicketClientInterface {

  protected $drupal;

  /**
   * @param DrupalServicesClientInterface $drupal_client
   */
  public function __construct(DrupalServicesClientInterface $drupal_client) {
    $this->drupal = $drupal_client;
  }

  /**
   * @{inheritdoc}
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
   * @{inheritdoc}
   */
  public function markTicketUsed($barcode, $log = 'Validated via API client') {
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
    catch (ClientException $e) {
      // Deal with the 'Ticket not validated' error.
      if ($e->getResponse() && $e->getResponse()->getStatusCode() == 400) {
        return $e->getResponse()->json();
      }
      throw $e;
    }
    return $response->json();
  }

  /**
   * @{inheritdoc}
   */
  public function markMultipleTicketsUsed(array $tickets, $log = 'Validated via API client') {
    if (!$this->drupal->isLoggedIn()) {
      throw new RequestException('Not logged in');
    }
    if (count($tickets) == 0) {
      throw new RequestException('No tickets specified');
    }
    elseif (count($tickets) > 100) {
      throw new RequestException('Too many tickets');
    }
    $options = ['body' => ['tickets' => $tickets, 'log' => $log]];
    $response = $this->drupal->post('event-ticket/validate-multiple', $options);
    return $response->json();
  }

  /**
   * @{inheritdoc}
   */
  public function resetTicket($barcode, $log = 'Reset via API client') {
    if (!$this->isBarcodeValid($barcode)) {
      throw new RequestException('Invalid barcode');
    }
    if (!$this->drupal->isLoggedIn()) {
      throw new RequestException('Not logged in');
    }
    $options = ['body' => ['log' => $log]];
    $response = $this->drupal->post('event-ticket/' . $barcode . '/reset', $options);
    return $response->json();
  }

  /**
   * @{inheritdoc}
   */
  public function getNodes($offset = 0, $limit = 50, $date_filter = TRUE, $date_sort = TRUE) {
    $options = [
      'query' => [
        'offset' => $offset,
        'limit' => $limit,
        'date_filter' => $date_filter ? 1 : 0,
        'date_sort' => $date_sort ? 1 : 0,
      ],
    ];
    $response = $this->drupal->get('event-ticket-nodes', $options);
    return $response->json();
  }

  /**
   * @{inheritdoc}
   */
  public function getNodeTickets($nid, $offset = 0, $limit = 50, $changed_since = 0) {
    $options = [
      'query' => [
        'offset' => $offset,
        'limit' => $limit,
        'changed_since' => $changed_since,
      ],
    ];
    $response = $this->drupal->get('node/' . $nid . '/tickets', $options);
    return $response->json();
  }

}
