<?php
/**
 * Our main plugin class.
 */
class GOT_CHOSEN_INTG_PLUGIN {

  /**
   * Holds an instance of the API class.
   *
   * @since 1.0
   * @access private
   * @var object $api Holds an instance of the API class passed in to the constructor.
   */
  private $api;

  /**
   * Contains the path to the includes folder.
   *
   * @since 1.0
   * @access private
   * @var string $includes_path Path to plugin's includes folder so it
   *  does not need to be evaluated repeatedly.
   */
  private $includes_path;

  /**
   * Contains the URL to the includes folder.
   *
   * @since 1.0
   * @access private
   * @var string $includes_url URL to plugin's includes folder so it
   *  does not need to be evaluated repeatedly.
   */
  private $includes_url;

  /**
   * The user's GCID.
   *
   * @since 1.0
   * @access private
   * @var string $gcid Holds the GCID retrieved from the minifeed API.
   */
  private $gcid;

  /**
   * The options array.
   *
   * @since 1.0
   * @access private
   * @var array $options Holds the options array retrieved with get_option().
   */
  private $options;

  /**
   * Contains the posts to publish.
   *
   * @since 1.0
   * @access private
   * @var array $pub_queue Array to hold all of the information necessary
   *   to send to the minifeed API to publish a post.
   */
  private $pub_queue;

  /**
   * Constructor function.
   *
   * Private constructor used to get singleton instance of the class.
   *
   * @since 1.0
   * @access private
   *
   * @see get_instance()
   *
   * @param object $api Instance of our API handler class.
   * @param string $plugin_file File path to the main plugin file.
   */
  private function __construct($api, $plugin_file) {
    // Initialize our properties.
    $this -> api = $api;
    $this -> includes_path = plugin_dir_path($plugin_file) . 'includes' . DIRECTORY_SEPARATOR;
    $this -> includes_url = plugins_url('includes', $plugin_file);
    $this -> options = get_option('got_chosen_intg_settings', array());
    $this -> api -> set_feedkey($this -> options['feedkey']);
    $this -> gcid = $this -> get_gcid();
    if (!$this -> pub_queue = get_transient('got_chosen_pub_queue')) {
      $this -> pub_queue = array();
    }
    // Add necessary hooks.
    add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
    add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));
    add_action('save_post', array(&$this, 'save_post'));
    add_action('admin_menu', array(&$this, 'admin_menu'));
    add_action('add_meta_boxes_post', array(&$this, 'add_meta_boxes'));
    add_action('wp_head', array(&$this, 'add_meta_tag'));
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
   * @param object $api Instance of our API handler class.
   * @param string $plugin_file File path to the main plugin file.
   * @return object An instance of the current class.
   */
  public function get_instance($api, $plugin_file) {
    static $instance = null;
    if ($instance === null) {
      $instance = new GOT_CHOSEN_INTG_PLUGIN($api, $plugin_file);
    }
    return $instance;
  }

  /**
   * Gets user's GCID.
   *
   * Calls the API's verifyminifeed endpoint to get the GCID
   * based off of the user's feedkey. Stores the GCID in a
   * transient so the API does not need to be called as frequently.
   *
   * @since 1.0
   * @deprecated
   * @access private
   *
   * @see __construct()
   *
   * @return mixed The GCID or false if not retrieved.
   */

  private function get_gcid() {
    if ( !empty($this -> options['gcid']) ) {
        return $this -> options['gcid'];
    }

    if ($gcid = get_transient('got_chosen_intg_gcid')) {
      return $gcid;
    }

    if ( !empty($this -> options['feedkey']) ) {
      $response = $this -> api -> verifyminifeed();
      if ($response) {
        // Don't use this anymore
        // set_transient('got_chosen_intg_gcid', $response -> gcid, (24 * 60 * 60));
        $this -> options['gcid'] = $response -> gcid;
        update_option('got_chosen_intg_settings', $this -> options);

        return $this -> options['gcid'];
      }
    }

    return false;
  }

  /**
   * Callback for wp_enqueue_scripts action.
   *
   * Attaches the webcurtain javascript if it's enabled
   * and a GCID was obtained from the API.
   *
   * @since 1.0
   *
   * @see __construct()
   */
  public function enqueue_scripts() {
    if ($this -> gcid && $this -> options['webcurtain']) {
      wp_register_script('gc_intg_webcurtain', $this -> includes_url . '/js/gc-webcurtain.js', array('jquery'));
      wp_localize_script('gc_intg_webcurtain', 'gc_intg_plugin', array('gcid' => trim($this -> gcid), 'compat' => $this -> options['webcurtain_compat'], ));
      wp_enqueue_script('gc_intg_webcurtain');
    }
  }

  /**
   * Callback for admin_enqueue_scripts action.
   *
   * Attaches the css file for our admin page.
   *
   * @since 1.0
   *
   * @see __construct()
   */
  public function admin_enqueue_scripts() {
    if (isset($_GET['page']) && $_GET['page'] == 'got_chosen') {
      wp_enqueue_style('gc_intg_settings_css', $this -> includes_url . '/css/settings.css');
    }
    wp_enqueue_style('gc_intg_admin_css', $this -> includes_url . '/css/admin.css');
  }

  /**
   * Callback for save_post action.
   *
   * Saves the post meta controlling whether or not the post gets
   * published to a minifeed. Also sees if a post should be published to
   * the minifeed and adds it to the queue if so.
   *
   * @since 1.0
   *
   * @see __construct()
   *
   * @param int $post_id The ID of the post being saved.
   */
  public function save_post($post_id) {
    // Don't do anything on the autosaving of posts.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return;
    }
    // Return if user is not allowed to edit posts.
    if (!current_user_can('edit_post', $post_id)) {
      return;
    }
    // Return if it is a post revision
    if (wp_is_post_revision($post_id)) {
      return;
    }
    // Save our publish option.
    if (isset($_POST['gc_meta_wpnonce']) && wp_verify_nonce($_POST['gc_meta_wpnonce'], 'got chosen save meta')) {
      $publish = isset($_POST['gc_minifeed_publish']) ? 1 : 0;
      update_post_meta($post_id, 'gc_minifeed_publish', $publish);
    }
    // Check if post has been sent to the minifeed API.
    $minifeed_id = get_post_meta($post_id, 'gc_sent_to_minifeed', true);
    if (empty($minifeed_id) && isset($publish) && $publish) {
      $post = get_post($post_id);
      if ($post -> post_type = 'post') {
        $mini_post = new stdClass();
        $mini_post -> title = $post -> post_title;
        $mini_post -> body = wp_trim_words($post -> post_content, 150, '') . ' ' . get_permalink($post_id);
        $mini_post -> shareable = (bool)$this -> options['shareable'];
        $mini_post -> commentable = (bool)$this -> options['commentable'];
        if ($image_src = $this -> get_image_src($post)) {
           $mini_post -> media = array($image_src);
        }
        $args['body'] = json_encode($mini_post);
        $this -> pub_queue[$post_id] = $args;
      }
    }
    // On every post save attempt to send posts to the minifeed API.
    // This is to catch any posts that failed to send due to connectivity issues.
    $this -> process_pub_queue();
  }

  /**
   * Gets the image source for a post.
   *
   * Looks for a featured image source, or the source of the
   * first image in the body of the post, and returns it.
   *
   * @since 1.0.3
   * @access private
   *
   * @see save_post()
   *
   * @param object $post The current post object.
   * @return mixed The URL to the image source, or false.
   */
  private function get_image_src(&$post) {
    if ($post_thumb = get_post_thumbnail_id($post->ID)) {
      return wp_get_attachment_image($post_thumb);
    }
    else {
      // Load content into DOMDocument.
      $post_dom = new DOMDocument();
      $post_dom -> loadHTML($post->post_content);
      // Find all images.
      $images = $post_dom -> getElementsByTagName('img');
      // If we found some, continue.
      if ($images -> length > 0) {
        $first_image = $images -> item(0);
        // Check that the first image has a src attribute,
        // then return it.
        if ($first_image -> hasAttribute('src')) {
          return $first_image -> getAttribute('src');
        }
      }
    }
    return false;
  }
  /**
   * Processes the publish queue.
   *
   * Loops through the publish queue and attempts to publish
   * each post to the minifeed. On success it removes the post from
   * the queue and updates the post with the minifeed ID so it
   * won't get readded to the queue.
   *
   * @since 1.0
   * @access private
   *
   * @see save_post()
   */
  private function process_pub_queue() {
    if (!empty($this -> pub_queue)) {
      foreach ($this->pub_queue as $post_id => $args) {
        $response = $this -> api -> minifeed($args);
        if ($response) {
          update_post_meta($post_id, 'gc_sent_to_minifeed', 1);
          unset($this -> pub_queue[$post_id]);
        }
      }
      set_transient('got_chosen_pub_queue', $this -> pub_queue);
    }
  }

  /**
   * Callback for admin_menu action.
   *
   * Adds the administration menu for the plugins options.
   *
   * @since 1.0
   *
   * @see __construct()
   */
  public function admin_menu() {
    add_menu_page('GotChosen Integration', 'GotChosen', 'manage_options', 'got_chosen', array(&$this, 'build_menu'), $this -> includes_url . '/images/gc-thumb.png');
  }

  /**
   * Callback for add_menu_page function.
   *
   * Outputs the options page as well as handling updating
   * the options.
   *
   * @since 1.0
   *
   * @see admin_menu()
   */
  public function build_menu() {
    // Process submission.
    if ($_POST && isset($_POST['_wpnonce'])) {
      // Verify submission was made on the site.
      if (wp_verify_nonce($_POST['_wpnonce'], 'got chosen save options') !== false) {
        // Rebuild options array and update.
        $this -> options['gcid'] = isset($_POST['gcid']) ? $_POST['gcid'] : $this -> options['gcid'];
        $this -> options['feedkey'] = isset($_POST['feedkey']) ? $_POST['feedkey'] : $this -> options['feedkey'];
        $this -> options['webcurtain'] = isset($_POST['webcurtain']) ? 1 : 0;
        $this -> options['webcurtain_compat'] = isset($_POST['webcurtain_compat']) ? 1 : 0;
        $this -> options['pub_minifeed_default'] = isset($_POST['pub_minifeed_default']) ? 1 : 0;
        $this -> options['shareable'] = isset($_POST['shareable']) ? 1 : 0;
        $this -> options['commentable'] = isset($_POST['commentable']) ? 1 : 0;
        update_option('got_chosen_intg_settings', $this -> options);
        delete_transient('got_chosen_intg_gcid');
      }
    }
    // Include admin template.
    require_once $this -> includes_path . 'templates' . DIRECTORY_SEPARATOR . 'admin.php';
  }

  /**
   * Callback for add_meta_boxes_post action.
   *
   * Adds the meta box to posts that allows users to choose
   * if a post is published to the minifeed or not.
   *
   * @since 1.0
   *
   * @see __construct()
   */
  public function add_meta_boxes() {
    add_meta_box('gc_intg_minifeed_pub', 'Publish to GotChosen Minifeed', array(&$this, 'build_meta_box'), 'post', 'side');
  }

  /**
   * Callback for add_meta_box function.
   *
   * Builds the HTML for the meta box.
   *
   * @since 1.0
   *
   * @see add_meta_boxes()
   *
   * @param object $post The current post object being edited/added by a user.
   */
  public function build_meta_box($post) {
    wp_nonce_field('got chosen save meta', 'gc_meta_wpnonce');
    $publish = get_post_meta($post -> ID, 'gc_minifeed_publish', true);
    // If get_post_meta returns an empty string, the option was not found.
    if ($publish === '') {
      $publish = $this -> options['pub_minifeed_default'];
    }
    $checked = '';
    if ($publish) {
      $checked = 'checked="checked"';
    }
    echo '<label for="gc_minifeed_publish">Publish to GotChosen minifeed: </label>';
    echo '<input type="checkbox" name="gc_minifeed_publish" id="gc_minifeed_publish" ' . $checked . '/>';
  }

  public function add_meta_tag() {
      echo '<meta name="gotchosen:gcid" content="' . trim($this -> gcid) . '" />';
  }
}
