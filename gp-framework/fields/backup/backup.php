<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Field: backup
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'GP_Field_backup' ) ) {
  class GP_Field_backup extends GP_Fields {

    public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
      parent::__construct( $field, $value, $unique, $where, $parent );
    }

    public function render() {

      $unique = $this->unique;
      $nonce  = wp_create_nonce( 'gp_backup_nonce' );
      $export = add_query_arg( array( 'action' => 'gp-export', 'unique' => $unique, 'nonce' => $nonce ), admin_url( 'admin-ajax.php' ) );

      echo $this->field_before();

      echo '<textarea name="gp_import_data" class="gp-import-data"></textarea>';
      echo '<button type="submit" class="button button-primary gp-confirm gp-import" data-unique="'. esc_attr( $unique ) .'" data-nonce="'. esc_attr( $nonce ) .'">'. esc_html__( 'Import', 'gp-framework' ) .'</button>';
      echo '<hr />';
      echo '<textarea readonly="readonly" class="gp-export-data">'. esc_attr( json_encode( get_option( $unique ) ) ) .'</textarea>';
      echo '<a href="'. esc_url( $export ) .'" class="button button-primary gp-export" target="_blank">'. esc_html__( 'Export & Download', 'gp-framework' ) .'</a>';
      echo '<hr />';
      echo '<button type="submit" name="gp_transient[reset]" value="reset" class="button gp-warning-primary gp-confirm gp-reset" data-unique="'. esc_attr( $unique ) .'" data-nonce="'. esc_attr( $nonce ) .'">'. esc_html__( 'Reset', 'gp-framework' ) .'</button>';

      echo $this->field_after();

    }

  }
}
