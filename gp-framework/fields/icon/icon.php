<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Field: icon
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'GP_Field_icon' ) ) {
  class GP_Field_icon extends GP_Fields {

    public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
      parent::__construct( $field, $value, $unique, $where, $parent );
    }

    public function render() {

      $args = wp_parse_args( $this->field, array(
        'button_title' => esc_html__( 'Add Icon', 'gp-framework' ),
        'remove_title' => esc_html__( 'Remove Icon', 'gp-framework' ),
      ) );

      echo $this->field_before();

      $nonce  = wp_create_nonce( 'gp_icon_nonce' );
      $hidden = ( empty( $this->value ) ) ? ' hidden' : '';

      echo '<div class="gp-icon-select">';
      echo '<span class="gp-icon-preview'. esc_attr( $hidden ) .'"><i class="'. esc_attr( $this->value ) .'"></i></span>';
      echo '<a href="#" class="button button-primary gp-icon-add" data-nonce="'. esc_attr( $nonce ) .'">'. $args['button_title'] .'</a>';
      echo '<a href="#" class="button gp-warning-primary gp-icon-remove'. esc_attr( $hidden ) .'">'. $args['remove_title'] .'</a>';
      echo '<input type="hidden" name="'. esc_attr( $this->field_name() ) .'" value="'. esc_attr( $this->value ) .'" class="gp-icon-value"'. $this->field_attributes() .' />';
      echo '</div>';

      echo $this->field_after();

    }

    public function enqueue() {
      add_action( 'admin_footer', array( 'GP_Field_icon', 'add_footer_modal_icon' ) );
      add_action( 'customize_controls_print_footer_scripts', array( 'GP_Field_icon', 'add_footer_modal_icon' ) );
    }

    public static function add_footer_modal_icon() {
    ?>
      <div id="gp-modal-icon" class="gp-modal gp-modal-icon hidden">
        <div class="gp-modal-table">
          <div class="gp-modal-table-cell">
            <div class="gp-modal-overlay"></div>
            <div class="gp-modal-inner">
              <div class="gp-modal-title">
                <?php esc_html_e( 'Add Icon', 'gp-framework' ); ?>
                <div class="gp-modal-close gp-icon-close"></div>
              </div>
              <div class="gp-modal-header">
                <input type="text" placeholder="<?php esc_html_e( 'Search...', 'gp-framework' ); ?>" class="gp-icon-search" />
              </div>
              <div class="gp-modal-content">
                <div class="gp-modal-loading"><div class="gp-loading"></div></div>
                <div class="gp-modal-load"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php
    }

  }
}
