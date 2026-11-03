# Quick start for classic PHP themes

Call `gp_place()` for site-wide values and `gp_page()` for per-page values; each call registers its field the first time the template loads.

```php
// Site option: appears under Guava Pattern > HOME in wp-admin
echo wp_kses_post( gp_place( 'hero_title', 'text', 'home' ) );

// Page option: appears in the GP Pages box of each page
$subtitle = gp_page( get_the_ID(), 'subtitle', 'text', 'page_options' );

// Repeater: a list of rows, returned as an array
$features = gp_page( get_the_ID(), 'features', 'text', 'Features', true );
gp_repeater( 'Features', 'page' );

foreach ( (array) $features as $feature ) {
    echo '<li>' . esc_html( $feature ) . '</li>';
}
```

| Function | Arguments | Returns |
| --- | --- | --- |
| `gp_place( $key, $type, $group, $args )` | key, field type (default `text`), group = admin tab (default `global_options`), optional extra args such as `array( 'options' => ... )` for a select | the saved value, with line breaks as `<br />` |
| `gp_page( $id, $key, $type, $group, $repeater )` | page ID, key, type, group (default `page_options`), repeater flag | the saved value, or an array for a repeater |
| `gp_repeater( $group, $type )` | group name, post type (default `page`) | marks a whole page group as a repeater section |

How the admin side is built:

- Each **group** becomes a tab. `gp_place( 'hero_title', 'text', 'home' )` puts the field in the HOME tab.
- Tab and field titles are made from the group and key, in capitals (`hero_title` becomes HERO TITLE).
- Saved values are cleaned with `wp_kses_post()` when saved. Still escape on output with `wp_kses_post()` or `esc_html()`, and `esc_url()` for images.
- Images and uploads are saved as a URL string.
