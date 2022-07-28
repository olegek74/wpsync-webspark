<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


final class Wpsync_Webspark_Settings
{

    private static $_instance = null;

    private $_has_select;

    public static function instance()
    {
        if (is_null(self::$_instance))
            self::$_instance = new self();
        return self::$_instance;
    } // End instance()

    public function __construct()
    {
    } // End __construct()

    public function validate_settings($input, $section)
    {
        if (is_array($input) && 0 < count($input)) {
            $fields = $this->get_settings_fields();

            foreach ($input as $k => $v) {
                if (!isset($fields[$k])) {
                    continue;
                }

                // Determine if a method is available for validating this field.
                $method = 'validate_field_' . $fields[$k]['type'];

                if (!method_exists($this, $method)) {
                    if (true === (bool)apply_filters('wpsync-webspark-validate-field-' . $fields[$k]['type'] . '_use_default', true)) {
                        $method = 'validate_field_text';
                    } else {
                        $method = '';
                    }
                }

                // If we have an internal method for validation, filter and apply it.
                if ('' != $method) {
                    add_filter('wpsync-webspark-validate-field-' . $fields[$k]['type'], array($this, $method));
                }

                $method_output = apply_filters('wpsync-webspark-validate-field-' . $fields[$k]['type'], $v, $fields[$k]);

                if (!is_wp_error($method_output)) {
                    $input[$k] = $method_output;
                }
            }
        }
        return $input;
    } // End validate_settings()

    public function validate_field_text($v)
    {
        return (string)wp_kses_post($v);
    } // End validate_field_text()

    public function validate_field_textarea($v)
    {
        // Allow iframe, object and embed tags in textarea fields.
        $allowed = wp_kses_allowed_html('post');
        $allowed['iframe'] = array(
            'src' => true,
            'width' => true,
            'height' => true,
            'id' => true,
            'class' => true,
            'name' => true
        );
        $allowed['object'] = array(
            'src' => true,
            'width' => true,
            'height' => true,
            'id' => true,
            'class' => true,
            'name' => true
        );
        $allowed['embed'] = array(
            'src' => true,
            'width' => true,
            'height' => true,
            'id' => true,
            'class' => true,
            'name' => true
        );

        return wp_kses($v, $allowed);
    } // End validate_field_textarea()


    /**
     * Validate the given data, assuming it is from a URL field.
     * @access public
     * @param string $v
     * @return string
     * @since  6.0.0
     */
    public function validate_field_url($v)
    {
        return trim(esc_url($v));
    } // End validate_field_url()

    /**
     * Render a field of a given type.
     * @access  public
     * @param array $args The field parameters.
     * @return  void
     * @since   1.0.0
     */
    public function render_field($args)
    {
        $html = '';
        if (!in_array($args['type'], $this->get_supported_fields())) return ''; // Supported field type sanity check.

        // Make sure we have some kind of default, if the key isn't set.
        if (!isset($args['default'])) {
            $args['default'] = '';
        }

        $method = 'render_field_' . $args['type'];

        if (!method_exists($this, $method)) {
            $method = 'render_field_text';
        }

        // Construct the key.
        $key = Wpsync_Webspark()->token . '-' . $args['section'] . '[' . $args['id'] . ']';
        $method_output = $this->$method($key, $args);

        if (!is_wp_error($method_output)) {
            $html .= $method_output;
        }

        // Output the description, if the current field allows it.
        if (isset($args['type']) && !in_array($args['type'], (array)apply_filters('wpsync-webspark-no-description-fields', array('checkbox')))) {
            if (isset($args['description'])) {
                $description = '<p class="description">' . wp_kses_post($args['description']) . '</p>' . "\n";
                if (in_array($args['type'], (array)apply_filters('wpsync-webspark-new-line-description-fields', array('textarea', 'select')))) {
                    $description = wpautop($description);
                }
                $html .= $description;
            }
        }

        echo $html;
    } // End render_field()

    /**
     * Retrieve the settings fields details
     * @access  public
     * @return  array        Settings fields.
     * @since   1.0.0
     */
    public function get_settings_sections()
    {
        $settings_sections = array();
        $settings_sections['standard-fields'] = __('Standard Fields', 'wpsync-webspark');
        return (array)apply_filters('wpsync-webspark-settings-sections', $settings_sections);
    }

    private function get_authors()
    {
        $args = array(
        'role__in'     => array('administrator','author','editor'),
        'orderby'      => 'display_name',
        'order'        => 'ASC',
        );
        $authors = get_users($args);
        $users = [];
        foreach($authors as $author){
            $users[] = ['id' => $author->data->ID, 'user_login' => $author->data->user_login];
        }
        return $users;
    }
	/**
	 * Retrieve the settings fields details
	 * @access  public
	 * @param  string $section field section.
	 * @since   1.0.0
	 * @return  array        Settings fields.
	 */
	public function get_settings_fields () {
		$settings_fields = array();

		$settings_fields['url'] = array(
            'name' => __( 'Api Url', 'wpsync-webspark' ),
            'type' => 'text',
            'default' => '',
            'section' => 'standard-fields',
            'description' => __( 'Api url link', 'wpsync-webspark' )
        );

        $settings_fields['last'] = array(
            'name' => __( 'Last update date', 'wpsync-webspark' ),
            'type' => 'text',
            'default' => '',
            'section' => 'standard-fields',
            'description' => __( 'Last update date', 'wpsync-webspark' )
        );

        $settings_fields['password'] = array(
            'name' => __( 'Password', 'wpsync-webspark' ),
            'type' => 'text',
            'default' => '',
            'section' => 'standard-fields',
            'description' => __( 'Password for update', 'wpsync-webspark' )
        );

		$settings_fields['period'] = array(
            'name' => __( 'Period', 'wpsync-webspark' ),
            'type' => 'select',
            'default' => '',
            'section' => 'standard-fields',
            'options' => array(
                            '1' => __( '1', 'wpsync-webspark' ),
                            '2' => __( '2', 'wpsync-webspark' ),
                            '3' => __( '3', 'wpsync-webspark' ),
                            '4' => __( '4', 'wpsync-webspark' ),
                            '5' => __( '5', 'wpsync-webspark' ),
                            '6' => __( '6', 'wpsync-webspark' ),
                            '8' => __( '8', 'wpsync-webspark' ),
                            '10' => __( '10', 'wpsync-webspark' ),
                            '12' => __( '12', 'wpsync-webspark' ),
                            '15' => __( '15', 'wpsync-webspark' ),
                            '18' => __( '18', 'wpsync-webspark' ),
                            '21' => __( '21', 'wpsync-webspark' ),
                            '24' => __( '24', 'wpsync-webspark' )
                        ),
            'description' => __( 'Product update period.', 'wpsync-webspark' )
    );
        $authors = $this->get_authors();
        $options = [];
        foreach($authors as $author){
            $options[$author['id']] = __($author['user_login'], 'wpsync-webspark');
        }

        $settings_fields['author'] = array(
            'name' => __( 'Author', 'wpsync-webspark' ),
            'type' => 'select',
            'default' => '',
            'section' => 'standard-fields',
            'options' => $options,
            'description' => __( 'Setting post author', 'wpsync-webspark' )
        );

		return (array)apply_filters( 'wpsync-webspark-settings-fields', $settings_fields );
	}

	protected function render_field_text ( $key, $args ) {
		$html = '<input id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" size="40" type="text" value="' . esc_attr( $this->get_value( $args['id'], $args['default'], $args['section'] ) ) . '" />' . "\n";
		return $html;
	}

	protected function render_field_select ( $key, $args ) {
		$this->_has_select = true;

		$html = '';
		if ( isset( $args['options'] ) && ( 0 < count( (array)$args['options'] ) ) ) {
			$html .= '<select id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">' . "\n";
				foreach ( $args['options'] as $k => $v ) {
					$html .= '<option value="' . esc_attr( $k ) . '"' . selected( esc_attr( $this->get_value( $args['id'], $args['default'], $args['section'] ) ), $k, false ) . '>' . esc_html( $v ) . '</option>' . "\n";
				}
			$html .= '</select>' . "\n";
		}
		return $html;
	}

	public function get_array_field_types () {
		return array();
	}

	protected function get_no_label_field_types () {
		return array( 'info' );
	}

	public function get_supported_fields () {
		return (array)apply_filters( 'wpsync-webspark-supported-fields', array( 'text', 'checkbox', 'radio', 'textarea', 'select', 'select_taxonomy' ) );
	}

	public function get_value ( $key, $default, $section ) {
		$values = get_option( 'wpsync-webspark-' . $section, array() );

		if ( is_array( $values ) && isset( $values[$key] ) ) {
			$response = $values[$key];
		} else {
			$response = $default;
		}

		return $response;
	}

	public function get_settings ( $section = '' ) {
		$response = false;

		$sections = array_keys( (array)$this->get_settings_sections() );

		if ( in_array( $section, $sections ) ) {
			$sections = array( $section );
		}

		if ( 0 < count( $sections ) ) {
			foreach ( $sections as $k => $v ) {
				$fields = $this->get_settings_fields();
				$values = get_option( 'wpsync-webspark-' . $v, array() );

				if ( is_array( $fields ) && 0 < count( $fields ) ) {
					foreach ( $fields as $i => $j ) {
						// If we have a value stored, use it.
						if ( isset( $values[$i] ) ) {
							$response[$i] = $values[$i];
						} else {
							// Otherwise, check for a default value. If we have one, use it. Otherwise, return an empty string.
							if ( isset( $fields[$i]['default'] ) ) {
								$response[$i] = $fields[$i]['default'];
							} else {
								$response[$i] = '';
							}
						}
					}
				}
			}
		}

		return $response;
	}
} // End Class
