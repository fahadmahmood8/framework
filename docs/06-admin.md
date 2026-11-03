# Admin panel and page metabox
Guava Pattern builds two admin screens from the placeholders it has collected: a site options panel and a page metabox.

**Options panel.** A top-level **Guava Pattern** menu (`?page=guava-pattern`, administrators only) with one tab per group. The header shows the name, version, release and links to the documentation and Font Awesome. Saving uses a normal form post; the buttons are Save, Reset Section and Reset All. On a fresh install, before any placeholder has been collected, the panel shows a Getting Started note.

**GP Pages metabox.** A box on the page edit screen with one tab per page group. A group marked as a repeater adds **Add Fieldset** and **Remove Fieldset** buttons, and fields flagged as repeaters get **Add Field** / **Remove**. By default it appears on pages only; add post types with the `gp_metabox_post_types` filter. It works in both the block editor and the classic editor, checks a nonce and the `edit_post` capability, and cleans values with `wp_kses_post()`. Empty values and empty repeater rows are not saved.

**Where data is stored**

| What | Where | Read it with |
| --- | --- | --- |
| Site option values | option `_gp_options` (one array) | `gp_get_option( $key )` |
| Page values | post meta `gp_meta` (one array per page) | `gp_get_meta( $id, $key )` |
| Field registry, site | options `_gp_core_arr` (type to keys), `_gp_core_groups` (group to keys) |  |
| Field registry, pages | options `_gp_page_arr`, `_gp_page_groups`, `_gp_repeater_groups` |  |
| Extra field args and token defaults | options `gp_args`, `_gp_token_defaults` |  |

`gp_get_option()` and `gp_get_meta()` return `null` when the value is empty, or the default you pass as the last argument. `gp_set_option( $key, $value )` saves one site option from code.

**Resetting the registry.** The registry only grows as templates load. To clear fields that no longer exist in your templates, print one of these URLs from code and open it while logged in as an administrator (they carry a nonce, so typing `?reset_gp` by hand no longer works):

```php
echo gp_reset_url();           // clears the site options registry
echo gp_reset_url( 'pages' );  // clears the page fields registry
```

Saved values are kept; only the list of fields is cleared and then rebuilt on the next page load.
