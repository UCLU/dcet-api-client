<?php
/**
 * @file
 * A Drupal Services API client.
 */

namespace DCET;

use DCET\Exception\ResponseException;
use GuzzleHttp\Client as GuzzleClient;

class DrupalServicesClient implements DrupalServicesClientInterface {

  protected $csrf_token;
  protected $http;
  protected $logged_in = FALSE;
  protected $username;
  protected $uid = 0;

  /**
   * Constructor.
   *
   * @param string $endpoint_url
   *   The full URL to the (Drupal Services) API endpoint, for example
   *   'https://example.com/api'.
   * @param array $options
   *   An array of options to pass to \GuzzleHttp\Client::__construct().
   */
  public function __construct($endpoint_url, array $options = []) {
    $options = array_merge_recursive([
      'base_url' => rtrim($endpoint_url, '/') . '/',
      'defaults' => [
        // Disable redirects to avoid confusing responses.
        'allow_redirects' => FALSE,
        // Enable cookies for every request.
        'cookies' => TRUE,
        // Use JSON requests and accept JSON responses by default.
        'headers' => [
          'Accept' => 'application/json',
          'Content-Type' => 'application/json',
        ]
      ],
    ], $options);
    $this->http = new GuzzleClient($options);
  }

  /**
   * @{inheritdoc}
   */
  public function get($path, array $options = []) {
    return $this->http->get($path, $options);
  }

  /**
   * @{inheritdoc}
   *
   * This ensures that the correct CSRF token is passed as a header, which is
   * necessary for (nearly) all Drupal Services POST requests.
   */
  public function post($path, array $options = []) {
    $options['headers']['X-CSRF-Token'] = $this->getCsrfToken();
    return $this->http->post($path, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function login($username, $password) {
    if ($this->logged_in && $this->username === $username) {
      return TRUE;
    }
    elseif ($this->logged_in) {
      $this->logout();
    }
    $options = ['body' => ['name' => $username, 'pass' => $password]];
    $response = $this->http->post('user/login', $options);
    if ($response->getStatusCode() == 200) {
      $this->logged_in = TRUE;
      $this->username = $username;
      $data = $response->json();
      $this->csrf_token = $data['token'];
      $this->uid = $data['user']['uid'];
      return TRUE;
    }
    throw new ResponseException('Failed to log in');
  }

  /**
   * {@inheritdoc}
   */
  public function logout() {
    if (!$this->logged_in) {
      return TRUE;
    }
    $response = $this->post('user/logout');
    if ($response->getStatusCode() == 200) {
      $this->logged_in = FALSE;
      $this->username = NULL;
      $this->csrf_token = NULL;
      $this->uid = 0;
      return TRUE;
    }
    throw new ResponseException('Failed to log out');
  }

  /**
   * @{inheritdoc}
   */
  public function isLoggedIn() {
    return $this->logged_in;
  }

  /**
   * @{inheritdoc}
   */
  public function getUsername() {
    return $this->username;
  }

  /**
   * @{inheritdoc}
   */
  public function getUid() {
    return $this->uid;
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
    $response = $this->http->post('user/token');
    $data = $response->json();
    return $data['token'];
  }

}
