<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Placeholder tokens and the Guava Pattern template parser
 *
 * Write placeholders in any HTML file of a block theme (templates, template parts, patterns).
 * The key always comes first; type, group and default can follow in ANY order:
 *
 *   {{gp:key|type|group}}              a site option   (Guava Pattern admin panel)
 *   {{gp:key|group|type}}              same thing: the type is recognised by its name
 *   {{gp:key|type=image|group=home}}   named parts are always unambiguous
 *   {{gp.page:key|type|group}}         a page option   ("GP Pages" metabox, per page)
 *   {{gp:key|text|home|Default text}}  the part left over after type and group is the default
 *   {{gp.page:items|text[]|Features}}  "[]" after the type = repeater
 *
 * Template parser (loops and conditions, in the spirit of the CodeIgniter template parser):
 *
 *   {{gp.page:items|text|Features}}  <li>{{item}}</li>  {{/gp.page:items}}      loop over a repeater
 *       inside a loop: {{item}} {{index}} {{count}} {{first}} {{last}}
 *   {{if gp:hero_image|image|home}} <img src="{{gp:hero_image}}"> {{else}} ... {{/if}}
 *   {{if !gp.page:subtitle|text}} ... {{/if}}      "!" negates;  {{if first}} / {{if last}} inside loops
 *
 * Every token, condition and loop is collected into the same registries that gp_place() and
 * gp_page() fill (_gp_core_arr, _gp_core_groups, _gp_page_arr, _gp_page_groups,
 * _gp_repeater_groups), so the admin panel and the metabox are built from one page load.
 *
 * Rules for the loose order: a part is the TYPE when it is a known field type (text, textarea,
 * image, color, select, ... or a legacy name such as color_picker); the first other part is the
 * GROUP, the next one the DEFAULT. A group that is named like a type must be written group=name.
 *
 * @since 2.0.0
 * @version 2.1.0
 *
 */

/**
 *
 * Known field types (and friendly aliases) a token part can be recognised by
 *
 */
if ( ! function_exists( 'gp_token_type_alias' ) ) {
  function gp_token_type_alias( $type ) {

    $aliases = apply_filters( 'gp_token_type_aliases', array(
      'img'     => 'image',
      'picture' => 'image',
      'colour'  => 'color',
      'editor'  => 'wp_editor',
      'toggle'  => 'switcher',
      'bool'    => 'switcher',
    ) );

    return ( isset( $aliases[$type] ) ) ? $aliases[$type] : $type;

  }
}

if ( ! function_exists( 'gp_token_is_type' ) ) {
  function gp_token_is_type( $name ) {

    $name = gp_token_type_alias( $name );

    return ( in_array( $name, array( 'color_picker', 'wysiwyg', 'image' ), true ) || class_exists( 'GP_Field_'. $name ) );

  }
}

/**
 *
 * Read the spec of a token: "key|part|part|part" (everything after "gp:" or "gp.page:")
 *
 * @return array|null array( key, type, group, default, repeater ) or null when the key is invalid
 *
 */
if ( ! function_exists( 'gp_token_spec' ) ) {
  function gp_token_spec( $spec, $scope = 'option' ) {

    $parts = array_map( 'trim', explode( '|', $spec ) );
    $key   = array_shift( $parts );

    if ( ! preg_match( '/^[A-Za-z0-9_\-]+$/', $key ) ) {
      return null;
    }

    $type      = 'text';
    $type_set  = false;
    $group     = '';
    $default   = '';
    $repeater  = false;
    $loose     = array();

    foreach ( $parts as $i => $part ) {

      if ( $part === '' ) {
        continue;
      }

      // named parts: type=image  group=home  repeater=true  default=anything | even with bars
      if ( preg_match( '/^(type|group|default|repeater)\s*=\s*(.*)$/is', $part, $named ) ) {

        $name  = strtolower( $named[1] );
        $value = trim( $named[2] );

        if ( $name === 'default' ) {
          $default = trim( implode( '|', array_merge( array( $value ), array_slice( $parts, $i + 1 ) ) ) );
          break;
        } elseif ( $name === 'type' ) {
          $repeater = ( substr( $value, -2 ) === '[]' ) ? true : $repeater;
          $type     = gp_token_type_alias( preg_replace( '/\[\]$/', '', $value ) );
          $type_set = true;
        } elseif ( $name === 'repeater' ) {
          $repeater = in_array( strtolower( $value ), array( '1', 'true', 'yes', 'on' ), true );
        } else {
          $group = $value;
        }

        continue;

      }

      // the type is recognised by its name, wherever it stands
      $base = ( substr( $part, -2 ) === '[]' ) ? substr( $part, 0, -2 ) : $part;

      if ( ! $type_set && gp_token_is_type( $base ) ) {
        $type     = gp_token_type_alias( $base );
        $repeater = ( $base !== $part ) ? true : $repeater;
        $type_set = true;
        continue;
      }

      $loose[] = $part;

    }

    if ( $group === '' ) {
      $group = ( ! empty( $loose ) ) ? array_shift( $loose ) : ( ( $scope === 'page' ) ? 'page_options' : 'global_options' );
    }

    if ( $default === '' && ! empty( $loose ) ) {
      $default = implode( '|', $loose );
    }

    return array(
      'key'      => $key,
      'type'     => $type,
      'group'    => $group,
      'default'  => $default,
      'repeater' => $repeater,
      'explicit' => ( count( array_filter( $parts, 'strlen' ) ) > 0 ), // false for a bare {{gp:key}}
    );

  }
}

/**
 *
 * Split a template into text and tags
 *
 * @return array items: array( 't' => 'text', 'v' => string ) or a tag:
 *         var | if | else | endif | loopend | lv (loop variable) | raw (not ours, left untouched)
 *
 */
if ( ! function_exists( 'gp_tokens_split' ) ) {
  function gp_tokens_split( $content ) {

    $items = array();
    $pos   = 0;

    if ( ! preg_match_all( '/\{\{\s*([^{}]*?)\s*\}\}/', $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
      return array( array( 't' => 'text', 'v' => $content ) );
    }

    foreach ( $matches as $match ) {

      $raw    = $match[0][0];
      $offset = $match[0][1];
      $inner  = $match[1][0];

      if ( $offset > $pos ) {
        $items[] = array( 't' => 'text', 'v' => substr( $content, $pos, $offset - $pos ) );
      }

      $pos  = $offset + strlen( $raw );
      $item = array( 't' => 'raw', 'v' => $raw );

      if ( preg_match( '/^if\s+(!?)\s*gp(\.page)?\s*:\s*(.+)$/s', $inner, $m ) ) {
        $scope = ( ! empty( $m[2] ) ) ? 'page' : 'option';
        $spec  = gp_token_spec( $m[3], $scope );
        $item  = ( $spec ) ? array( 't' => 'if', 'neg' => ( $m[1] === '!' ), 'scope' => $scope, 'spec' => $spec, 'v' => $raw ) : $item;
      } elseif ( preg_match( '/^if\s+(!?)\s*(item|index|count|first|last)$/', $inner, $m ) ) {
        $item = array( 't' => 'if', 'neg' => ( $m[1] === '!' ), 'lv' => $m[2], 'v' => $raw );
      } elseif ( $inner === 'else' ) {
        $item = array( 't' => 'else', 'v' => $raw );
      } elseif ( $inner === '/if' || $inner === 'endif' ) {
        $item = array( 't' => 'endif', 'v' => $raw );
      } elseif ( preg_match( '/^\/\s*gp(\.page)?\s*:\s*([A-Za-z0-9_\-]+)$/', $inner, $m ) ) {
        $item = array( 't' => 'loopend', 'scope' => ( ! empty( $m[1] ) ) ? 'page' : 'option', 'key' => $m[2], 'v' => $raw );
      } elseif ( preg_match( '/^gp(\.page)?\s*:\s*(.+)$/s', $inner, $m ) ) {
        $scope = ( ! empty( $m[1] ) ) ? 'page' : 'option';
        $spec  = gp_token_spec( $m[2], $scope );
        $item  = ( $spec ) ? array( 't' => 'var', 'scope' => $scope, 'spec' => $spec, 'v' => $raw ) : $item;
      } elseif ( in_array( $inner, array( 'item', 'index', 'count', 'first', 'last' ), true ) ) {
        $item = array( 't' => 'lv', 'name' => $inner, 'v' => $raw );
      }

      $items[] = $item;

    }

    if ( $pos < strlen( $content ) ) {
      $items[] = array( 't' => 'text', 'v' => substr( $content, $pos ) );
    }

    return $items;

  }
}

/**
 *
 * Find the tokens in a string (for the registries)
 *
 * @return array list of array( scope, key, type, group, default, repeater )
 *
 */
if ( ! function_exists( 'gp_tokens_parse' ) ) {
  function gp_tokens_parse( $content ) {

    $tokens = array();

    if ( strpos( $content, '{{' ) === false ) {
      return $tokens;
    }

    $items = gp_tokens_split( $content );

    foreach ( $items as $i => $item ) {

      if ( $item['t'] !== 'var' && ( $item['t'] !== 'if' || empty( $item['spec'] ) ) ) {
        continue;
      }

      $spec     = $item['spec'];
      $repeater = $spec['repeater'];

      // a variable that has a closing tag later on is a loop over a repeater
      if ( $item['t'] === 'var' && ! $repeater ) {
        for ( $j = $i + 1; $j < count( $items ); $j++ ) {
          if ( $items[$j]['t'] === 'loopend' && $items[$j]['scope'] === $item['scope'] && $items[$j]['key'] === $spec['key'] ) {
            $repeater = true;
            break;
          }
        }
      }

      $tokens[] = array(
        'scope'    => $item['scope'],
        'key'      => $spec['key'],
        'type'     => $spec['type'],
        'group'    => $spec['group'],
        'default'  => $spec['default'],
        'repeater' => $repeater,
        'explicit' => $spec['explicit'],
      );

    }

    return $tokens;

  }
}

/**
 *
 * Files and database templates that may hold tokens
 *
 * Block theme folders: templates, parts, patterns (parent and child theme).
 * Add more with the gp_token_paths filter.
 *
 */
if ( ! function_exists( 'gp_tokens_files' ) ) {
  function gp_tokens_files() {

    $roots = array_unique( array( get_template_directory(), get_stylesheet_directory() ) );
    $dirs  = apply_filters( 'gp_token_paths', array( 'templates', 'parts', 'patterns' ) );
    $files = array();

    foreach ( $roots as $root ) {
      foreach ( (array) $dirs as $dir ) {
        foreach ( array( 'html', 'php' ) as $ext ) {
          $found = glob( trailingslashit( $root ) . trim( $dir, '/' ) . '/*.'. $ext );
          if ( $found ) {
            $files = array_merge( $files, $found );
          }
        }
      }
    }

    return $files;

  }
}

/**
 *
 * Collect every token (files + templates edited in the Site Editor)
 *
 */
if ( ! function_exists( 'gp_tokens_collect' ) ) {
  function gp_tokens_collect( $files = null ) {

    $files  = ( $files === null ) ? gp_tokens_files() : $files;
    $tokens = array();

    foreach ( $files as $file ) {
      if ( is_readable( $file ) ) {
        $tokens = array_merge( $tokens, gp_tokens_parse( (string) file_get_contents( $file ) ) );
      }
    }

    // templates, template parts and patterns saved from the Site Editor live in the database
    // (queried directly so it also works before the template post types are registered)
    global $wpdb;

    $contents = $wpdb->get_col( "SELECT post_content FROM {$wpdb->posts} WHERE post_type IN ( 'wp_template', 'wp_template_part', 'wp_block' ) AND post_status = 'publish' AND post_content LIKE '%{{gp%' LIMIT 500" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

    foreach ( (array) $contents as $content ) {
      $tokens = array_merge( $tokens, gp_tokens_parse( $content ) );
    }

    return $tokens;

  }
}

/**
 *
 * Register the tokens in the registries gp_place() / gp_page() use
 *
 */
if ( ! function_exists( 'gp_tokens_register' ) ) {
  function gp_tokens_register( $tokens ) {

    $core_arr    = get_option( '_gp_core_arr' );
    $core_groups = get_option( '_gp_core_groups' );
    $page_arr    = get_option( '_gp_page_arr' );
    $page_groups = get_option( '_gp_page_groups' );
    $rep_groups  = get_option( '_gp_repeater_groups' );
    $defaults    = get_option( '_gp_token_defaults' );

    $core_arr    = ( is_array( $core_arr ) )    ? $core_arr    : array();
    $core_groups = ( is_array( $core_groups ) ) ? $core_groups : array();
    $page_arr    = ( is_array( $page_arr ) )    ? $page_arr    : array();
    $page_groups = ( is_array( $page_groups ) ) ? $page_groups : array();
    $rep_groups  = ( is_array( $rep_groups ) )  ? $rep_groups  : array();
    $defaults    = ( is_array( $defaults ) )    ? $defaults    : array();

    // key lookups: a key that is already registered (by a token or by gp_place()) is left alone
    $has_key = static function ( $groups, $key ) {
      foreach ( $groups as $keys ) {
        if ( in_array( $key, (array) $keys, true ) ) {
          return true;
        }
      }
      return false;
    };

    // a bare {{gp:key}} (or {{if gp:key}}) must not claim a key before the token that defines its type
    usort( $tokens, static function ( $a, $b ) {
      return (int) $b['explicit'] - (int) $a['explicit'];
    } );

    // a key used as a loop (or written with "[]") anywhere is a repeater everywhere
    $rep_keys = array();
    foreach ( $tokens as $token ) {
      if ( $token['repeater'] ) {
        $rep_keys[$token['scope'] .':'. $token['key']] = true;
      }
    }

    foreach ( $tokens as $token ) {

      $key      = $token['key'];
      $repeater = isset( $rep_keys[$token['scope'] .':'. $key] );

      if ( $token['scope'] === 'page' ) {

        if ( ! $has_key( $page_groups, $key ) ) {
          $page_arr[$token['type']][]     = array( 'key' => $key, 'repeater' => $repeater );
          $page_groups[$token['group']][] = $key;
          if ( $repeater ) {
            $rep_groups['page'] = ( isset( $rep_groups['page'] ) && is_array( $rep_groups['page'] ) ) ? $rep_groups['page'] : array();
            if ( ! in_array( $token['group'], $rep_groups['page'], true ) ) {
              $rep_groups['page'][] = $token['group'];
            }
          }
        }

      } else {

        if ( ! $has_key( $core_groups, $key ) ) {
          $core_arr[$token['type']][]      = $key;
          $core_groups[$token['group']][]  = $key;
        }

      }

      if ( $token['default'] !== '' ) {
        $defaults[$token['scope'] .':'. $key] = $token['default'];
      }

    }

    update_option( '_gp_core_arr', $core_arr );
    update_option( '_gp_core_groups', $core_groups );
    update_option( '_gp_page_arr', $page_arr );
    update_option( '_gp_page_groups', $page_groups );
    if ( ! empty( $rep_groups ) ) {
      update_option( '_gp_repeater_groups', $rep_groups );
    }
    update_option( '_gp_token_defaults', $defaults );

  }
}

/**
 *
 * Refresh: scan only when a template changed (cheap signature check), so a normal
 * request costs a few stat() calls.
 *
 */
if ( ! function_exists( 'gp_tokens_refresh' ) ) {
  function gp_tokens_refresh() {

    static $done = false;

    if ( $done || ( defined( 'GP_ACTIVE_TOKENS' ) && ! GP_ACTIVE_TOKENS ) ) {
      return;
    }

    $done  = true;
    $files = gp_tokens_files();
    $sig   = '';

    foreach ( $files as $file ) {
      $sig .= $file . filemtime( $file ) . filesize( $file );
    }

    $sig = md5( $sig . get_stylesheet() );

    if ( ! get_option( '_gp_tokens_dirty' ) && get_option( '_gp_tokens_sig' ) === $sig ) {
      return;
    }

    gp_tokens_register( gp_tokens_collect( $files ) );

    delete_option( '_gp_tokens_dirty' );
    update_option( '_gp_tokens_sig', $sig, true );

  }
  // before the admin panel / metabox configs are built (gp_framework_load_configs runs at 5)
  add_action( 'after_setup_theme', 'gp_tokens_refresh', 4 );
  add_action( 'init', 'gp_tokens_refresh', 4 );
}

// a template, part or pattern saved in the Site Editor: rescan on the next request
if ( ! function_exists( 'gp_tokens_mark_dirty' ) ) {
  function gp_tokens_mark_dirty( $post_id, $post = null ) {
    $post = ( $post ) ? $post : get_post( $post_id );
    if ( $post && in_array( $post->post_type, array( 'wp_template', 'wp_template_part', 'wp_block' ), true ) ) {
      update_option( '_gp_tokens_dirty', 1 );
    }
  }
  add_action( 'save_post', 'gp_tokens_mark_dirty', 10, 2 );
}

/**
 *
 * Saved value of a token (falls back to the default written in the template)
 *
 */
if ( ! function_exists( 'gp_tokens_value' ) ) {
  function gp_tokens_value( $scope, $spec, $post_id ) {

    static $defaults = null;

    if ( $defaults === null ) {
      $defaults = get_option( '_gp_token_defaults' );
      $defaults = ( is_array( $defaults ) ) ? $defaults : array();
    }

    $key   = $spec['key'];
    $value = ( $scope === 'page' ) ? gp_get_meta( $post_id, $key ) : gp_get_option( $key );

    if ( $value === null || $value === '' || $value === array() ) {
      $value = ( $spec['default'] !== '' ) ? $spec['default'] : ( isset( $defaults[$scope .':'. $key] ) ? $defaults[$scope .':'. $key] : '' );
    }

    return $value;

  }
}

/**
 *
 * Build the tree of nodes (text, variables, conditions, loops) from the split template
 *
 */
if ( ! function_exists( 'gp_tokens_build' ) ) {
  function gp_tokens_build( $items, &$i, $stop = '', $loop_key = array() ) {

    $nodes = array();
    $count = count( $items );

    while ( $i < $count ) {

      $item = $items[$i];

      if ( $item['t'] === 'else' || $item['t'] === 'endif' ) {
        if ( $stop === 'if' ) {
          return array( $nodes, $item['t'] );
        }
      }

      if ( $item['t'] === 'loopend' && $stop === 'loop' && $item['scope'] === $loop_key[0] && $item['key'] === $loop_key[1] ) {
        return array( $nodes, 'loopend' );
      }

      if ( $item['t'] === 'if' ) {

        $i++;
        list( $then, $end ) = gp_tokens_build( $items, $i, 'if' );
        $else = array();

        if ( $end === 'else' ) {
          $i++;
          list( $else, $end ) = gp_tokens_build( $items, $i, 'if' );
        }

        if ( $end === 'endif' ) {
          $i++;
        }

        $item['then'] = $then;
        $item['else'] = $else;
        $nodes[]      = $item;
        continue;

      }

      if ( $item['t'] === 'var' ) {

        $is_loop = false;

        for ( $j = $i + 1; $j < $count; $j++ ) {
          if ( $items[$j]['t'] === 'loopend' && $items[$j]['scope'] === $item['scope'] && $items[$j]['key'] === $item['spec']['key'] ) {
            $is_loop = true;
            break;
          }
        }

        if ( $is_loop ) {

          $i++;
          list( $body, $end ) = gp_tokens_build( $items, $i, 'loop', array( $item['scope'], $item['spec']['key'] ) );

          if ( $end === 'loopend' ) {
            $i++;
          }

          $item['t']    = 'loop';
          $item['body'] = $body;
          $nodes[]      = $item;
          continue;

        }

      }

      $nodes[] = $item;
      $i++;

    }

    return array( $nodes, '' );

  }
}

/**
 *
 * Output the nodes. Output is never parsed again, so a saved value that looks like a token is
 * printed as plain (escaped) text.
 *
 */
if ( ! function_exists( 'gp_tokens_run' ) ) {
  function gp_tokens_run( $nodes, $post_id, $ctx = array() ) {

    $out = '';

    foreach ( $nodes as $node ) {

      switch ( $node['t'] ) {

        case 'text':
        case 'raw':
        case 'else':
        case 'endif':
        case 'loopend':
          $out .= $node['v'];
          break;

        case 'var':
          $out .= gp_tokens_format( gp_tokens_value( $node['scope'], $node['spec'], $post_id ), $node['spec']['type'] );
          break;

        case 'lv':
          if ( empty( $ctx ) ) {
            $out .= $node['v']; // not inside a loop: leave it alone
          } elseif ( $node['name'] === 'item' ) {
            $out .= gp_tokens_format( $ctx['item'], $ctx['type'] );
          } elseif ( in_array( $node['name'], array( 'first', 'last' ), true ) ) {
            $out .= ( $ctx[$node['name']] ) ? '1' : '';
          } else {
            $out .= (int) $ctx[$node['name']];
          }
          break;

        case 'if':
          if ( isset( $node['lv'] ) ) {
            $check = ( ! empty( $ctx ) ) ? $ctx[$node['lv']] : false;
          } else {
            $check = gp_tokens_value( $node['scope'], $node['spec'], $post_id );
          }
          $true = ( $check !== null && $check !== '' && $check !== array() && $check !== false && $check !== 0 && $check !== '0' );
          $out .= gp_tokens_run( ( $true xor $node['neg'] ) ? $node['then'] : $node['else'], $post_id, $ctx );
          break;

        case 'loop':
          $value = gp_tokens_value( $node['scope'], $node['spec'], $post_id );
          $rows  = ( is_array( $value ) ) ? array_values( $value ) : ( ( $value === '' || $value === null ) ? array() : array( $value ) );
          $total = count( $rows );
          foreach ( $rows as $n => $row ) {
            if ( ! is_scalar( $row ) ) {
              continue;
            }
            $out .= gp_tokens_run( $node['body'], $post_id, array(
              'item'  => $row,
              'type'  => $node['spec']['type'],
              'index' => $n + 1,
              'count' => $total,
              'first' => ( $n === 0 ),
              'last'  => ( $n === $total - 1 ),
            ) );
          }
          break;

      }

    }

    return $out;

  }
}

/**
 *
 * Render a template string: variables, loops and conditions
 *
 * @param string $content HTML containing tokens
 * @param int    $post_id page whose gp.page tokens should be read (0 = current)
 *
 */
if ( ! function_exists( 'gp_tokens_replace' ) ) {
  function gp_tokens_replace( $content, $post_id = 0 ) {

    if ( ! is_string( $content ) || strpos( $content, '{{' ) === false ) {
      return $content;
    }

    $post_id = ( $post_id ) ? (int) $post_id : (int) get_the_ID();
    $post_id = ( $post_id ) ? $post_id : (int) get_queried_object_id();

    $items = gp_tokens_split( $content );
    $i     = 0;

    list( $nodes ) = gp_tokens_build( $items, $i );

    return gp_tokens_run( $nodes, $post_id );

  }
}

/**
 *
 * Escape a value for output, based on its field type
 *
 */
if ( ! function_exists( 'gp_tokens_format' ) ) {
  function gp_tokens_format( $value, $type = 'text', $repeater = false ) {

    if ( is_array( $value ) ) {

      $items = '';

      foreach ( $value as $item ) {
        if ( is_scalar( $item ) ) {
          $items .= '<li>'. gp_tokens_format( $item, $type ) .'</li>';
        }
      }

      return ( $items !== '' ) ? '<ul class="gp-repeater">'. $items .'</ul>' : '';

    }

    $value = (string) $value;

    switch ( gp_field_type( $type ) ) {

      case 'upload':
      case 'media':
        return esc_url( wp_strip_all_tags( $value ) );

      case 'color':
      case 'number':
      case 'switcher':
      case 'select':
      case 'radio':
        return esc_attr( $value );

      case 'textarea':
        return nl2br( wp_kses_post( $value ) );

      default:
        return wp_kses_post( $value );

    }

  }
}

/**
 *
 * Replace tokens in everything a block theme renders (templates, parts, patterns, post content)
 *
 */
if ( ! function_exists( 'gp_tokens_render_block' ) ) {
  function gp_tokens_render_block( $block_content, $block, $instance = null ) {

    if ( $block_content === '' || strpos( $block_content, '{{' ) === false ) {
      return $block_content;
    }

    $post_id = ( $instance instanceof WP_Block && ! empty( $instance->context['postId'] ) ) ? (int) $instance->context['postId'] : 0;

    return gp_tokens_replace( $block_content, $post_id );

  }
  add_filter( 'render_block', 'gp_tokens_render_block', 20, 3 );
}
