<?php
/**
 * @file
 * Ticket checking API client.
 */

namespace DCET;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class ApiClient implements ApiClientInterface {

  protected $client;
  protected $csrf_token;
  protected $logged_in;

  /**
   * Constructor.
   *
   * @param string $endpoint_url
   *   The full URL to the (Drupal Services) API endpoint, for example
   *   'https://example.com/api'.
   */
  public function __construct($endpoint_url) {
    $this->client = new Client(array(
      'base_url' => rtrim($endpoint_url, '/') . '/',
      'defaults' => array('allow_redirects' => FALSE, 'cookies' => TRUE),
    ));
  }

  /**
   * @{inheritdoc}
   */
  public function login($username, $password) {
    if ($this->logged_in) {
      return TRUE;
    }
    $response = $this->client->post('user/login', array('body' => array(
      'name' => $username,
      'pass' => $password,
    )));
    if ($response->getStatusCode() == 200) {
      $this->logged_in = TRUE;
      return TRUE;
    }
    throw new ApiClientException('Failed to log in');
  }

  /**
   * @{inheritdoc}
   */
  public function logout() {
    if (!$this->logged_in) {
      return TRUE;
    }
    $options = array();
    $options['headers']['X-CSRF-Token'] = $this->getCsrfToken();
    $response = $this->client->post('user/logout', $options);
    if ($response->getStatusCode() == 200) {
      $this->logged_in = FALSE;
      return TRUE;
    }
    throw new ApiClientException('Failed to log out');
  }

  /**
   * Get the Drupal Services CSRF token for this client.
   *
   * @return string
   */
  protected function getCsrfToken() {
    if ($this->csrf_token !== NULL) {
      return $this->csrf_token;
    }
    $options = array();
    $options['headers']['Accept'] = 'application/json';
    $response = $this->client->post('user/token', $options);
    if ($response->getStatusCode() == 200) {
      $data = $response->json();
      return $data['token'];
    }
    throw new ApiClientException('Failed to get CSRF token');
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
      throw new ApiClientException('Invalid barcode');
    }
    if (!$this->logged_in) {
      throw new ApiClientException('Not logged in');
    }
    $options = array();
    $options['headers']['Accept'] = 'application/json';
    $response = $this->client->get('event-ticket/' . $barcode, $options);
    if ($response->getStatusCode() == 200) {
      return $response->json();
    }
    throw new ApiClientException(sprintf('Unexpected response status: %d', $response->getStatusCode()));
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
      throw new ApiClientException('Invalid barcode');
    }
    if (!$this->logged_in) {
      throw new ApiClientException('Not logged in');
    }
    $options = array();
    $options['headers']['Accept'] = 'application/json';
    $options['headers']['X-CSRF-Token'] = $this->getCsrfToken();
    if ($log) {
      $options['query']['log'] = $log;
    }
    try {
      $response = $this->client->post('event-ticket/' . $barcode . '/validate', $options);
    }
    catch (ClientException $e) {
      // Deal with the 400 response status (when tickets are not valid).
      $response = $e->getResponse();
      if ($response && $response->getStatusCode() == 400) {
        return $response->json();
      }
      throw $e;
    }
    if ($response->getStatusCode() == 200) {
      return $response->json();
    }
    throw new ApiClientException(sprintf('Unexpected response status: %d', $response->getStatusCode()));
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
