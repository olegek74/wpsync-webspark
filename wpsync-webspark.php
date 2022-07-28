<?php
/**
 * Plugin Name: Wpsync Webspark
 * Plugin URI: http://domain.com/wpsync-webspark/
 * Description: Hey there! I'm your new wpsync webspark.
 * Version: 1.0.0
 * Author: Matty
 * Author URI: http://domain.com/
 * Requires at least: 4.0.0
 * Tested up to: 4.0.0
 *
 * Text Domain: wpsync-webspark
 * Domain Path: /languages/
 *
 * @package Wpsync_Webspark
 * @category Core
 * @author Matty
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function Wpsync_Webspark() {
	return Wpsync_Webspark::instance();
}

add_action( 'plugins_loaded', 'Wpsync_Webspark' );

final class Wpsync_Webspark {

	private static $_instance = null;

	public $token;

	public $version;

	public $plugin_url;

	public $plugin_path;

	public $admin;

	public $settings;
	// Admin - End

	public $post_types = array();

    public $upload_dir;

    public function __construct () {
		$this->token 			= 'wpsync-webspark';
		$this->plugin_url 		= plugin_dir_url( __FILE__ );
		$this->plugin_path 		= plugin_dir_path( __FILE__ );
		$this->version 			= '1.0.0';
        $this->upload_dir = wp_upload_dir();
		// Admin - Start
		require_once( 'classes/class-wpsync-webspark-settings.php' );
			$this->settings = Wpsync_Webspark_Settings::instance();

		if ( is_admin() ) {
			require_once( 'classes/class-wpsync-webspark-admin.php' );
			$this->admin = Wpsync_Webspark_Admin::instance();
		}
		// Admin - End

		// Post Types - Start
        require_once( 'classes/class-wpsync-webspark-post-type.php' );

        // Register an example post type. To register other post types, duplicate this line.
        $this->post_types['thing'] = new Wpsync_Webspark_Post_Type( 'thing', __( 'Thing', 'wpsync-webspark' ), __( 'Things', 'wpsync-webspark' ), array( 'menu_icon' => 'dashicons-carrot' ) );
        // Post Types - End
        register_activation_hook( __FILE__, array( $this, 'install' ) );

        add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
        add_action( 'init', array( $this, 'start_export' ) );
	} // End __construct()

	public static function instance () {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	} // End instance()

	public function load_plugin_textdomain() {
        load_plugin_textdomain( 'wpsync-webspark', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	} // End load_plugin_textdomain()

	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	} // End __clone()

	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	} // End __wakeup()

	public function install () {
		$this->_log_version_number();
	} // End install()

	private function _log_version_number () {
		// Log the version number.
		update_option( $this->token . '-version', $this->version );
	}

    private function _product_name($product_name){
        $post_name = mb_strtolower($product_name);
        $post_name = preg_replace('/[^a-z0-9]/', ' ', $post_name);

        while(strstr($post_name, '  ')){
            $post_name = str_replace('  ', ' ', $post_name);
        }
        $post_name = str_replace(' ', '-', $post_name);
        return $post_name;
    }

    private function _price($product_price){
        return preg_replace('/[^0-9\.]/', '', $product_price);
    }

    private function _image($image_url, $post_id){

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $image_url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $image_data = curl_exec($ch);
        curl_close($ch);

        preg_match('/(\.([jpgnJPGN]{3,3}))/', $image_url, $ext);
        $parts = preg_split('/(\.([jpgnJPGN]{3,3}))/', $image_url);

        $filename = basename($parts[0].$ext[0]);

        if ( wp_mkdir_p( $this->upload_dir['path'] ) ) {
            $file = $this->upload_dir['path'] . '/' . $filename;
        }
        else {
            $file = $this->upload_dir['basedir'] . '/' . $filename;
        }

        file_put_contents($file, $image_data);

        $wp_filetype = wp_check_filetype($filename, null );

        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit',
            'post_parent' => $post_id
        );

        $attach_id = wp_insert_attachment($attachment, $file);
        require_once(ABSPATH.'wp-admin/includes/image.php' );
        $attach_data = wp_generate_attachment_metadata($attach_id, $file);

        wp_update_attachment_metadata( $attach_id, $attach_data );
        return (int) $attach_id;
    }

    public function start_export(){
        global $wpdb;
        if(!is_admin() && strpos($_SERVER['REQUEST_URI'], '/export-json/passwod/') === 0){
            $option = get_option( 'wpsync-webspark-standard-fields', array());

            if($_SERVER['REQUEST_URI'] == '/export-json/passwod/'.$option['password']) {
                $post_author = $option['author'];
                $posts_ids = [];
                $attach_ids = [];

                if ((time() - $option['period'] * 3600) >= strtotime($option['last'])) {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $option['url']);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-API-Key: 89b23a40'));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $json_data = curl_exec($ch);
                    curl_close($ch);
                    if ($json_data) {
                        $data = json_decode($json_data);
                        if (is_array($data) && !empty($data)) {

                            foreach ($data as $product) {

                                $db_product = $wpdb->get_row('SELECT p.* FROM `wp_postmeta` m JOIN `wp_posts` p ON (p.`ID` = m.`post_id`) WHERE p.`post_type` = "product" AND m.`meta_key` = "_sku" AND m.`meta_value` = "' . $product->sku . '"');

                                if (!$db_product) {
                                    $post_name = $this->_product_name($product->name);
                                    $price = $this->_price($product->price);
                                    $post_data = [
                                        'post_title' => $product->name,
                                        'post_name' => $post_name,
                                        'post_content' => $product->description,
                                        'post_type' => 'product',
                                        'post_author' => $post_author,
                                        'post_status' => 'publish',
                                        'meta_input' => [
                                            '_price' => $price,
                                            '_sku' => $product->sku,
                                            '_stock' => $product->in_stock,
                                            '_stock_status' => 'instock'
                                        ]
                                    ];
                                    $post_id = wp_insert_post(wp_slash($post_data));
                                    if ($post_id) {
                                        $posts_ids[] = (int)$post_id;
                                        $attach_ids[] = $this->_image($product->picture, $post_id);
                                    }
                                } else {
                                    $post_name = $this->_product_name($product->name);
                                    $price = $this->_price($product->price);
                                    $post_data = [
                                        'ID' => $db_product->ID,
                                        'post_title' => $product->name,
                                        'post_name' => $post_name,
                                        'post_content' => $product->description,
                                        'post_type' => 'product',
                                        'post_author' => $post_author,
                                        'post_status' => 'publish',
                                        'meta_input' => [
                                            '_price' => $price,
                                            '_sku' => $product->sku,
                                            '_stock' => $product->in_stock,
                                            '_stock_status' => 'instock'
                                        ]
                                    ];
                                    $post_id = wp_update_post(wp_slash($post_data));
                                    $posts_ids[] = (int)$post_id;
                                    $attach_ids[] = $this->_image($product->picture, $post_id);
                                }
                            }

                            if (!empty($posts_ids)) {
                                $_posts = $wpdb->get_results('SELECT `ID` FROM `wp_posts` WHERE `post_type` = "product" AND `ID` NOT IN (' . implode(',', $posts_ids) . ')');
                                foreach ($_posts as $post) {
                                    wp_delete_post($post->ID);
                                }
                            }
                            if (!empty($attach_ids)) {
                                $_attaches = $wpdb->get_results('SELECT p1.`ID` FROM `wp_posts` p1 JOIN `wp_posts` p2 ON (p2.`ID` = p1.`post_parent`) WHERE p1.`post_type` = "attachment" AND p2.`post_type` = "product" AND p1.`ID` NOT IN (' . implode(',', $attach_ids) . ')');
                                foreach ($_attaches as $_attach) {
                                    wp_delete_attachment($_attach->ID);
                                }
                            }
                            $option['last'] = date('Y-m-d H:i');
                            update_option('wpsync-webspark-standard-fields', $option);
                            die('end');
                        }
                        else die('empty data');
                    }
                    else die('error data');
                }
                else die('false start');
            }
            else die('error password');
        }
    }

} // End Class
