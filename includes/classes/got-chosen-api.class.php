<?php
/**
 * Class to handle API requests.
 */
class GOT_CHOSEN_API_HANDLER {
  
  /**
   * Base API URL.
   * 
   * @since 1.0
   * @access private
   * @var string $api_url The base URL of the GotChosen API.
   */
  private $api_url = 'https://gotchosen.com/api/';
  
  /**
   * User's feedkey.
   * 
   * @since 1.0
   * @access private
   * @var string $feedkey The user's feedkey, used to authenticate with the API.
   */
  private $feedkey;
  
  /**
   * Message array to output.
   * 
   * @since 1.0
   * @access private
   * @var array $notices Array to hold error messages to output to the admin_notices action.
   */
  private $notices;
  
  /**
   * Constructor function.
   * 
   * Private constructor used to get singleton instance of the class.
   * 
   * @since 1.0
   * @access private
   * 
   * @see get_instance()
   */
  private function __construct() {
    add_action('admin_notices', array(&$this, 'admin_notices'));
    if (!$this -> notices = get_transient('got_chosen_api_notices')) {
      $this -> notices = array();
    }
  }
  
  /**
   * Get instance of the class.
   * 
   * If no instance is set, constructs a new instance and returns it.
   * 
   * @since 1.0
   * 
   * @see __construct()
   * 
   * @return object An instance of the current class.
   */
  public function get_instance() {
    static $instance = null;
    if ($instance === null) {
      $instance = new GOT_CHOSEN_API_HANDLER();
    }
    return $instance;
  }
  
  /**
   * Calls the verifyminifeed endpoint.
   * 
   * Makes the call to the API's verifyminifeed endpoint and returns the call_api() response.
   * On success this will return an object with the user's GCID.
   * 
   * @since 1.0
   * 
   * @see call_api() 
   * 
   * @param array $args Arguments to be passed forward to call_api().
   * @return mixed The response object, or false if an error occured.
   */
  public function verifyminifeed($args = array()) {
    return $this -> call_api('GET', 'verifyminifeed', $args);
  }
  
  /**
   * Calls the minifeed endpoint.
   * 
   * Makes the call to the API's minifeed endpoint and returns the call_api() response.
   * On success this will return an object with the post id of the minifeed submission.
   * 
   * @since 1.0
   * 
   * @see call_api() 
   * 
   * @param array $args Arguments to be passed forward to call_api().
   * @return mixed The response object, or false if an error occured.
   */
  public function minifeed($args = array()) {
    return $this -> call_api('POST', 'minifeed', $args);
  }
  
  /**
   * Calls the minifeed endpoint.
   * 
   * Makes the call to the API endpoint that's passed in and returns the response, 
   * or false if an error occurs.
   * 
   * @since 1.0
   * @access private
   * 
   * @param string $method The HTTP method to be used in the request.
   * @param string $endpoint The API endpoint to make the request against.
   * @param array $args Arguments to be passed forward to wp_remote_{method} functions.
   * @return mixed The response object, or false if an error occured.
   */
  private function call_api($method, $endpoint, $args) {
    // Set common headers.
    $args['headers'] = array('Content-Type' => 'application/json', 'X-GotChosen-Feed-Key' => $this -> feedkey );
    if (!empty($args['body'])) {
      $args['headers']['Content-Length'] = strlen($args['body']);
    }
    $response = array();
    $http = new WP_Http_Streams();
    if ($method == 'GET') {
      $response = $http->request($this -> api_url . $endpoint, $args);
      // Old versions of CURL are completely broken
      // $response = wp_remote_get($this -> api_url . $endpoint, $args);
    } elseif ($method == 'POST') {
      $args['method'] = 'POST';
      $response = $http->request($this -> api_url . $endpoint, $args);
      // Old versions of CURL are completely broken
      // $response = wp_remote_post($this -> api_url . $endpoint, $args);
    }
    // Handle request errors.
    if (is_wp_error($response)) {
      $this -> update_notices('A WordPress API error occured: ' . $response -> get_error_message());
    } elseif ($response['response']['code'] == '403') {
      $this -> update_notices('There was an error authenticating, please check your Feed Key.');
    } elseif ($response['response']['code'] == '500') {
      $this -> update_notices('There was an error contacting the API server.');
    } elseif ($response['response']['code'] == '200') {
      return json_decode($response['body']);
    }
    // If request was not successful, return false.
    return false;
  }
  
  /**
   * Sets the feedkey.
   * 
   * Allows code using this class to set the feedkey property.
   * 
   * @since 1.0
   * 
   * @param string $feedkey The feedkey to be used in API requests.
   */
  public function set_feedkey($feedkey) {
    $this -> feedkey = $feedkey;
  }
  
  /**
   * Adds messages to notices array.
   * 
   * When a notice is generated this adds it to the notices property 
   * and updates the transient containing the notices.
   * 
   * @since 1.0
   * @access private
   * 
   * @see admin_notices()
   * 
   * @param string $message Message to add to the notices array. 
   */
  private function update_notices($message) {
    if (!in_array($message, $this -> notices)) {
      $this -> notices[] = $message;
    }
    set_transient('got_chosen_api_notices', $this -> notices);
  }
  
  /**
   * Outputs notices.
   * 
   * Callback for the admin_notices action that loops through 
   * our notices array and outputs each message. It also cleans up
   * the transient once it's done.
   * 
   * @since 1.0
   * @access private
   * 
   * @see update_notices()
   */
  public function admin_notices() {
    if (!empty($this -> notices)) {
      echo '<div class="error">';
      foreach ($this->notices as $notice) {
        echo '<p>GotChosen Integration: ' . $notice . '</p>';
      }
      echo '</div>';
      delete_transient('got_chosen_api_notices');
    }
  }

}
