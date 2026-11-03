# Guava Pattern 2.0.0: what changed

Foundation: Codestar Framework 2.3.1 (GPL-2.0-or-later), renamed to `GP` / `gp_`.
Saved data is unchanged: options stay in `_gp_options`, page options in the `gp_meta` post meta row.

## Kept from earlier releases
- `functions-extended.php` theme engine: `gp_place()`, `gp_page()`, `gp_option()`, `gp_page_option()`,
  `gp_repeater()`, `gp_gallery()`, `gp_video()`, `gp_info()`, shortcodes, login branding, skin colour.
- Dynamic admin panel built from the `_gp_core_arr` / `_gp_core_groups` registry, one tab per group.
- "GP Pages" metabox built from `_gp_page_arr` / `_gp_page_groups` / `_gp_repeater_groups`, with the
  `repeater` flag on text and textarea fields and the `gp_<title>` section classes.
- `gp_get_option()`, `gp_set_option()`, `gp_get_meta()`, `GP_OPTION`, `GP_VERSION`, the
  `gp-framework-override` folder, `?page=guava-pattern`, header with Documentation / Font Awesome links.
- Legacy field types are mapped automatically: `color_picker` -> `color`, `wysiwyg` -> `wp_editor`, `image` -> `upload`.

## New
- New core API: `GP::createOptions()`, `GP::createSection()`, `GP::createMetabox()` and ~40 field types.
- Native metabox on core WordPress APIs (nonce, capability check, block-editor compatible).
- Font Awesome 5 bundled locally (no CDN). Google Fonts are off unless a theme enables them.
- New constants: `GP_META`, `GP_ACTIVE_FRAMEWORK`, `GP_ACTIVE_METABOX`.

## Changed / removed
- Reset links are now administrator-only and nonce-protected: use `gp_reset_url()` / `gp_reset_url('pages')`
  instead of typing `?reset_gp`.
- No PHP sessions; `gp_info()` caches the GitHub README in a transient (12 h).
- PHP 8 fixes (optional-before-required parameters, undefined keys, `end(explode())`); `LIVE` defaults to false.
- Removed: Customizer, TinyMCE shortcode generator and the old `GPFramework` class (WordPress is moving to
  block themes / Site Editor). Codestar promo pages removed.
- `gp-framework/config/*.php` replaces the old duplicated core + override config copies.

## Placeholder tokens (block themes)
Write placeholders in the HTML files of a block theme (`templates/`, `parts/`, `patterns/`, and templates edited
in the Site Editor):

    {{gp:key|type|group}}               site option  -> Guava Pattern admin panel
    {{gp.page:key|type|group}}          page option  -> "GP Pages" metabox
    {{gp:key|type|group|Default text}}  optional default shown until a value is saved
    {{gp.page:items|text[]|Features}}   "[]" after the type = repeater (rendered as <ul class="gp-repeater">)

One page load collects every token into the same registries `gp_place()` / `gp_page()` fill, so the panel and
the metabox are built automatically. At render time each token is replaced by the saved value, escaped by type.
Saving a template in the Site Editor triggers a rescan on the next request. Disable with
`define( 'GP_ACTIVE_TOKENS', false );`. In PHP, `gp_tokens_replace( $html, $post_id )` does the same replacement.
Tip: put tokens in text content or in a `core/html` block, not in block attributes (the editor would flag the block).

### Flexible order and the template parser (2.1)
The key comes first; type, group and default can follow in **any order**. A part is the type when it is a known
field type (or alias: `img`, `colour`, `editor`, `toggle`), the first other part is the group, the next the
default. Named parts are always unambiguous: `{{gp:hero|type=image|group=home|default=Hello | world}}`.
A group that is named like a type must be written `group=text`.

    {{gp:hero_image|image|home}}  =  {{gp:hero_image|home|image}}  =  {{gp:hero_image|type=image|group=home}}

Loops, conditions and loop variables (output is never re-parsed, so saved text that looks like a token stays text):

    {{gp.page:features|Features|text}} <li>{{index}}/{{count}} {{item}}</li> {{/gp.page:features}}
    {{if gp:hero_image|image|home}} <img src="{{gp:hero_image}}"> {{else}} no image {{/if}}
    {{if !gp.page:subtitle|text}} ... {{/if}}      {{if first}} / {{if last}} inside loops
    A variable with a closing tag, or a condition, registers its field too. Unknown {{tags}} are left untouched.
