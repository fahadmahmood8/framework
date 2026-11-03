# Field types

Placeholders accept any field type of the Codestar core; an unknown type name falls back to `text`.

The types available are: accordion, background, backup, border, button_set, callback, checkbox, code_editor, color, color_group, content, date, datetime, dimensions, fieldset, gallery, group, heading, icon, image_select, link, link_color, map, media, notice, number, palette, radio, repeater, select, slider, sortable, sorter, spacing, spinner, subheading, submessage, switcher, tabbed, text, textarea, typography, upload and wp_editor.

**Tested in this release:** text, textarea, color, select, upload (as `image`) and switcher. The rest come from the Codestar 2.3.1 core and render, but were not exercised in the Guava Pattern tests. Typography, map and code_editor load their libraries from a CDN, and only when a field of that type is used.

**Names from earlier releases** are translated automatically, so existing templates keep working:

| Old name | Becomes |
| --- | --- |
| `color_picker` | `color` |
| `wysiwyg` | `wp_editor` |
| `image` | `upload` (saved as a URL string) |

In placeholders you can also use the aliases `img`, `picture`, `colour`, `editor`, `toggle` and `bool`. Add your own with the `gp_token_type_aliases` filter, and your own legacy names with `gp_legacy_field_types`.

**Select, radio and other fields that need options.** Pass the options once, from PHP, with `gp_args()`; the placeholder itself only names the type:

```php
// in functions.php
gp_args( 'layout', array( 'options' => array( 'a' => 'Alpha', 'b' => 'Beta' ) ) );

// in a PHP template (this also works without the line above)
gp_place( 'layout', 'select', 'home', array( 'options' => array( 'a' => 'Alpha', 'b' => 'Beta' ) ) );
```

```html
{{gp:layout|select|home}}
```

The `repeater` flag works on `text` and `textarea` fields. For a full list of field options (titles, descriptions, dependencies, defaults), see the Codestar field reference and `gp-framework/samples/admin-options.php`, which uses `GP::` in place of `CSF::`.
