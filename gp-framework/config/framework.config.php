<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
// ===============================================================================================
// -----------------------------------------------------------------------------------------------
// FRAMEWORK SETTINGS: the Guava Pattern admin options panel
//
// The panel is built dynamically: every gp_place() / gp_option() call in the theme registers a
// field (type, key, group) in the _gp_core_arr and _gp_core_groups options, and each group
// becomes a tab here. Saved values live in the GP_OPTION row, same as in every earlier release.
// -----------------------------------------------------------------------------------------------
// ===============================================================================================

if ( ! class_exists( 'GP' ) ) {
  return;
}

$gp_prefix = GP_OPTION;
$gp_info   = ( function_exists( 'gp_info' ) ) ? gp_info() : array();
$gp_name   = ( function_exists( 'gp_humanize' ) && ! empty( $gp_info['name'] ) ) ? gp_humanize( $gp_info['name'] ) : 'Guava Pattern';
$gp_docs   = ( ! empty( $gp_info['documentation'] ) ) ? $gp_info['documentation'] : 'https://guavapattern.com/documentation';
$gp_rel    = ( ! empty( $gp_info['release'] ) ) ? $gp_info['release'] : '';

// Clear the collected registry (administrators only, nonce protected, see gp_reset_url()).
if ( gp_can_reset( 'reset_gp' ) ) {
  delete_option( '_gp_core_arr' );
  delete_option( '_gp_core_groups' );
}

// ----------------------------------------
// Panel settings                         -
// ----------------------------------------
GP::createOptions( $gp_prefix, array(
  'framework_title' => esc_html( $gp_name ) .' <small>v'. esc_html( GP_VERSION ) . ( $gp_rel ? ' / '. esc_html( $gp_rel ) : '' ) .'</small>'
                      .' <small class="gp-header-links"><a target="_blank" rel="noopener" href="'. esc_url( $gp_docs ) .'" class="wp-docs">'. esc_html__( 'Documentation', 'gp-framework' ) .'</a>'
                      .'<a target="_blank" rel="noopener" href="https://fontawesome.com/v5/search?m=free" class="wp-fa">Font Awesome</a></small>',
  'menu_title'      => $gp_name,
  'menu_slug'       => 'guava-pattern',
  'menu_type'       => 'menu',
  'menu_capability' => 'manage_options',
  'show_bar_menu'   => false, // the theme adds its own toolbar link
  'ajax_save'       => false,
  'footer_text'     => esc_html( $gp_name ) .' <strong>v'. esc_html( GP_VERSION ) .'</strong>',
  'enqueue_webfont' => false, // no Google Fonts request unless a theme turns it on
) );

// ----------------------------------------
// Sections: one tab per collected group  -
// ----------------------------------------
$gp_core_arr    = get_option( '_gp_core_arr' );
$gp_core_groups = get_option( '_gp_core_groups' );
$gp_core_arr    = ( is_array( $gp_core_arr ) ) ? $gp_core_arr : array();
$gp_core_groups = ( is_array( $gp_core_groups ) ) ? $gp_core_groups : array();

// key => type, the first registration of a key wins
$gp_key_type = array();
foreach ( $gp_core_arr as $gp_type => $gp_keys ) {
  foreach ( (array) $gp_keys as $gp_key ) {
    if ( ! isset( $gp_key_type[$gp_key] ) ) {
      $gp_key_type[$gp_key] = $gp_type;
    }
  }
}

$gp_sections = 0;

foreach ( $gp_core_groups as $gp_group => $gp_fields ) {

  if ( empty( $gp_fields ) ) {
    continue;
  }

  $gp_section_fields = array();

  foreach ( array_unique( (array) $gp_fields ) as $gp_field ) {

    if ( ! isset( $gp_key_type[$gp_field] ) ) {
      continue;
    }

    $gp_type = gp_field_type( $gp_key_type[$gp_field] );
    $gp_def  = array(
      'id'      => $gp_field,
      'type'    => $gp_type,
      'title'   => ( function_exists( 'gp_capital' ) ) ? gp_capital( $gp_field ) : ucwords( str_replace( '_', ' ', $gp_field ) ),
      'default' => '',
    );

    // extra arguments registered with gp_option( $key, $type, $group, $args )
    $gp_args = ( function_exists( 'gp_args' ) ) ? gp_args( $gp_field ) : array();

    if ( ! empty( $gp_args['options'] ) ) {
      $gp_def['options'] = $gp_args['options'];
      if ( $gp_type === 'select' ) {
        $gp_def['placeholder'] = esc_html__( 'Select', 'gp-framework' );
      }
    }

    $gp_section_fields[] = $gp_def;

  }

  if ( empty( $gp_section_fields ) ) {
    continue;
  }

  GP::createSection( $gp_prefix, array(
    'id'     => 'group_'. $gp_group,
    'title'  => ( function_exists( 'gp_capital' ) ) ? gp_capital( $gp_group ) : ucwords( str_replace( '_', ' ', $gp_group ) ),
    'icon'   => 'fa fa-object-ungroup',
    'fields' => $gp_section_fields,
  ) );

  $gp_sections++;

}

// Fresh install: nothing collected yet, show a hint instead of an empty (hidden) panel.
if ( ! $gp_sections ) {

  GP::createSection( $gp_prefix, array(
    'title'  => esc_html__( 'Getting Started', 'gp-framework' ),
    'icon'   => 'fa fa-seedling',
    'fields' => array(
      array(
        'type'    => 'notice',
        'style'   => 'info',
        'content' => esc_html__( 'Options appear here as your theme templates call gp_place( $key, $type, $group ). Load a front-end page that uses them, then reload this screen.', 'gp-framework' ),
      ),
    ),
  ) );

}

unset( $gp_prefix, $gp_info, $gp_name, $gp_docs, $gp_rel, $gp_core_arr, $gp_core_groups, $gp_key_type, $gp_sections, $gp_group, $gp_fields, $gp_field, $gp_type, $gp_keys, $gp_key, $gp_def, $gp_args, $gp_section_fields );
