<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

final class Wpsync_Webspark_Admin {

	private static $_instance = null;

	private $_hook;

	public function __construct () {
		// Register the settings with WordPress.
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Register the settings screen within WordPress.
		add_action( 'admin_menu', array( $this, 'register_settings_screen' ) );
	} // End __construct()

	public static function instance () {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	} // End instance()

	public function register_settings_screen () {
		$this->_hook = add_submenu_page( 'options-general.php', __( 'Wpsync Webspark Settings', 'wpsync-webspark' ), __( 'Wpsync Webspark', 'wpsync-webspark' ), 'manage_options', 'wpsync-webspark', array( $this, 'settings_screen' ) );
	} // End register_settings_screen()

	public function settings_screen () {
		global $title;
		$sections = Wpsync_Webspark()->settings->get_settings_sections();
		$tab = $this->_get_current_tab( $sections );
		?>
		<div class="wrap wpsync-webspark-wrap">
			<?php
				echo $this->get_admin_header_html( $sections, $title );
			?>
			<form action="options.php" method="post">
				<?php
					settings_fields( 'wpsync-webspark-settings-' . $tab );
					do_settings_sections( 'wpsync-webspark-' . $tab );
					submit_button( __( 'Save Changes', 'wpsync-webspark' ) );
				?>
			</form>
		</div><!--/.wrap-->
		<?php
	} // End settings_screen()

	public function register_settings () {
		$sections = Wpsync_Webspark()->settings->get_settings_sections();
		if ( 0 < count( $sections ) ) {
			foreach ( $sections as $k => $v ) {
				register_setting( 'wpsync-webspark-settings-' . sanitize_title_with_dashes( $k ), 'wpsync-webspark-' . $k, array( $this, 'validate_settings' ) );
				add_settings_section( sanitize_title_with_dashes( $k ), $v, array( $this, 'render_settings' ), 'wpsync-webspark-' . $k, $k, $k );
			}
		}
	} // End register_settings()

	public function render_settings ( $args ) {
		$token = $args['id'];
		$fields = Wpsync_Webspark()->settings->get_settings_fields();

		if ( 0 < count( $fields ) ) {
			foreach ( $fields as $k => $v ) {
				$args 		= $v;
				$args['id'] = $k;

				add_settings_field( $k, $v['name'], array( Wpsync_Webspark()->settings, 'render_field' ), 'wpsync-webspark-' . $token , $v['section'], $args );
			}
		}
	} // End render_settings()

	public function validate_settings ( $input ) {
		$sections = Wpsync_Webspark()->settings->get_settings_sections();
		$tab = $this->_get_current_tab( $sections );
		return Wpsync_Webspark()->settings->validate_settings( $input, $tab );
	} // End validate_settings()

	public function get_admin_header_html ( $sections, $title ) {
		$defaults = array(
							'tag' => 'h2',
							'atts' => array( 'class' => 'wpsync-webspark-wrapper' ),
							'content' => $title
						);

		$args = $this->_get_admin_header_data( $sections, $title );

		$args = wp_parse_args( $args, $defaults );

		$atts = '';
		if ( 0 < count ( $args['atts'] ) ) {
			foreach ( $args['atts'] as $k => $v ) {
				$atts .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
			}
		}

		$response = '<' . esc_attr( $args['tag'] ) . $atts . '>' . $args['content'] . '</' . esc_attr( $args['tag'] ) . '>' . "\n";

		return $response;
	} // End get_admin_header_html()

	private function _get_current_tab ( $sections = array() ) {

        if ( is_array( $sections ) && ! empty( $sections ) ) {
            list( $first_section ) = array_keys( $sections );
            $response = $first_section;
        } else {
            $response = '';
        }
		return $response;
	} // End _get_current_tab()

	private function _get_admin_header_data ( $sections, $title ) {
		$response = array( 'tag' => 'h2', 'atts' => array( 'class' => 'wpsync-webspark-wrapper' ), 'content' => $title );

		if ( is_array( $sections ) && 1 < count( $sections ) ) {
			$response['content'] = '';
			$response['atts']['class'] = 'nav-tab-wrapper';

			$tab = $this->_get_current_tab( $sections );

			foreach ( $sections as $key => $value ) {
				$class = 'nav-tab';
				if ( $tab == $key ) {
					$class .= ' nav-tab-active';
				}

				$response['content'] .= '<a href="' . admin_url( 'options-general.php?page=wpsync-webspark&tab=' . sanitize_title_with_dashes( $key ) ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $value ) . '</a>';
			}
		}

		return (array)apply_filters( 'wpsync-webspark-get-admin-header-data', $response );
	} // End _get_admin_header_data()
} // End Class
