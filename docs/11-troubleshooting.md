# Troubleshooting and known limits

Most problems come from a placeholder that was never loaded once, or from a stale field registry.

| Symptom | Cause and fix |
| --- | --- |
| A field does not appear in the panel | The placeholder has not been loaded yet. Open the front end page (or a page that uses it) once, then reload the panel. |
| A field I removed is still in the panel | The registry only grows. Use `gp_reset_url()` (or `gp_reset_url( 'pages' )`) and load a page again. |
| A field is in the wrong tab or has the wrong type | The first registration of a key wins. Fix it where the key is first used, reset the registry and reload. |
| A token prints as text with the braces | The key has an invalid character (only letters, numbers, `_` and `-` are allowed) or the tag is not a Guava Pattern tag. |
| The editor says a block is invalid | A token sits inside a block attribute. Move it into text content or a Custom HTML block. |
| A token's group is read as its type, or the reverse | A group named like a type. Write `group=name` or `type=name`. |
| `?reset_gp` does nothing | It needs an administrator login and a nonce now. Use `gp_reset_url()`. |
| The Customizer or shortcode generator is missing | They were removed in 2.0. See the upgrade notes. |

**Known limits**

- Tested on one install: the latest WordPress development version, PHP 8.3, SQLite. Not tested on PHP 7.4 or 8.0, a normal MySQL database or multisite.
- Only some field types were exercised (see Field types). The rest come from the Codestar 2.3.1 core unchanged.
- Typography, map and code_editor fields load libraries from a CDN, and only when used.
- The block placeholders print into rendered block output. They are not previewed in the Site Editor, which shows the raw text.
- `functions-extended.php` is the older theme engine with targeted fixes, not a rewrite, and has not had a full security audit.
- Placeholders cannot carry field options such as a select's choices; pass those with `gp_args()`.

**Credits.** The options engine is the Codestar Framework 2.3.1, Copyright 2015-2022 Codestar, licensed GPL-2.0-or-later. Font Awesome Free 5.15.4 is bundled under its own license (`gp-framework/assets/vendor/fontawesome/LICENSE.txt`).
