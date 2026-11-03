<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
// ===============================================================================================
// -----------------------------------------------------------------------------------------------
// METABOX SETTINGS: page options
//
// Every gp_page() / gp_page_option() call in a template registers a field (type, key, group,
// repeater flag) in the _gp_page_arr, _gp_page_groups and _gp_repeater_groups options. Each
// group becomes a tab of the "GP Pages" metabox. Values are stored in the GP_META post meta row
// (one array per page), exactly as in every earlier release.
// -----------------------------------------------------------------------------------------------
// ===============================================================================================

if ( ! is_admin() || ! class_exists( 'GP' ) ) {
  return;
}

// Clear the collected registry (administrators only, nonce protected, see gp_reset_url( 'pages' )).
if ( gp_can_reset( 'reset_gp_pages' ) ) {
  delete_option( '_gp_page_arr' );
  delete_option( '_gp_page_groups' );
  delete_option( '_gp_repeater_groups' );
}

$gp_page_arr    = get_option( '_gp_page_arr' );
$gp_page_groups = get_option( '_gp_page_groups' );
$gp_rep_groups  = get_option( '_gp_repeater_groups' );
$gp_page_arr    = ( is_array( $gp_page_arr ) ) ? $gp_page_arr : array();
$gp_page_groups = ( is_array( $gp_page_groups ) ) ? $gp_page_groups : array();
$gp_rep_groups  = ( is_array( $gp_rep_groups ) ) ? $gp_rep_groups : array();

$gp_post_types  = apply_filters( 'gp_metabox_post_types', array( 'page' ) );
$gp_prefix      = GP_META;

// key => array( type, repeater ), the first registration of a key wins
$gp_key_info = array();
foreach ( $gp_page_arr as $gp_type => $gp_entries ) {
  foreach ( (array) $gp_entries as $gp_entry ) {
    $gp_key = ( is_array( $gp_entry ) ) ? ( isset( $gp_entry['key'] ) ? $gp_entry['key'] : '' ) : $gp_entry;
    if ( $gp_key !== '' && ! isset( $gp_key_info[$gp_key] ) ) {
      $gp_key_info[$gp_key] = array(
        'type'     => $gp_type,
        'repeater' => ( is_array( $gp_entry ) && ! empty( $gp_entry['repeater'] ) ),
      );
    }
  }
}

// repeater groups of the page post type
$gp_rep_page = ( isset( $gp_rep_groups['page'] ) && is_array( $gp_rep_groups['page'] ) ) ? $gp_rep_groups['page'] : array();

$gp_sections = array();

foreach ( $gp_page_groups as $gp_group => $gp_fields ) {

  if ( empty( $gp_fields ) ) {
    continue;
  }

  $gp_is_repeater    = in_array( $gp_group, $gp_rep_page, true );
  $gp_section_fields = array();

  foreach ( array_unique( (array) $gp_fields ) as $gp_field ) {

    if ( ! isset( $gp_key_info[$gp_field] ) ) {
      continue;
    }

    $gp_type = gp_field_type( $gp_key_info[$gp_field]['type'] );
    $gp_def  = array(
      'id'      => $gp_field,
      'type'    => $gp_type,
      'title'   => ( function_exists( 'gp_capital' ) ) ? gp_capital( $gp_field ) : ucwords( str_replace( '_', ' ', $gp_field ) ),
      'default' => '',
    );

    // the repeater flag is supported by the text and textarea fields
    if ( $gp_key_info[$gp_field]['repeater'] && in_array( $gp_type, array( 'text', 'textarea' ), true ) ) {
      $gp_def['repeater'] = true;
    }

    $gp_section_fields[] = $gp_def;

  }

  if ( empty( $gp_section_fields ) ) {
    continue;
  }

  $gp_sections[] = array(
    'title'  => ( function_exists( 'gp_capital' ) ) ? gp_capital( $gp_group ) : ucwords( str_replace( '_', ' ', $gp_group ) ),
    'icon'   => 'fa fa-'. ( $gp_is_repeater ? 'redo' : 'circle' ),
    'class'  => ( $gp_is_repeater ? 'repeater' : '' ),
    'fields' => $gp_section_fields,
  );

}

if ( ! empty( $gp_sections ) ) {

  GP::createMetabox( $gp_prefix, array(
    'title'     => 'GP Pages',
    'post_type' => $gp_post_types,
    'context'   => 'normal',
    'priority'  => 'default',
    'data_type' => 'serialize',
    'theme'     => 'light',
    'rest'      => false,
  ) );

  foreach ( $gp_sections as $gp_section ) {
    GP::createSection( $gp_prefix, $gp_section );
  }

}

unset( $gp_page_arr, $gp_page_groups, $gp_rep_groups, $gp_post_types, $gp_prefix, $gp_key_info, $gp_type, $gp_entries, $gp_entry, $gp_key, $gp_rep_page, $gp_sections, $gp_group, $gp_fields, $gp_is_repeater, $gp_section_fields, $gp_field, $gp_def, $gp_section );
