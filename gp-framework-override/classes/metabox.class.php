<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access pages directly.
/**
 *
 * Metabox Class
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
class GPFramework_Metabox extends GPFramework_Abstract{

  /**
   *
   * metabox options
   * @access public
   * @var array
   *
   */
  public $options = array();

  /**
   *
   * instance
   * @access private
   * @var class
   *
   */
  private static $instance = null;

  // run metabox construct
  public function __construct( $options ){

    $this->options = apply_filters( 'gp_metabox_options', $options );

    if( ! empty( $this->options ) ) {
      $this->addAction( 'add_meta_boxes', 'add_meta_box' );
      $this->addAction( 'save_post', 'save_post', 10, 2 );
    }

  }

  // instance
  public static function instance( $options = array() ){
    if ( is_null( self::$instance ) && gp_ACTIVE_METABOX ) {
      self::$instance = new self( $options );
    }
    return self::$instance;
  }

  // add metabox
  public function add_meta_box( $post_type ) {

    foreach ( $this->options as $value ) {
      if( $post_type == $value['post_type'] ) {
        add_meta_box( $value['id'], $value['title'], array( &$this, 'render_meta_box_content' ), $value['post_type'], $value['context'], $value['priority'], $value );
      }
    }

  }

  // metabox render content
  public function render_meta_box_content( $post, $callback ) {

    global $post, $gp_errors;

    wp_nonce_field( 'gp-framework-metabox', 'gp-framework-metabox-nonce' );

    $unique       = $callback['args']['id'];
    $sections     = $callback['args']['sections'];
    $meta_value   = get_post_meta( $post->ID, $unique, true );
    $transient    = get_transient( 'gp-metabox-transient' );
    $gp_errors    = $transient['errors'];
    $has_nav      = ( count( $sections ) >= 2 && $callback['args']['context'] != 'side' ) ? true : false;
    $show_all     = ( ! $has_nav ) ? ' gp-show-all' : '';
    $section_name = ( ! empty( $sections[0]['fields'] ) ) ? $sections[0]['name'] : $sections[1]['name'];
    $section_id   = ( ! empty( $transient['ids'][$unique] ) ) ? $transient['ids'][$unique] : $section_name;
    $section_id   = ( ! empty( $_GET['gp-section'] ) ) ? esc_attr( $_GET['gp-section'] ) : $section_id;

    echo '<div class="gp-framework gp-metabox-framework">';

      echo '<input type="hidden" name="gp_section_id['. $unique .']" class="gp-reset" value="'. $section_id .'">';

      echo '<div class="gp-body'. $show_all .'">';

        if( $has_nav ) {

          echo '<div class="gp-nav">';

            echo '<ul>';
            foreach( $sections as $value ) {

              $tab_icon = ( ! empty( $value['icon'] ) ) ? '<i class="gp-icon '. $value['icon'] .'"></i>' : '';

              if( isset( $value['fields'] ) ) {
                $active_section = ( $section_id == $value['name'] ) ? ' class="gp-section-active"' : '';
                echo '<li><a href="#"'. $active_section .' data-section="'. $value['name'] .'">'. $tab_icon . $value['title'] .'</a></li>';
              } else {
                echo '<li><div class="gp-seperator">'. $tab_icon . $value['title'] .'</div></li>';
              }

            }
            echo '</ul>';

          echo '</div>';

        }

        echo '<div class="gp-content">';

          echo '<div class="gp-sections">';
		  //pree($sections);
          foreach( $sections as $val ) {
			$gp_classes = isset($val['class'])?$val['class']:'';
			//pree($val);
            if( isset( $val['fields'] ) ) {

              $active_content = ( $section_id == $val['name'] ) ? ' style="display: block;"' : '';

              echo '<div id="gp-tab-'. $val['name'] .'" class="gp-section '.$gp_classes.'"'. $active_content .'>';
              echo ( isset( $val['title'] ) ) ? '<div class="gp-section-title gp_'.gp_underscore($val['title']).'"><h3>'. $val['title'] .'</h3></div>' : '';

              foreach ( $val['fields'] as $field_key => $field ) {

                $default    = ( isset( $field['default'] ) ) ? $field['default'] : '';
                $elem_id    = ( isset( $field['id'] ) ) ? $field['id'] : '';
                $elem_value = ( is_array( $meta_value ) && isset( $meta_value[$elem_id] ) ) ? $meta_value[$elem_id] : $default;
				
				//$field['repeater'] = true;
				//pree($field);pree($elem_value);pree($unique);
                echo gp_add_element( $field, $elem_value, $unique );

              }
              echo '</div>';

            }
          }
          echo '</div>';

          echo '<div class="clear"></div>';

        echo '</div>';

        echo ( $has_nav ) ? '<div class="gp-nav-background"></div>' : '';

        echo '<div class="clear"></div>';

      echo '</div>';

    echo '</div>';

  }

  // save metabox options
  public function save_post( $post_id, $post ) {

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return $post_id; }

    $nonce = ( isset( $_POST['gp-framework-metabox-nonce'] ) ) ? $_POST['gp-framework-metabox-nonce'] : '';

    if ( ! wp_verify_nonce( $nonce, 'gp-framework-metabox' ) ) { return $post_id; }

    $errors = array();
    $post_type = ( isset( $_POST['post_type'] ) ) ? $_POST['post_type'] : '';

    if ( 'page' == $post_type ) {
      if ( ! current_user_can( 'edit_page', $post_id ) ) { return $post_id; }
    } else {
      if ( ! current_user_can( 'edit_post', $post_id ) ) { return $post_id; }
    }

    foreach ( $this->options as $request_value ) {

      if( $post_type == $request_value['post_type'] ) {

        $request_key = $request_value['id'];
        $meta_value  = get_post_meta( $post_id, $request_key, true );
        $request     = ( isset( $_POST[$request_key] ) ) ? $_POST[$request_key] : array();

        // ignore _nonce
        if( isset( $request['_nonce'] ) ) {
          unset( $request['_nonce'] );
        }

        foreach( $request_value['sections'] as $key => $section ) {

          if( isset( $section['fields'] ) ) {

            foreach( $section['fields'] as $field ) {
				//pree($field);
              if( isset( $field['type'] ) && isset( $field['id'] ) ) {

                $field_value = ( isset( $_POST[$request_key][$field['id']] ) ) ? $_POST[$request_key][$field['id']] : '';

				//pree($field_value);
                // sanitize options
                if( isset( $field['sanitize'] ) && $field['sanitize'] !== false ) {
                  $sanitize_type = $field['sanitize'];
                } else if ( ! isset( $field['sanitize'] ) ) {
                  $sanitize_type = $field['type'];
                }
				$filter_san = 'gp_sanitize_'. $sanitize_type;
				//pree($filter_san);
                if( has_filter( $filter_san ) ) {
                  $request[$field['id']] = apply_filters( 'gp_sanitize_' . $sanitize_type, $field_value, $field, $section['fields'] );
                }
				//pree($request);
                // validate options
                if ( isset( $field['validate'] ) && has_filter( 'gp_validate_'. $field['validate'] ) ) {

                  $validate = apply_filters( 'gp_validate_' . $field['validate'], $field_value, $field, $section['fields'] );

                  if( ! empty( $validate ) ) {

                    $errors[$field['id']] = array( 'code' => $field['id'], 'message' => $validate, 'type' => 'error' );
                    $default_value = isset( $field['default'] ) ? $field['default'] : '';
                    $request[$field['id']] = ( isset( $meta_value[$field['id']] ) ) ? $meta_value[$field['id']] : $default_value;

                  }

                }

              }

            }

          }

        }

        $request = apply_filters( 'gp_save_post', $request, $request_key, $meta_value, $this );

        if( empty( $request ) ) {

          delete_post_meta( $post_id, $request_key );

        } else {
			
			//pree($post_id);pree($request_key);pree($request);exit;
			
          if( get_post_meta( $post_id, $request_key ) ) {
			if(empty($request))
			$request = array_filter($request, 'strlen');
			
            update_post_meta( $post_id, $request_key, $request );

          } else {

            add_post_meta( $post_id, $request_key, $request );

          }

        }

        $transient['ids'][$request_key] = $_POST['gp_section_id'][$request_key];
        $transient['errors'] = $errors;

      }

    }

    set_transient( 'gp-metabox-transient', $transient, 10 );

  }

}
