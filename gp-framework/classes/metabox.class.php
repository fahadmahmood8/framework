<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Metabox Class
 *
 * Guava Pattern native metabox framework, built on the core WordPress APIs
 * (add_meta_box, register_post_meta, nonces, capability checks).
 *
 * Storage (data_type):
 *   'serialize'   (default) one post meta row named after the metabox id holding an array
 *                 of field values, e.g. get_post_meta( $id, 'gp_meta', true ).
 *                 This is the storage format Guava Pattern has always used.
 *   'unserialize' one post meta row per field id.
 *
 * @since 2.0.0
 * @version 2.0.0
 *
 */
if ( ! class_exists( 'GP_Metabox' ) ) {
  class GP_Metabox extends GP_Abstract {

    // constans
    public $unique       = '';
    public $abstract     = 'metabox';
    public $sections     = array();
    public $pre_tabs     = array();
    public $pre_fields   = array();
    public $pre_sections = array();
    public $post_type_obj = null;
    public $args         = array(
      'title'              => '',
      'post_type'          => 'post',
      'data_type'          => 'serialize',
      'context'            => 'advanced',
      'priority'           => 'default',
      'capability'         => 'edit_post',
      'show_restore'       => false,
      'theme'              => 'light',
      'nav'                => 'normal',
      'class'              => '',
      'defaults'           => array(),
      'output_css'         => false,
      'enqueue_webfont'    => false,
      'async_webfont'      => false,
      'page_templates'     => '',
      'post_formats'       => '',
      'rest'               => true, // expose values in the REST API / block editor
    );

    // run metabox construct
    public function __construct( $key, $params = array() ) {

      $this->unique   = $key;
      $this->args     = apply_filters( "gp_{$this->unique}_args", wp_parse_args( $params['args'], $this->args ), $this );
      $this->sections = apply_filters( "gp_{$this->unique}_sections", $params['sections'], $this );

      $this->pre_tabs     = $this->pre_tabs( $this->sections );
      $this->pre_fields   = $this->pre_fields( $this->sections );
      $this->pre_sections = $this->pre_sections( $this->sections );

      add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
      add_action( 'save_post', array( $this, 'save_meta_box' ), 10, 2 );
      add_action( 'init', array( $this, 'register_meta' ), 20 );

      if ( ! empty( $this->args['page_templates'] ) || ! empty( $this->args['post_formats'] ) ) {
        add_filter( 'postbox_classes_' . $this->first_post_type() . '_' . $this->unique, array( $this, 'add_postbox_classes' ) );
      }

      // wp enqeueu for typography and output css
      parent::__construct();

    }

    // instance
    public static function instance( $key, $params = array() ) {
      return new self( $key, $params );
    }

    // first post type helper
    public function first_post_type() {
      $post_types = (array) $this->args['post_type'];
      return reset( $post_types );
    }

    // expose the stored values to the REST API so the block editor can read them
    public function register_meta() {

      if ( empty( $this->args['rest'] ) ) {
        return;
      }

      foreach ( (array) $this->args['post_type'] as $post_type ) {

        if ( $this->args['data_type'] === 'serialize' ) {

          register_post_meta( $post_type, $this->unique, array(
            'single'            => true,
            'type'              => 'object',
            'show_in_rest'      => array(
              'schema' => array(
                'type'                 => 'object',
                'additionalProperties' => true,
              ),
            ),
            'auth_callback'     => array( $this, 'auth_callback' ),
          ) );

        } else {

          foreach ( $this->pre_fields as $field ) {
            if ( ! empty( $field['id'] ) ) {
              register_post_meta( $post_type, $field['id'], array(
                'single'        => true,
                'type'          => 'string',
                'show_in_rest'  => true,
                'auth_callback' => array( $this, 'auth_callback' ),
              ) );
            }
          }

        }

      }

    }

    // meta auth
    public function auth_callback( $allowed, $meta_key, $post_id ) {
      return current_user_can( $this->args['capability'], $post_id );
    }

    // metabox classes for page template / post format visibility
    public function add_postbox_classes( $classes ) {

      if ( ! empty( $this->args['page_templates'] ) ) {
        $classes[] = 'gp-page-templates';
        foreach ( (array) $this->args['page_templates'] as $template ) {
          $classes[] = 'gp-page-' . sanitize_title( str_replace( '.php', '', $template ) );
        }
      }

      if ( ! empty( $this->args['post_formats'] ) ) {
        $classes[] = 'gp-post-formats';
        foreach ( (array) $this->args['post_formats'] as $format ) {
          $classes[] = 'gp-post-format-' . sanitize_html_class( $format );
        }
      }

      return $classes;

    }

    // add metabox
    public function add_meta_box( $post_type ) {

      if ( ! in_array( $post_type, (array) $this->args['post_type'], true ) ) {
        return;
      }

      add_meta_box(
        $this->unique,
        wp_kses_post( $this->args['title'] ),
        array( $this, 'add_meta_box_content' ),
        $post_type,
        $this->args['context'],
        $this->args['priority'],
        array( '__block_editor_compatible_meta_box' => true )
      );

    }

    // get default value
    public function get_default( $field ) {

      $default = ( isset( $field['default'] ) ) ? $field['default'] : '';
      $default = ( isset( $this->args['defaults'][$field['id']] ) ) ? $this->args['defaults'][$field['id']] : $default;

      return $default;

    }

    // get meta value (whole array when serialized, or a single field when asked)
    public function get_meta_value( $field = array(), $post_id = 0 ) {

      $post_id = ( $post_id ) ? $post_id : get_the_ID();

      if ( $this->args['data_type'] === 'serialize' ) {

        $meta  = get_post_meta( $post_id, $this->unique, true );
        $meta  = ( is_array( $meta ) ) ? $meta : array();

        if ( empty( $field['id'] ) ) {
          return $meta;
        }

        return ( isset( $meta[$field['id']] ) ) ? $meta[$field['id']] : null;

      }

      if ( empty( $field['id'] ) ) {
        return null;
      }

      return metadata_exists( 'post', $post_id, $field['id'] ) ? get_post_meta( $post_id, $field['id'], true ) : null;

    }

    // metabox html output
    public function add_meta_box_content( $post ) {

      $has_nav       = ( count( $this->pre_tabs ) > 1 ) ? true : false;
      $show_all      = ( ! $has_nav ) ? ' gp-show-all' : '';
      $theme         = ( $this->args['theme'] ) ? ' gp-theme-' . $this->args['theme'] : '';
      $class         = ( $this->args['class'] ) ? ' ' . $this->args['class'] : '';
      $nav_type      = ( $this->args['nav'] === 'inline' ) ? 'inline' : 'normal';

      wp_nonce_field( 'gp_metabox_nonce', 'gp_metabox_nonce' . $this->unique );

      do_action( 'gp_metabox_before' );

      echo '<div class="gp gp-metabox' . esc_attr( $theme . $class ) . '" data-unique="' . esc_attr( $this->unique ) . '">';

        echo '<div class="gp-wrapper' . esc_attr( $show_all ) . '">';

          if ( $has_nav ) {

            echo '<div class="gp-nav gp-nav-' . esc_attr( $nav_type ) . ' gp-nav-metabox">';

              echo '<ul>';

              foreach ( $this->pre_tabs as $tab ) {

                $tab_icon = ( ! empty( $tab['icon'] ) ) ? '<i class="gp-tab-icon ' . esc_attr( $tab['icon'] ) . '"></i>' : '';

                if ( ! empty( $tab['subs'] ) ) {

                  echo '<li class="gp-tab-item">';
                  echo '<a href="#" class="gp-arrow">' . $tab_icon . wp_kses_post( $tab['title'] ) . '</a>';
                  echo '<ul>';

                  foreach ( $tab['subs'] as $sub ) {
                    $sub_icon = ( ! empty( $sub['icon'] ) ) ? '<i class="gp-tab-icon ' . esc_attr( $sub['icon'] ) . '"></i>' : '';
                    echo '<li><a href="#">' . $sub_icon . wp_kses_post( $sub['title'] ) . '</a></li>';
                  }

                  echo '</ul>';
                  echo '</li>';

                } else {

                  echo '<li><a href="#">' . $tab_icon . wp_kses_post( $tab['title'] ) . '</a></li>';

                }

              }

              echo '</ul>';

            echo '</div>';

          }

          echo '<div class="gp-content">';

            echo '<div class="gp-sections">';

            foreach ( $this->pre_sections as $section ) {

              $section_onload = ( ! $has_nav ) ? ' gp-onload' : '';
              $section_class  = ( ! empty( $section['class'] ) ) ? ' ' . $section['class'] : '';
              $section_title  = ( ! empty( $section['title'] ) ) ? $section['title'] : '';
              $section_icon   = ( ! empty( $section['icon'] ) ) ? '<i class="gp-section-icon ' . esc_attr( $section['icon'] ) . '"></i>' : '';
              // Guava Pattern: every section also carries a gp_<title> class for theme level styling.
              $section_slug   = ( ! empty( $section_title ) ) ? ' gp_' . sanitize_key( str_replace( ' ', '_', strtolower( wp_strip_all_tags( $section_title ) ) ) ) : '';

              echo '<div class="gp-section hidden' . esc_attr( $section_onload . $section_class . $section_slug ) . '">';
              echo ( $has_nav ) ? '<div class="gp-section-title"><h3>' . $section_icon . wp_kses_post( $section_title ) . '</h3></div>' : '';
              echo ( ! empty( $section['description'] ) ) ? '<div class="gp-field gp-section-description">' . wp_kses_post( $section['description'] ) . '</div>' : '';

              if ( ! empty( $section['fields'] ) ) {

                foreach ( $section['fields'] as $field ) {

                  $value = null;

                  if ( ! empty( $field['id'] ) ) {
                    $field['default'] = $this->get_default( $field );
                    $value            = $this->get_meta_value( $field, $post->ID );
                  }

                  GP::field( $field, $value, $this->unique, 'metabox' );

                }

              } else {

                echo '<div class="gp-no-option">' . esc_html__( 'No data available.', 'gp-framework' ) . '</div>';

              }

              echo '</div>';

            }

            echo '</div>';

            echo '<div class="clear"></div>';

          echo '</div>';

          echo ( $has_nav && $nav_type === 'normal' ) ? '<div class="gp-nav-background"></div>' : '';

          echo '<div class="clear"></div>';

        echo '</div>';

      echo '</div>';

      do_action( 'gp_metabox_after' );

    }

    // sanitize a single field value the same way the options panel does
    public function sanitize_value( $field, $value ) {

      if ( ! isset( $field['sanitize'] ) ) {
        return is_array( $value ) ? wp_kses_post_deep( $value ) : wp_kses_post( $value );
      }

      if ( is_callable( $field['sanitize'] ) ) {
        return call_user_func( $field['sanitize'], $value );
      }

      return $value;

    }

    // save metabox
    public function save_meta_box( $post_id, $post ) {

      if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
      }

      if ( wp_is_post_revision( $post_id ) || ! in_array( $post->post_type, (array) $this->args['post_type'], true ) ) {
        return;
      }

      $nonce = ( isset( $_POST[ 'gp_metabox_nonce' . $this->unique ] ) ) ? sanitize_text_field( wp_unslash( $_POST[ 'gp_metabox_nonce' . $this->unique ] ) ) : '';

      if ( ! wp_verify_nonce( $nonce, 'gp_metabox_nonce' ) ) {
        return;
      }

      if ( ! current_user_can( $this->args['capability'], $post_id ) ) {
        return;
      }

      // Each field value is sanitized below, based on its field definition.
      $request = ( isset( $_POST[ $this->unique ] ) && is_array( $_POST[ $this->unique ] ) ) ? wp_unslash( $_POST[ $this->unique ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

      $data   = array();
      $errors = array();

      foreach ( $this->pre_fields as $field ) {

        if ( empty( $field['id'] ) ) {
          continue;
        }

        $field_id    = $field['id'];
        $field_value = ( isset( $request[$field_id] ) ) ? $request[$field_id] : '';

        // repeater values: drop empty rows
        if ( ! empty( $field['repeater'] ) && is_array( $field_value ) ) {
          $field_value = array_values( array_filter( $field_value, 'strlen' ) );
        }

        $data[$field_id] = $this->sanitize_value( $field, $field_value );

        if ( isset( $field['validate'] ) && is_callable( $field['validate'] ) ) {

          $has_validated = call_user_func( $field['validate'], $field_value );

          if ( ! empty( $has_validated ) ) {
            $old               = $this->get_meta_value( $field, $post_id );
            $data[$field_id]   = ( $old !== null ) ? $old : $this->get_default( $field );
            $errors[$field_id] = $has_validated;
          }

        }

      }

      $data = apply_filters( "gp_{$this->unique}_save", $data, $post_id, $this );

      do_action( "gp_{$this->unique}_save_before", $data, $post_id, $this );

      if ( $this->args['data_type'] === 'serialize' ) {

        // keep empty strings out of the stored array, same behaviour as before
        $data = array_filter( $data, static function ( $value ) {
          return ! ( $value === '' || $value === array() || $value === null );
        } );

        if ( empty( $data ) ) {
          delete_post_meta( $post_id, $this->unique );
        } else {
          update_post_meta( $post_id, $this->unique, $data );
        }

      } else {

        foreach ( $data as $field_id => $value ) {
          if ( $value === '' || $value === array() || $value === null ) {
            delete_post_meta( $post_id, $field_id );
          } else {
            update_post_meta( $post_id, $field_id, $value );
          }
        }

      }

      do_action( "gp_{$this->unique}_save_after", $data, $post_id, $this );

      if ( ! empty( $errors ) ) {
        set_transient( 'gp_metabox_errors_' . $post_id . '_' . get_current_user_id(), $errors, 60 );
      }

    }

  }
}
