<?php
/**
 * @file
 * Interface for a Drupal Services API client.
 */

namespace DCET;

interface DrupalClientInterface extends ClientInterface {

  /**
   * Log in to Drupal.
   *
   * @return bool
   */
  public function login($username, $password);

  /**
   * Log out of Drupal.
   *
   * @return bool
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
   * Get the currently logged in user ID.
   *
   * @return int
   *   The Drupal user ID (AKA 'uid'). If anonymous (not logged in), this is 0.
   */
  public function getUserId();

  /**
   * Make a GET request to Drupal.
   *
   * @param string $path
   * @param array $options
   */
  public function get($path, array $options = array());

  /**
   * Make a POST request to Drupal.
   *
   * @param string $path
   * @param array $options
   */
  public function post($path, array $options = array());

}
