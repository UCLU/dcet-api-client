<?php
/**
 * @file
 * Interface for a Drupal Services API client.
 */

namespace DCET;

use DCET\Exception\ResponseException;
use GuzzleHttp\Message\ResponseInterface;

interface DrupalServicesClientInterface {

  /**
   * Log in to Drupal.
   *
   * @param string $username
   * @param string $password
   *
   * @return bool
   * @throws ResponseException
   */
  public function login($username, $password);

  /**
   * Log out of Drupal.
   *
   * @return bool
   * @throws ResponseException
   */
  public function logout();

  /**
   * Check whether we are logged in.
   *
   * @return bool
   */
  public function isLoggedIn();

  /**
   * Get the currently logged in username.
   *
   * This corresponds to Drupal's $user->name property.
   *
   * @return string|NULL
   *   A string if logged in, NULL otherwise.
   */
  public function getUsername();

  /**
   * Get the currently logged in user ID (UID).
   *
   * @return int
   *   The Drupal user ID (UID). If anonymous (not logged in), this is 0.
   */
  public function getUid();

  /**
   * Make a GET request to Drupal.
   *
   * @param string $path
   * @param array $options
   *
   * @return ResponseInterface
   */
  public function get($path, array $options = []);

  /**
   * Make a POST request to Drupal.
   *
   * @param string $path
   * @param array $options
   *
   * @return ResponseInterface
   */
  public function post($path, array $options = []);

}
