# Placeholder tokens for block themes
Write `{{gp:key|type|group}}` in any HTML template, template part or pattern; one page load registers it and the admin fields appear.

```html
<h1>{{gp:hero_title|text|home|Welcome}}</h1>
<p>{{gp.page:subtitle|text|page_options}}</p>
```

| Token | Meaning | Where the field appears |
| --- | --- | --- |
| `{{gp:key\|type\|group}}` | Site option | **Guava Pattern** panel, tab = group |
| `{{gp.page:key\|type\|group}}` | Page option, read from the current page | **GP Pages** box on each page |

The key always comes first. After it come the type, the group and an optional default, **in any order**. These three tokens mean the same thing:

```html
{{gp:hero_image|image|home}}
{{gp:hero_image|home|image}}
{{gp:hero_image|type=image|group=home}}
```

How each part is read:

- **Type:** a part that is a known field type (`text`, `textarea`, `image`, `color`, `select`, `switcher` and so on) or an alias (`img`, `picture`, `colour`, `editor`, `toggle`, `bool`). Default: `text`.
- **Group:** the first part that is not a type. Default: `global_options` for `gp:`, `page_options` for `gp.page:`.
- **Default text:** the next part after the group. It shows until a value is saved: `{{gp:hero_title|text|home|Welcome}}`.
- **Repeater:** add `[]` to the type, as in `text[]`, or write `repeater=true`.

**Named parts** are always unambiguous: `type=`, `group=`, `default=`, `repeater=`. Use them when a group is named like a type (`group=text`) or a default contains a `|` (`default=` takes everything after it).

**Where tokens are found:** the `templates/`, `parts/` and `patterns/` folders of the parent and child theme, plus templates, parts and patterns saved from the Site Editor. Saving one in the Site Editor triggers a rescan on the next request. Change the folders with the `gp_token_paths` filter.

**How values are printed**, by field type: images and uploads through `esc_url()`; colors, numbers, switchers, selects and radios through `esc_attr()`; textareas through `wp_kses_post()` with line breaks kept; everything else through `wp_kses_post()`. Repeater values print as `<ul class="gp-repeater">`.

**Tips**

- Put tokens in text content or in a Custom HTML block. A token inside a block attribute (such as a `style` value) makes the editor flag the block as invalid.
- A bare `{{gp:key}}` reuses the field defined elsewhere with its full spec. Define the type once, in the first place you use it.
- In PHP, `gp_tokens_replace( $html, $post_id )` renders a string the same way.
