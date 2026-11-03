<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Array search key & value
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! function_exists( 'gp_array_search' ) ) {
  function gp_array_search( $array, $key, $value ) {

    $results = array();

    if ( is_array( $array ) ) {
      if ( isset( $array[$key] ) && $array[$key] == $value ) {
        $results[] = $array;
      }

      foreach ( $array as $sub_array ) {
        $results = array_merge( $results, gp_array_search( $sub_array, $key, $value ) );
      }

    }

    return $results;

  }
}

/**
 *
 * Between Microtime
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! function_exists( 'gp_timeout' ) ) {
  function gp_timeout( $timenow, $starttime, $timeout = 30 ) {
    return ( ( $timenow - $starttime ) < $timeout ) ? true : false;
  }
}

/**
 *
 * Check for wp editor api
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! function_exists( 'gp_wp_editor_api' ) ) {
  function gp_wp_editor_api() {
    global $wp_version;
    return version_compare( $wp_version, '4.8', '>=' );
  }
}

/**
 *
 * Get a Guava Pattern option (stored as one array in the GP_OPTION row)
 *
 * Kept compatible with every earlier Guava Pattern release, so theme code calling
 * gp_get_option( 'key' ) keeps working and the saved data does not need migrating.
 *
 * @since 1.0.0
 * @version 2.0.0
 *
 */
if ( ! function_exists( 'gp_get_option' ) ) {
  function gp_get_option( $option_name = '', $default = '' ) {

    $options = apply_filters( 'gp_get_option', get_option( GP_OPTION ), $option_name, $default );

    if ( ! empty( $option_name ) && is_array( $options ) && ! empty( $options[$option_name] ) ) {
      return $options[$option_name];
    }

    return ( ! empty( $default ) ) ? $default : null;

  }
}

/**
 *
 * Set a Guava Pattern option
 *
 * @since 1.0.0
 * @version 2.0.0
 *
 */
if ( ! function_exists( 'gp_set_option' ) ) {
  function gp_set_option( $option_name = '', $new_value = '' ) {

    $options = apply_filters( 'gp_set_option', get_option( GP_OPTION ), $option_name, $new_value );
    $options = ( is_array( $options ) ) ? $options : array();

    if ( ! empty( $option_name ) ) {
      $options[$option_name] = $new_value;
      update_option( GP_OPTION, $options );
    }

  }
}

/**
 *
 * Get a Guava Pattern post meta value (stored as one array in the GP_META row)
 *
 * @since 1.0.0
 * @version 2.0.0
 *
 */
if ( ! function_exists( 'gp_get_meta' ) ) {
  function gp_get_meta( $id, $option_name = '', $default = '' ) {

    $options = get_post_meta( $id, GP_META, true );

    if ( ! empty( $option_name ) && is_array( $options ) && ! empty( $options[$option_name] ) ) {
      return $options[$option_name];
    }

    return ( ! empty( $default ) ) ? $default : null;

  }
}

/**
 *
 * Map the field type names older Guava Pattern releases used to the current field types
 *
 * @since 2.0.0
 * @version 2.0.0
 *
 */
if ( ! function_exists( 'gp_field_type' ) ) {
  function gp_field_type( $type = 'text' ) {

    $map = apply_filters( 'gp_legacy_field_types', array(
      'color_picker' => 'color',
      'wysiwyg'      => 'wp_editor',
      'image'        => 'upload',
    ) );

    $type = ( isset( $map[$type] ) ) ? $map[$type] : $type;

    return ( class_exists( 'GP_Field_'. $type ) ) ? $type : 'text';

  }
}

/**
 *
 * Nonce protected URL that clears the dynamically collected field registry
 *
 * $what: 'options' (admin options panel) | 'pages' (page metabox)
 *
 * @since 2.0.0
 * @version 2.0.0
 *
 */
if ( ! function_exists( 'gp_reset_url' ) ) {
  function gp_reset_url( $what = 'options' ) {
    $arg = ( $what === 'pages' ) ? 'reset_gp_pages' : 'reset_gp';
    return wp_nonce_url( add_query_arg( $arg, '1', admin_url( 'admin.php' ) ), 'gp_reset' );
  }
}

if ( ! function_exists( 'gp_can_reset' ) ) {
  function gp_can_reset( $arg ) {
    return ( is_admin() && isset( $_GET[$arg] ) && current_user_can( 'manage_options' ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'gp_reset' ) );
  }
}
