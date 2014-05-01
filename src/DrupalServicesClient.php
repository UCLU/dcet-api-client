<?php
/**
 * @file
 * A Drupal Services API client.
 */

namespace DCET;

use DCET\Exception\RequestException;
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
   */
  public function __construct($endpoint_url) {
    $guzzle_options = array();

    // Set up the base URL of every request.
    $guzzle_options['base_url'] = rtrim($endpoint_url, '/') . '/';

    // Disable redirects to avoid confusing responses.
    $guzzle_options['defaults']['allow_redirects'] = FALSE;

    // Enable cookies for every request, so that we can easily remain logged in.
    $guzzle_options['defaults']['cookies'] = TRUE;

    // Request JSON responses by default.
    $guzzle_options['defaults']['headers']['Accept'] = 'application/json';

    $this->http = new GuzzleClient($guzzle_options);
  }

  /**
   * @{inheritdoc}
   */
  public function get($path, array $options = array()) {
    return $this->http->get($path, $options);
  }

  /**
   * @{inheritdoc}
   *
   * This ensures that the correct CSRF token is passed as a header, which is
   * necessary for (nearly) all Drupal Services POST requests.
   */
  public function post($path, array $options = array()) {
    $options['X-CSRF-Token'] = $this->getCsrfToken();
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
    $options = array();
    $options['body']['username'] = $username;
    $options['body']['password'] = $password;
    $options['headers']['Content-Type'] = 'application/json';
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
    $options = array();
    $options['headers']['X-CSRF-Token'] = $this->getCsrfToken();
    $response = $this->http->post('user/logout', $options);
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
  public function getUserId() {
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
    if ($response->getStatusCode() == 200) {
      $data = $response->json();
      return $data['token'];
    }
    throw new ResponseException('Failed to get CSRF token');
  }

}
