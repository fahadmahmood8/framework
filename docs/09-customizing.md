# Customizing

Change the framework by adding files to the override folder, never by editing `gp-framework/` itself, so the core stays upgradeable.

**Override folder.** Create `gp-framework-override/` in your theme (or child theme) root and put a file there at the same relative path it has inside `gp-framework/`. The override is loaded instead of the core file. Examples:

| To change | Add this file |
| --- | --- |
| How the admin panel is built | `gp-framework-override/config/framework.config.php` |
| How the page metabox is built | `gp-framework-override/config/metabox.config.php` |
| The text field markup | `gp-framework-override/fields/text/text.php` |

Rename the folder with the `gp_framework_override` filter.

**Config files.** `gp-framework/config/framework.config.php` and `metabox.config.php` read the field registry and call `GP::createOptions()`, `GP::createSection()` and `GP::createMetabox()`. Copy one to the override folder to change the menu title, slug, icon, header or the post types the metabox appears on.

**Admin skin.** The panel takes its colour from the site option `guava_pattern_skin_color` (Appearance tab, default `pink`). Enter any CSS color there.

**Theme assets.** The theme engine loads every `.css` and `.js` file it finds in these folders:

| Folder | Loaded on |
| --- | --- |
| `css/common`, `js/common` | Admin and front end |
| `css/admin`, `js/admin` | Admin screens |
| `css/front`, `js/front` | Front end |
| `css/login`, `js/login` | Login screen |

The shipped `css/admin/style.css` and `js/admin/scripts.js` style the panel and run the repeater buttons (**Add Field**, **Add Fieldset**).

**Assets of the core.** Font Awesome 5 Free is bundled in `gp-framework/assets/vendor/fontawesome`, so the admin makes no CDN request for icons. Google Fonts are not requested unless a theme turns on `enqueue_webfont`.

**Translations.** The text domain is `gp-framework` and the languages live in `gp-framework/languages/`. The bundled `.mo` files are from Codestar and still contain its wording in a few strings.
