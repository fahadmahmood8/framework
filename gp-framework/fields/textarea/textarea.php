<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Field: textarea
 *
 * Guava Pattern addition: 'repeater' => true renders one textarea per stored value
 * (name="unique[id][]"), see the text field.
 *
 * @since 1.0.0
 * @version 2.0.0
 *
 */
if ( ! class_exists( 'GP_Field_textarea' ) ) {
  class GP_Field_textarea extends GP_Fields {

    public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
      parent::__construct( $field, $value, $unique, $where, $parent );
    }

    public function render() {

      $repeater = ! empty( $this->field['repeater'] );

      echo $this->field_before();

      if ( $repeater ) {

        $values = ( is_array( $this->value ) && ! empty( $this->value ) ) ? array_values( $this->value ) : array( ( is_array( $this->value ) ) ? '' : $this->value );

        foreach ( $values as $value ) {
          echo '<textarea name="'. esc_attr( $this->field_name() ) .'[]"'. $this->field_attributes( array( 'class' => 'repeater-field', 'data-repeater' => 'true' ) ) .'>'. esc_textarea( $value ) .'</textarea>';
        }

      } else {

        echo '<textarea name="'. esc_attr( $this->field_name() ) .'"'. $this->field_attributes() .'>'. esc_textarea( $this->value ) .'</textarea>';

      }

      echo $this->field_after();

    }

  }
}
