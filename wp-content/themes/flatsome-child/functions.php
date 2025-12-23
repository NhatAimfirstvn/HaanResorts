<?php
// Load helpers
foreach (glob(get_stylesheet_directory() . '/inc/helpers/*.php') as $file) {
    require_once $file;
}

// Load hooks
	//---Load actions
foreach (glob(get_stylesheet_directory() . '/inc/hooks/actions/*.php') as $file) {
    require_once $file;
}
	//---Load filters
foreach (glob(get_stylesheet_directory() . '/inc/hooks/filters/*.php') as $file) {
    require_once $file;
}
	//---Load shortcodes
foreach (glob(get_stylesheet_directory() . '/inc/hooks/shortcodes/*.php') as $file) {
    require_once $file;
}
// Load custom-ux-block-flatsome
foreach (glob(get_stylesheet_directory() . '/ux-elements/*.php') as $file) {
    require_once $file;
}
