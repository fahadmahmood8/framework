# Overview

Guava Pattern is a WordPress theme framework where you write a placeholder in a template, load the page once, and the admin screen for that field builds itself.

How a field comes to life:

1. Put a placeholder in a template: `gp_place('hero_title', 'text', 'home')` in PHP, or `{{gp:hero_title|text|home}}` in a block theme's HTML.
2. Load any page once. The framework collects the placeholder into its registry.
3. Open **Guava Pattern** in wp-admin for site options, or edit a page and use the **GP Pages** box for page options. The field is already there.
4. Save a value. The template now shows it.

Version 2.0.0 is built on the Codestar Framework 2.3.1 (GPL-2.0-or-later), renamed to `GP` / `gp_`. It declares PHP 7.4+ and WordPress 6.2+ for the block starter; it was tested on PHP 8.3 only.

The package comes as three zips:

| File | What it is |
| --- | --- |
| `guava-pattern-2.0.0.zip` | The framework folder: `gp-framework/`, `functions-extended.php`, `css/`, `js/`, `fonts/`, `addons/` |
| `guava-pattern-starter-theme-2.0.0.zip` | Classic PHP starter theme (`gp_place()` and `gp_page()` in templates) |
| `guava-pattern-block-starter-theme-2.0.0.zip` | Block starter theme (`{{gp:...}}` placeholders in HTML templates) |
