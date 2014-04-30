<?php
/**
 * @file
 * Interface for a ticket checking API client.
 */

namespace DCET;

interface ApiClientInterface {

  /**
   * Log in to the API.
   *
   * @return bool
   *   TRUE on success, FALSE on failure to log in.
   */
  public function login($username, $password);

  /**
   * Log out of the API.
   *
   * @return bool
   *   TRUE on success, FALSE on failure to log out.
   */
  public function logout();

}
