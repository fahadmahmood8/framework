# gp-framework-override

Drop a file here (same relative path as inside `gp-framework/`, e.g. `config/framework.config.php`
or `fields/text/text.php`) and it is loaded instead of the core file. Keep this folder in the theme
root (or child theme); the core stays untouched and upgradeable.
The folder name can be changed with the `gp_framework_override` filter.
