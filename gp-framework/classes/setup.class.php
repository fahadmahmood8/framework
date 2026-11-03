<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Setup Class
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'GP_Setup' ) ) {
  class GP_Setup {

    // Default constants
    public static $premium  = false;
    public static $version  = '2.0.0';
    public static $dir      = '';
    public static $url      = '';
    public static $css      = '';
    public static $file     = '';
    public static $enqueue  = false;
    public static $webfonts = array();
    public static $subsets  = array();
    public static $inited   = array();
    public static $fields   = array();
    public static $args     = array(
      'admin_options'       => array(),
      'metabox_options'     => array(),
      'sections'            => array(),
    );

    private static $instance = null;

    public static function init( $file = __FILE__, $premium = false ) {

      // Set file constant
      self::$file = $file;

      // Set file constant
      self::$premium = $premium;

      // Set constants
      self::constants();

      // Include files
      self::includes();

      if ( is_null( self::$instance ) ) {
        self::$instance = new self();
      }

      return self::$instance;

    }

    // Initalize
    public function __construct() {

      // Init action
      do_action( 'gp_init' );

      // Setup textdomain
      self::textdomain();

      add_action( 'after_setup_theme', array( 'GP', 'setup' ) );
      add_action( 'init', array( 'GP', 'setup' ) );
      add_action( 'switch_theme', array( 'GP', 'setup' ) );
      add_action( 'admin_enqueue_scripts', array( 'GP', 'add_admin_enqueue_scripts' ) );
      add_action( 'wp_enqueue_scripts', array( 'GP', 'add_typography_enqueue_styles' ), 80 );
      add_action( 'wp_head', array( 'GP', 'add_custom_css' ), 80 );
      add_filter( 'admin_body_class', array( 'GP', 'add_admin_body_class' ) );

    }

    // Setup frameworks
    public static function setup() {

      // Setup admin option framework
      if ( class_exists( 'GP_Options' ) && ! empty( self::$args['admin_options'] ) ) {
        foreach ( self::$args['admin_options'] as $key => $value ) {
          if ( ! empty( self::$args['sections'][$key] ) && ! isset( self::$inited[$key] ) ) {

            self::$inited[$key] = true;

            GP_Options::instance( $key, array(
              'args'     => $value,
              'sections' => self::$args['sections'][$key],
            ) );

          }
        }
      }

      // Setup metabox framework
      if ( class_exists( 'GP_Metabox' ) && ! empty( self::$args['metabox_options'] ) ) {
        foreach ( self::$args['metabox_options'] as $key => $value ) {
          if ( ! empty( self::$args['sections'][$key] ) && ! isset( self::$inited[$key] ) ) {

            self::$inited[$key] = true;

            GP_Metabox::instance( $key, array(
              'args'     => $value,
              'sections' => self::$args['sections'][$key],
            ) );

          }
        }
      }

      do_action( 'gp_loaded' );

    }

    // Create options
    public static function createOptions( $id, $args = array() ) {
      self::$args['admin_options'][$id] = $args;
    }

    // Create metabox options
    public static function createMetabox( $id, $args = array() ) {
      self::$args['metabox_options'][$id] = $args;
    }

    // Create section
    public static function createSection( $id, $sections ) {
      self::$args['sections'][$id][] = $sections;
      self::set_used_fields( $sections );
    }

    // Set directory constants
    public static function constants() {

      // We need this path-finder code for set URL of framework
      $dirname        = str_replace( '//', '/', wp_normalize_path( dirname( dirname( self::$file ) ) ) );
      $theme_dir      = str_replace( '//', '/', wp_normalize_path( get_parent_theme_file_path() ) );
      $plugin_dir     = str_replace( '//', '/', wp_normalize_path( WP_PLUGIN_DIR ) );
      $plugin_dir     = str_replace( '/opt/bitnami', '/bitnami', $plugin_dir );
      $located_plugin = ( preg_match( '#'. self::sanitize_dirname( $plugin_dir ) .'#', self::sanitize_dirname( $dirname ) ) ) ? true : false;
      $directory      = ( $located_plugin ) ? $plugin_dir : $theme_dir;
      $directory_uri  = ( $located_plugin ) ? WP_PLUGIN_URL : get_parent_theme_file_uri();
      $foldername     = str_replace( $directory, '', $dirname );
      $protocol_uri   = ( is_ssl() ) ? 'https' : 'http';
      $directory_uri  = set_url_scheme( $directory_uri, $protocol_uri );

      self::$dir = $dirname;
      self::$url = $directory_uri . $foldername;

    }

    // Include file helper
    public static function include_plugin_file( $file, $load = true ) {

      $path     = '';
      $file     = ltrim( $file, '/' );
      $override = apply_filters( 'gp_framework_override', 'gp-framework-override' );

      if ( file_exists( get_parent_theme_file_path( $override .'/'. $file ) ) ) {
        $path = get_parent_theme_file_path( $override .'/'. $file );
      } elseif ( file_exists( get_theme_file_path( $override .'/'. $file ) ) ) {
        $path = get_theme_file_path( $override .'/'. $file );
      } elseif ( file_exists( self::$dir .'/'. $override .'/'. $file ) ) {
        $path = self::$dir .'/'. $override .'/'. $file;
      } elseif ( file_exists( self::$dir .'/'. $file ) ) {
        $path = self::$dir .'/'. $file;
      }

      if ( ! empty( $path ) && ! empty( $file ) && $load ) {

        global $wp_query;

        if ( is_object( $wp_query ) && function_exists( 'load_template' ) ) {

          load_template( $path, true );

        } else {

          require_once( $path );

        }

      } else {

        return self::$dir .'/'. $file;

      }

    }

    // Is active plugin helper
    public static function is_active_plugin( $file = '' ) {
      return in_array( $file, (array) get_option( 'active_plugins', array() ) );
    }

    // Sanitize dirname
    public static function sanitize_dirname( $dirname ) {
      return preg_replace( '/[^A-Za-z]/', '', $dirname );
    }

    // Set url constant
    public static function include_plugin_url( $file ) {
      return esc_url( self::$url ) .'/'. ltrim( $file, '/' );
    }

    // Include files
    public static function includes() {

      // Include common functions
      self::include_plugin_file( 'functions/actions.php'  );
      self::include_plugin_file( 'functions/helpers.php'  );
      self::include_plugin_file( 'functions/sanitize.php' );
      self::include_plugin_file( 'functions/validate.php' );

      // Include free version classes
      self::include_plugin_file( 'classes/abstract.class.php'      );
      self::include_plugin_file( 'classes/fields.class.php'        );
      self::include_plugin_file( 'classes/admin-options.class.php' );
      self::include_plugin_file( 'classes/metabox.class.php'       );

      // Include all framework fields
      $fields = apply_filters( 'gp_fields', array(
        'accordion',
        'background',
        'backup',
        'border',
        'button_set',
        'callback',
        'checkbox',
        'code_editor',
        'color',
        'color_group',
        'content',
        'date',
        'datetime',
        'dimensions',
        'fieldset',
        'gallery',
        'group',
        'heading',
        'icon',
        'image_select',
        'link',
        'link_color',
        'map',
        'media',
        'notice',
        'number',
        'palette',
        'radio',
        'repeater',
        'select',
        'slider',
        'sortable',
        'sorter',
        'spacing',
        'spinner',
        'subheading',
        'submessage',
        'switcher',
        'tabbed',
        'text',
        'textarea',
        'typography',
        'upload',
        'wp_editor',
      ) );

      if ( ! empty( $fields ) ) {
        foreach ( $fields as $field ) {
          if ( ! class_exists( 'GP_Field_'. $field ) && class_exists( 'GP_Fields' ) ) {
            self::include_plugin_file( 'fields/'. $field .'/'. $field .'.php' );
          }
        }
      }

    }

    // Setup textdomain
    public static function textdomain() {
      load_textdomain( 'gp-framework', self::$dir .'/languages/'. get_locale() .'.mo' );
    }

    // Set all of used fields
    public static function set_used_fields( $sections ) {

      if ( ! empty( $sections['fields'] ) ) {

        foreach ( $sections['fields'] as $field ) {

          if ( ! empty( $field['fields'] ) ) {
            self::set_used_fields( $field );
          }

          if ( ! empty( $field['tabs'] ) ) {
            self::set_used_fields( array( 'fields' => $field['tabs'] ) );
          }

          if ( ! empty( $field['accordions'] ) ) {
            self::set_used_fields( array( 'fields' => $field['accordions'] ) );
          }

          if ( ! empty( $field['elements'] ) ) {
            self::set_used_fields( array( 'fields' => $field['elements'] ) );
          }

          if ( ! empty( $field['type'] ) ) {
            self::$fields[$field['type']] = $field;
          }

        }

      }

    }

    // Enqueue admin and fields styles and scripts
    public static function add_admin_enqueue_scripts() {

      if ( ! self::$enqueue ) {

        // Loads scripts and styles only when needed
        $wpscreen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

        if ( $wpscreen ) {

          foreach ( (array) self::$args['admin_options'] as $argument ) {
            if ( ! empty( $argument['menu_slug'] ) && substr( $wpscreen->id, -strlen( $argument['menu_slug'] ) ) === $argument['menu_slug'] ) {
              self::$enqueue = true;
            }
          }

          foreach ( (array) self::$args['metabox_options'] as $argument ) {
            if ( ! empty( $argument['post_type'] ) && in_array( $wpscreen->post_type, (array) $argument['post_type'], true ) ) {
              self::$enqueue = true;
            }
          }

        }

      }

      if ( ! apply_filters( 'gp_enqueue_assets', self::$enqueue ) ) {
        return;
      }

      // Admin utilities
      wp_enqueue_media();

      // Wp color picker
      wp_enqueue_style( 'wp-color-picker' );
      wp_enqueue_script( 'wp-color-picker' );

      // Font Awesome 5 Free (bundled locally, no third-party CDN request)
      wp_enqueue_style( 'gp-fa5', self::include_plugin_url( 'assets/vendor/fontawesome/css/all.min.css' ), array(), '5.15.4', 'all' );
      wp_enqueue_style( 'gp-fa5-v4-shims', self::include_plugin_url( 'assets/vendor/fontawesome/css/v4-shims.min.css' ), array( 'gp-fa5' ), '5.15.4', 'all' );

      // Check for developer mode
      $min = ( self::$premium && SCRIPT_DEBUG ) ? '' : '.min';

      // Main style
      wp_enqueue_style( 'gp', self::include_plugin_url( 'assets/css/style'. $min .'.css' ), array(), self::$version, 'all' );

      // Main RTL styles
      if ( is_rtl() ) {
        wp_enqueue_style( 'gp-rtl', self::include_plugin_url( 'assets/css/style-rtl'. $min .'.css' ), array(), self::$version, 'all' );
      }

      // Main scripts
      wp_enqueue_script( 'gp-plugins', self::include_plugin_url( 'assets/js/plugins'. $min .'.js' ), array(), self::$version, true );
      wp_enqueue_script( 'gp', self::include_plugin_url( 'assets/js/main'. $min .'.js' ), array( 'gp-plugins' ), self::$version, true );

      // Main variables
      wp_localize_script( 'gp', 'gp_vars', array(
        'color_palette'     => apply_filters( 'gp_color_palette', array() ),
        'i18n'              => array(
          'confirm'         => esc_html__( 'Are you sure?', 'gp-framework' ),
          'typing_text'     => esc_html__( 'Please enter %s or more characters', 'gp-framework' ),
          'searching_text'  => esc_html__( 'Searching...', 'gp-framework' ),
          'no_results_text' => esc_html__( 'No results found.', 'gp-framework' ),
        ),
      ) );

      // Enqueue fields scripts and styles
      $enqueued = array();

      if ( ! empty( self::$fields ) ) {
        foreach ( self::$fields as $field ) {
          if ( ! empty( $field['type'] ) ) {
            $classname = 'GP_Field_' . $field['type'];
            if ( class_exists( $classname ) && method_exists( $classname, 'enqueue' ) ) {
              $instance = new $classname( $field );
              if ( method_exists( $classname, 'enqueue' ) ) {
                $instance->enqueue();
              }
              unset( $instance );
            }
          }
        }
      }

      do_action( 'gp_enqueue' );

    }

    // Add typography enqueue styles to front page
    public static function add_typography_enqueue_styles() {

      if ( ! empty( self::$webfonts ) ) {

        if ( ! empty( self::$webfonts['enqueue'] ) ) {

          $query = array();
          $fonts = array();

          foreach ( self::$webfonts['enqueue'] as $family => $styles ) {
            $fonts[] = $family . ( ( ! empty( $styles ) ) ? ':'. implode( ',', $styles ) : '' );
          }

          if ( ! empty( $fonts ) ) {
            $query['family'] = implode( '%7C', $fonts );
          }

          if ( ! empty( self::$subsets ) ) {
            $query['subset'] = implode( ',', self::$subsets );
          }

          $query['display'] = 'swap';

          wp_enqueue_style( 'gp-google-web-fonts', esc_url( add_query_arg( $query, '//fonts.googleapis.com/css' ) ), array(), null );

        }

        if ( ! empty( self::$webfonts['async'] ) ) {

          $fonts = array();

          foreach ( self::$webfonts['async'] as $family => $styles ) {
            $fonts[] = $family . ( ( ! empty( $styles ) ) ? ':'. implode( ',', $styles ) : '' );
          }

          wp_enqueue_script( 'gp-google-web-fonts', esc_url( '//ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js' ), array(), null );

          wp_localize_script( 'gp-google-web-fonts', 'WebFontConfig', array( 'google' => array( 'families' => $fonts ) ) );

        }

      }

    }

    // Add admin body class
    public static function add_admin_body_class( $classes ) {

      $classes .= ' gp-fa5-shims';

      return $classes;

    }

    // Add custom css to front page
    public static function add_custom_css() {

      if ( ! empty( self::$css ) ) {
        echo '<style type="text/css">'. wp_strip_all_tags( self::$css ) .'</style>';
      }

    }

    // Add a new framework field
    public static function field( $field = array(), $value = '', $unique = '', $where = '', $parent = '' ) {

      // Check for unallow fields
      if ( ! empty( $field['_notice'] ) ) {

        $field_type = $field['type'];

        $field            = array();
        $field['content'] = esc_html__( 'Oops! Not allowed.', 'gp-framework' ) .' <strong>('. $field_type .')</strong>';
        $field['type']    = 'notice';
        $field['style']   = 'danger';

      }

      $depend     = '';
      $visible    = '';
      $unique     = ( ! empty( $unique ) ) ? $unique : '';
      $class      = ( ! empty( $field['class'] ) ) ? ' ' . esc_attr( $field['class'] ) : '';
      $is_pseudo  = ( ! empty( $field['pseudo'] ) ) ? ' gp-pseudo-field' : '';
      $field_type = ( ! empty( $field['type'] ) ) ? esc_attr( $field['type'] ) : '';

      if ( ! empty( $field['dependency'] ) ) {

        $dependency      = $field['dependency'];
        $depend_visible  = '';
        $data_controller = '';
        $data_condition  = '';
        $data_value      = '';
        $data_global     = '';

        if ( is_array( $dependency[0] ) ) {
          $data_controller = implode( '|', array_column( $dependency, 0 ) );
          $data_condition  = implode( '|', array_column( $dependency, 1 ) );
          $data_value      = implode( '|', array_column( $dependency, 2 ) );
          $data_global     = implode( '|', array_column( $dependency, 3 ) );
          $depend_visible  = implode( '|', array_column( $dependency, 4 ) );
        } else {
          $data_controller = ( ! empty( $dependency[0] ) ) ? $dependency[0] : '';
          $data_condition  = ( ! empty( $dependency[1] ) ) ? $dependency[1] : '';
          $data_value      = ( ! empty( $dependency[2] ) ) ? $dependency[2] : '';
          $data_global     = ( ! empty( $dependency[3] ) ) ? $dependency[3] : '';
          $depend_visible  = ( ! empty( $dependency[4] ) ) ? $dependency[4] : '';
        }

        $depend .= ' data-controller="'. esc_attr( $data_controller ) .'"';
        $depend .= ' data-condition="'. esc_attr( $data_condition ) .'"';
        $depend .= ' data-value="'. esc_attr( $data_value ) .'"';
        $depend .= ( ! empty( $data_global ) ) ? ' data-depend-global="true"' : '';

        $visible = ( ! empty( $depend_visible ) ) ? ' gp-depend-visible' : ' gp-depend-hidden';

      }

      // These attributes has been sanitized above.
      echo '<div class="gp-field gp-field-'. $field_type . $is_pseudo . $class . $visible .'"'. $depend .'>';

      if ( ! empty( $field_type ) ) {

        if ( ! empty( $field['title'] ) ) {
          echo '<div class="gp-title">';
          echo '<h4>'. $field['title'] .'</h4>';
          echo ( ! empty( $field['subtitle'] ) ) ? '<div class="gp-subtitle-text">'. $field['subtitle'] .'</div>' : '';
          echo '</div>';
        }

        echo ( ! empty( $field['title'] ) ) ? '<div class="gp-fieldset">' : '';

        $value = ( ! isset( $value ) && isset( $field['default'] ) ) ? $field['default'] : $value;
        $value = ( isset( $field['value'] ) ) ? $field['value'] : $value;

        $classname = 'GP_Field_'. $field_type;

        if ( class_exists( $classname ) ) {
          $instance = new $classname( $field, $value, $unique, $where, $parent );
          $instance->render();
        } else {
          echo '<p>'. esc_html__( 'Field not found!', 'gp-framework' ) .'</p>';
        }

      } else {
        echo '<p>'. esc_html__( 'Field not found!', 'gp-framework' ) .'</p>';
      }

      echo ( ! empty( $field['title'] ) ) ? '</div>' : '';
      echo '<div class="clear"></div>';
      echo '</div>';

    }

  }

}

GP_Setup::init( __FILE__, false );

/**
 *
 * Extended Setup Class for Shortland
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'GP' ) ) {
  class GP extends GP_Setup{}
}
