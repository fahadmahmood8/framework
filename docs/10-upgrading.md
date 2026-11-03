# Upgrading from earlier releases

Existing sites keep their data: options stay in `_gp_options` and page values in `gp_meta`, and the theme helpers you already call still work.

| Area | What happens in 2.0 |
| --- | --- |
| Theme engine | Kept: `gp_place()`, `gp_page()`, `gp_option()`, `gp_page_option()`, `gp_repeater()`, gallery, video, slider and image helpers, shortcodes, login branding, skin colour |
| Admin panel and metabox | Kept, rebuilt on the new core. Still built from the field registry, one tab per group |
| Repeater fields and `gp_<title>` section classes | Kept |
| Legacy field type names | Translated automatically (`color_picker`, `wysiwyg`, `image`) |
| Core | New: Codestar Framework 2.3.1 renamed to `GP` / `gp_`, with about 40 field types |
| Metabox | New: built on core WordPress APIs, block-editor compatible |
| Font Awesome | New: bundled locally, no CDN |
| Placeholder tokens, loops, conditions | New: `{{gp:key\|type\|group}}` for block themes |
| Reset links | Changed: administrators only and nonce-protected, use `gp_reset_url()` instead of typing `?reset_gp` |
| PHP sessions | Removed. `gp_info()` caches the GitHub README in a transient for 12 hours |
| PHP 8 compatibility | Fixed several warnings and deprecations in the theme engine. `LIVE` now defaults to `false` |
| Customizer | Removed |
| TinyMCE shortcode generator | Removed |
| `GPFramework` class and the old `gp_framework_*` loader | Removed. Use `GP::createOptions()` and friends |
| Old duplicate `gp-framework-override/` configs | Replaced by `gp-framework/config/`. Put your own copies in `gp-framework-override/` |

**Steps**

1. Back up the database and the theme folder.
2. Replace the old `gp-framework/` folder, `functions-extended.php`, and the `css/`, `js/` and `addons/` folders with the new ones. Remove the old `gp-framework-override/` configs unless you changed them on purpose.
3. Open the front end and a page once, then check **Guava Pattern** and a page's **GP Pages** box.
4. Search your theme for `GPFramework`, `CSF::` and `?reset_gp`, which no longer exist.
