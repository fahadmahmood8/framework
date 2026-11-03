# PHP API reference
Beyond placeholders, you can build your own panels and metaboxes by hand with the `GP::` class, the successor of the Codestar `CSF::` API.

```php
if ( class_exists( 'GP' ) ) {

  $prefix = 'my_options';

  GP::createOptions( $prefix, array(
    'menu_title' => 'My Options',
    'menu_slug'  => 'my-options',
  ) );

  GP::createSection( $prefix, array(
    'title'  => 'General',
    'fields' => array(
      array( 'id' => 'site_note', 'type' => 'text', 'title' => 'Site note' ),
    ),
  ) );

  // read it back
  $options = get_option( 'my_options' );
  echo esc_html( $options['site_note'] ?? '' );
}
```

**Classes**

| Method | Purpose |
| --- | --- |
| `GP::createOptions( $id, $args )` | Register an admin options page. `$id` is the option name the values are saved under. |
| `GP::createSection( $id, $section )` | Add a tab (`title`, `icon`, `class`, `fields`) to an options page or metabox |
| `GP::createMetabox( $id, $args )` | Register a metabox. `$id` is the post meta key. |
| `GP::field( $field, $value, $unique, $where )` | Render a single field |

Common `createOptions` arguments: `framework_title`, `menu_title`, `menu_slug`, `menu_type` (`menu` or a submenu type), `menu_capability` (default `manage_options`), `menu_icon`, `menu_position`, `show_bar_menu`, `ajax_save`, `footer_text`, `theme`, `defaults`, `enqueue_webfont` and `output_css`.

Common `createMetabox` arguments: `title`, `post_type`, `data_type` (`serialize`: one array in one meta row; `unserialize`: one meta row per field), `context`, `priority`, `capability` (default `edit_post`), `theme`, `nav`, `class`, `defaults`, `page_templates`, `post_formats` and `rest` (expose in the REST API).

**Functions**

| Function | Purpose |
| --- | --- |
| `gp_place`, `gp_option` | Read a site option and register its field (`gp_place` adds line breaks) |
| `gp_page`, `gp_page_option` | Read a page value and register its field |
| `gp_repeater( $group, $type )` | Mark a page group as a repeater section |
| `gp_args( $key, $args )` | Store or read extra field arguments such as `options` |
| `gp_get_option`, `gp_set_option`, `gp_get_meta` | Read and write the stored values |
| `gp_field_type( $type )` | Translate a legacy or alias type name; unknown becomes `text` |
| `gp_reset_url( $what )` | Nonce-protected URL that clears the field registry (`options` or `pages`) |
| `gp_tokens_replace( $html, $post_id )` | Render placeholders, loops and conditions in a string |
| `gp_tokens_parse( $html )` | List the placeholders found in a string |
| `gp_info()` | Name, version, release and documentation URL from `README.md` |

**Constants**

| Constant | Value |
| --- | --- |
| `GP_VERSION` | Version from `README.md` |
| `GP_OPTION` | `_gp_options` |
| `GP_META` | `gp_meta` |
| `GP_ACTIVE_FRAMEWORK`, `GP_ACTIVE_METABOX`, `GP_ACTIVE_TOKENS` | Set to `false` to switch a module off |

**Filters and actions**

| Hook | Purpose |
| --- | --- |
| `gp_framework_override` | Name of the override folder (default `gp-framework-override`) |
| `gp_get_option` / `gp_set_option` | Change a site option as it is read or written |
| `gp_metabox_post_types` | Post types that get the GP Pages box (default `page`) |
| `gp_token_paths` | Folders scanned for placeholders (default `templates`, `parts`, `patterns`) |
| `gp_token_type_aliases`, `gp_legacy_field_types` | Extra type name aliases |
| `gp_{id}_args`, `gp_{id}_sections` | Change the arguments or sections of an options page or metabox |
| `gp_{id}_save` | Change the data just before it is saved |
| `gp_{id}_save_before`, `gp_{id}_save_after` | Actions around a save |
| `gp_enqueue_assets` | Force the framework scripts and styles on or off for a screen |
| `gp_loaded` | Action after the framework is set up |
