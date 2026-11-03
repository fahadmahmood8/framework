# Installation

The fastest start is a starter theme: upload its zip, activate it, and open one page.

**Starter theme (recommended)**

1. In wp-admin go to Appearance > Themes > Add New > Upload Theme.
2. Upload `guava-pattern-block-starter-theme-2.0.0.zip` (block theme) or `guava-pattern-starter-theme-2.0.0.zip` (classic theme), then activate.
3. For the classic starter's hero options, set a static front page in Settings > Reading.
4. Open the front page and one normal page. This registers their placeholders.
5. Open **Guava Pattern** in wp-admin, and edit a page to see the **GP Pages** box.

The block starter and the classic starter are separate themes. WordPress treats a theme with `templates/index.html` as a block theme, so the two styles cannot share one theme.

**Framework in your own theme**

1. Unzip `guava-pattern-2.0.0.zip` and copy its contents into your theme folder.
2. Add one line to your theme's `functions.php`:

```php
require_once get_template_directory() . '/functions-extended.php';
```

3. Use `gp_place()`, `gp_page()` or `{{gp:...}}` placeholders in your templates.

The framework folder is not a theme by itself: it has no `style.css` or templates.

**Optional constants** (define them in `wp-config.php` or before the `require_once`)

| Constant | Effect |
| --- | --- |
| `LIVE` | `true` on a live server, `false` for a local sub-folder install. Defaults to `false`. |
| `GP_ACTIVE_FRAMEWORK` | `false` turns off the admin options panel |
| `GP_ACTIVE_METABOX` | `false` turns off the page metabox |
| `GP_ACTIVE_TOKENS` | `false` turns off `{{gp:...}}` placeholder scanning |
