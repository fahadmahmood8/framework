<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Field: text
 *
 * Guava Pattern addition: 'repeater' => true renders one input per stored value
 * (name="unique[id][]") so the admin JS can add/remove rows. Without the flag the
 * field behaves exactly like the stock text field of the Codestar Framework it is based on.
 *
 * @since 1.0.0
 * @version 2.0.0
 *
 */
if ( ! class_exists( 'GP_Field_text' ) ) {
  class GP_Field_text extends GP_Fields {

    public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
      parent::__construct( $field, $value, $unique, $where, $parent );
    }

    public function render() {

      $type     = ( ! empty( $this->field['attributes']['type'] ) ) ? $this->field['attributes']['type'] : 'text';
      $repeater = ! empty( $this->field['repeater'] );

      echo $this->field_before();

      if ( $repeater ) {

        $values = ( is_array( $this->value ) && ! empty( $this->value ) ) ? array_values( $this->value ) : array( ( is_array( $this->value ) ) ? '' : $this->value );

        foreach ( $values as $value ) {
          echo '<input type="'. esc_attr( $type ) .'" name="'. esc_attr( $this->field_name() ) .'[]" value="'. esc_attr( $value ) .'"'. $this->field_attributes( array( 'class' => 'repeater-field', 'data-repeater' => 'true' ) ) .' />';
        }

      } else {

        echo '<input type="'. esc_attr( $type ) .'" name="'. esc_attr( $this->field_name() ) .'" value="'. esc_attr( $this->value ) .'"'. $this->field_attributes() .' />';

      }

      echo $this->field_after();

    }

  }
}
