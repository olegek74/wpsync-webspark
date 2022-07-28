<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.


class Wpsync_Webspark_Post_Type {

	public $post_type;

	public $singular;

	public $plural;

	public $args;

	public $taxonomies;

	public function __construct( $post_type = 'product', $singular = '', $plural = '', $args = array(), $taxonomies = array() ) {
		$this->post_type = $post_type;
		$this->singular = $singular;
		$this->plural = $plural;
		$this->args = $args;
		$this->taxonomies = $taxonomies;

		add_action( 'init', array( $this, 'register_post_type' ) );

		if ( is_admin() ) {
			global $pagenow;

			add_action( 'admin_menu', array( $this, 'meta_box_setup' ), 20 );
			add_action( 'save_post', array( $this, 'meta_box_save' ) );
			add_filter( 'enter_title_here', array( $this, 'enter_title_here' ) );
			add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );

			if ( $pagenow == 'edit.php' && isset( $_GET['post_type'] ) && esc_attr( $_GET['post_type'] ) == $this->post_type ) {
				add_filter( 'manage_edit-' . $this->post_type . '_columns', array( $this, 'register_custom_column_headings' ), 10, 1 );
				add_action( 'manage_posts_custom_column', array( $this, 'register_custom_columns' ), 10, 2 );
			}
		}
	} // End __construct()

	public function register_post_type () {
		$labels = array(
			'name' => sprintf( _x( '%s', 'post type general name', 'wpsync-webspark' ), $this->plural ),
			'singular_name' => sprintf( _x( '%s', 'post type singular name', 'wpsync-webspark' ), $this->singular ),
			'add_new' => _x( 'Add New', $this->post_type, 'wpsync-webspark' ),
			'add_new_item' => sprintf( __( 'Add New %s', 'wpsync-webspark' ), $this->singular ),
			'edit_item' => sprintf( __( 'Edit %s', 'wpsync-webspark' ), $this->singular ),
			'new_item' => sprintf( __( 'New %s', 'wpsync-webspark' ), $this->singular ),
			'all_items' => sprintf( __( 'All %s', 'wpsync-webspark' ), $this->plural ),
			'view_item' => sprintf( __( 'View %s', 'wpsync-webspark' ), $this->singular ),
			'search_items' => sprintf( __( 'Search %a', 'wpsync-webspark' ), $this->plural ),
			'not_found' => sprintf( __( 'No %s Found', 'wpsync-webspark' ), $this->plural ),
			'not_found_in_trash' => sprintf( __( 'No %s Found In Trash', 'wpsync-webspark' ), $this->plural ),
			'parent_item_colon' => '',
			'menu_name' => $this->plural,
		);

		$single_slug = apply_filters( 'wpsync-webspark_single_slug', _x( sanitize_title_with_dashes( $this->singular ), 'single post url slug', 'wpsync-webspark' ) );
		$archive_slug = apply_filters( 'wpsync-webspark_archive_slug', _x( sanitize_title_with_dashes( $this->plural ), 'post archive url slug', 'wpsync-webspark' ) );

		$defaults = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'query_var' => true,
			'rewrite' => array( 'slug' => $single_slug ),
			'capability_type' => 'post',
			'has_archive' => $archive_slug,
			'hierarchical' => false,
			'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
			'menu_position' => 5,
			'menu_icon' => 'dashicons-smiley',
		);

		$args = wp_parse_args( $this->args, $defaults );

		register_post_type( $this->post_type, $args );
	} // End register_post_type()

	public function register_custom_columns ( $column_name, $id ) {
		global $post;
	} // End register_custom_columns()

	public function register_custom_column_headings ( $defaults ) {
		$new_columns = array( 'image' => __( 'Image', 'wpsync-webspark' ) );

		$last_item = array();

		if ( isset( $defaults['date'] ) ) { unset( $defaults['date'] ); }

		if ( count( $defaults ) > 2 ) {
			$last_item = array_slice( $defaults, -1 );

			array_pop( $defaults );
		}
		$defaults = array_merge( $defaults, $new_columns );

		if ( is_array( $last_item ) && 0 < count( $last_item ) ) {
			foreach ( $last_item as $k => $v ) {
				$defaults[$k] = $v;
				break;
			}
		}

		return $defaults;
	} // End register_custom_column_headings()

	public function updated_messages ( $messages ) {
		global $post, $post_ID;

		$messages[$this->post_type] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf( __( '%3$s updated. %sView %4$s%s', 'wpsync-webspark' ), '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>', $this->singular, strtolower( $this->singular ) ),
			2 => __( 'Custom field updated.', 'wpsync-webspark' ),
			3 => __( 'Custom field deleted.', 'wpsync-webspark' ),
			4 => sprintf( __( '%s updated.', 'wpsync-webspark' ), $this->singular ),
			/* translators: %s: date and time of the revision */
			5 => isset($_GET['revision']) ? sprintf( __( '%s restored to revision from %s', 'wpsync-webspark' ), $this->singular, wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __( '%1$s published. %3$sView %2$s%4$s', 'wpsync-webspark' ), $this->singular, strtolower( $this->singular ), '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
			7 => sprintf( __( '%s saved.', 'wpsync-webspark' ), $this->singular ),
			8 => sprintf( __( '%s submitted. %sPreview %s%s', 'wpsync-webspark' ), $this->singular, strtolower( $this->singular ), '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', '</a>' ),
			9 => sprintf( __( '%s scheduled for: %1$s. %2$sPreview %s%3$s', 'wpsync-webspark' ), $this->singular, strtolower( $this->singular ),
			// translators: Publish box date format, see http://php.net/date
			'<strong>' . date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) . '</strong>', '<a target="_blank" href="' . esc_url( get_permalink($post_ID) ) . '">', '</a>' ),
			10 => sprintf( __( '%s draft updated. %sPreview %s%s', 'wpsync-webspark' ), $this->singular, strtolower( $this->singular ), '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', '</a>' ),
		);

		return $messages;
	} // End updated_messages()

	public function meta_box_setup () {
		add_meta_box( $this->post_type . '-data', __( 'Thing Details', 'wpsync-webspark' ), array( $this, 'meta_box_content' ), $this->post_type, 'normal', 'high' );
	} // End meta_box_setup()

	public function meta_box_content () {
		global $post_id;
		$fields = get_post_custom( $post_id );
		$field_data = $this->get_custom_fields_settings();

		$html = '';

		$html .= '<input type="hidden" name="wpsync-webspark_' . $this->post_type . '_noonce" id="wpsync-webspark_' . $this->post_type . '_noonce" value="' . wp_create_nonce( plugin_basename( dirname( Wpsync_Webspark()->plugin_path ) ) ) . '" />';

		if ( 0 < count( $field_data ) ) {
			$html .= '<table class="form-table">' . "\n";
			$html .= '<tbody>' . "\n";

			foreach ( $field_data as $k => $v ) {
				$data = $v['default'];
				if ( isset( $fields['_' . $k] ) && isset( $fields['_' . $k][0] ) ) {
					$data = $fields['_' . $k][0];
				}

				$html .= '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . $v['name'] . '</label></th><td><input name="' . esc_attr( $k ) . '" type="text" id="' . esc_attr( $k ) . '" class="regular-text" value="' . esc_attr( $data ) . '" />' . "\n";
				$html .= '<p class="description">' . $v['description'] . '</p>' . "\n";
				$html .= '</td></tr>' . "\n";
			}

			$html .= '</tbody>' . "\n";
			$html .= '</table>' . "\n";
		}

		echo $html;
	} // End meta_box_content()

	public function meta_box_save ( $post_id ) {
		global $post, $messages;

		// Verify
		if ( get_post_type() != $this->post_type ) {
			return $post_id;
		}

		if ( ! isset( $_POST['wpsync-webspark_' . $this->post_type . '_noonce'] ) || ! wp_verify_nonce( $_POST['wpsync-webspark_' . $this->post_type . '_noonce'], plugin_basename( dirname( Wpsync_Webspark()->plugin_path ) ) ) ) {
			return $post_id;
		}

		if ( isset( $_POST['post_type'] ) && 'page' == esc_attr( $_POST['post_type'] ) ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return $post_id;
			}
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}
		}

		$field_data = $this->get_custom_fields_settings();
		$fields = array_keys( $field_data );

		foreach ( $fields as $f ) {

			${$f} = strip_tags(trim($_POST[$f]));

			// Escape the URLs.
			if ( 'url' == $field_data[$f]['type'] ) {
				${$f} = esc_url( ${$f} );
			}

			if ( get_post_meta( $post_id, '_' . $f ) == '' ) {
				add_post_meta( $post_id, '_' . $f, ${$f}, true );
			} elseif( ${$f} != get_post_meta( $post_id, '_' . $f, true ) ) {
				update_post_meta( $post_id, '_' . $f, ${$f} );
			} elseif ( ${$f} == '' ) {
				delete_post_meta( $post_id, '_' . $f, get_post_meta( $post_id, '_' . $f, true ) );
			}
		}
	} // End meta_box_save()

	public function enter_title_here ( $title ) {
		if ( get_post_type() == $this->post_type ) {
			$title = __( 'Enter the thing title here', 'wpsync-webspark' );
		}
		return $title;
	} // End enter_title_here()

	public function get_custom_fields_settings () {
		$fields = array();

		$fields['url'] = array(
		    'name' => __( 'URL', 'wpsync-webspark' ),
		    'description' => __( 'Enter a URL that applies to this thing (for example: http://domain.com/).', 'wpsync-webspark' ),
		    'type' => 'url',
		    'default' => '',
		    'section' => 'info'
		);

		return apply_filters( 'wpsync-webspark_custom_fields_settings', $fields );
	} // End get_custom_fields_settings()

	public function activation () {
		$this->flush_rewrite_rules();
	} // End activation()

	private function flush_rewrite_rules () {
		$this->register_post_type();
		flush_rewrite_rules();
	} // End flush_rewrite_rules()

} // End Class
