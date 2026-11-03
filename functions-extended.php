<?php

	// Guava Pattern 2.0.0 -- theme engine. Loads the GP framework (gp-framework/) and registers the
	// theme helpers: gp_place(), gp_page(), gp_option(), gp_gallery() and friends.
	if ( ! defined( 'ABSPATH' ) ) { die; }

	// LIVE switches the path handling below between a live server and a local sub-folder install.
	// Define it in wp-config.php (true/false); it defaults to false so a missing constant is not fatal.
	defined('LIVE') or define('LIVE', false);

	//http://getbootstrap.com/css/
	//http://guavapattern.com/documentation/#configuration
	//define( 'GP_ACTIVE_FRAMEWORK',  false ); // default true: the admin options panel
	//define( 'GP_ACTIVE_METABOX',    false ); // default true: the page options metabox
	require_once dirname( __FILE__ ) .'/gp-framework/gp-framework.php';
	
	define('GP_GO', false);
	
	define('GP_THEME', (is_child_theme()?get_stylesheet_directory_uri():get_template_directory_uri()).'/');
	define('GP_IMAGES', GP_THEME.'images/');
	//echo GP_THEME;exit;
	global 
			$_gp_on, 
			$_gp_absolute, 
			$_gp_relative, 
			$_gp_enqueue, 
			$gp_theme, 
			$_gp_core_arr, 
			$_gp_core_groups, 

			$_gp_page_arr, 
			$_gp_page_groups, 
			
			$_gp_repeater_groups, 
			
			$gp_addons, 
			$_gp_unique;	
	
	$_gp_unique = array();
	
	$domain = explode('/', $_SERVER['PHP_SELF']);
	$domain = array_filter($domain, 'strlen');
	
	array_pop($domain);
	
	if(is_admin() && LIVE)
	array_pop($domain);
	
	
	
	if(LIVE)
	define('RELATIVE', $_SERVER['DOCUMENT_ROOT'].'/'.implode('/', $domain));
	else
	define('RELATIVE', $_SERVER['DOCUMENT_ROOT'].(is_admin()?current($domain):implode('/', $domain)));
	
	
	
	$_gp_on = (isset($_GET['page']) && $_GET['page']=='guava-pattern');
	
	function _gp_admin_header_scripts(){
		global $_gp_on;
		
		$guava_pattern_skin_color = gp_place('guava_pattern_skin_color');
		$guava_pattern_skin_color = ($guava_pattern_skin_color!=''?$guava_pattern_skin_color:'pink');
	?>
	<script type="text/javascript" language="javascript">
	</script>
	<style type="text/css">
	.toplevel_page_guava-pattern{
	}
	#gp-tab-donate_section,
	.toplevel_page_guava-pattern .update-nag{
		display:none !important;
	}
	.toplevel_page_guava-pattern .woocommerce-message{
		display:none;
	}
	
	.gp-toolbar-item a{

		font-weight:bold !important;

		color:<?php echo $guava_pattern_skin_color; ?> !important;

	}
	.toplevel_page_guava-pattern #wpcontent, .toplevel_page_guava-pattern #wpfooter, .toplevel_page_guava-pattern #wpwrap {

		background-color: <?php echo $guava_pattern_skin_color; ?>;

	}

	.gp-options .gp-content .gp-section-title,

	.current.menu-top.menu-icon-generic.toplevel_page_guava-pattern.menu-top-first.menu-top-last{

		background-color: <?php echo $guava_pattern_skin_color; ?> !important;

		color:black !important;

		font-weight:bold !important;

	}
	.gp-header h1{

		color:<?php echo $guava_pattern_skin_color; ?> !important;

	}

	.wp-docs:hover,

	.wp-fa:hover{ color:<?php echo $guava_pattern_skin_color; ?>; }					
	</style>
	<?php	
	}
	add_action('admin_head', '_gp_admin_header_scripts');

	function register_gp_menus() {
		register_nav_menus(
			array(
			  'top-menu' => __( 'Top Menu' ),
			  'footer-menu' => __( 'Footer Menu' )
			)
		);
	}

	function gp_slug($str){
		return str_replace(array(' '), '', strtolower($str));
	}
	function gp_tax_slug($arr){
		$arr = (array)$arr;
		$arr = (array)current($arr);
		return $arr['slug'];
	}
	
	function gp_init() {
		
		// init_sessions() is no longer called: PHP sessions break page caching and the REST API.
		
		global $_gp_absolute, $_gp_relative, $gp_theme, $gp_addons;
		
		$wp_url = get_bloginfo('wpurl');
		$template_directory = is_child_theme()?get_stylesheet_directory():get_template_directory();
		$relative = explode('/wp-content/', $template_directory);
		//pree($template_directory);exit;
		//pree($relative);exit;
		
		$relative = ($relative[0]==RELATIVE?RELATIVE:$relative[0]);
		$relative = str_replace('\\', '/', $relative);
		$gp_theme = str_replace('\\', '/', $template_directory);
		$gp_addons = $gp_theme.'/addons/';
		//echo $relative.'<br />'.
		//pree($wp_url);//exit;
		$_gp_absolute = str_replace($relative, $wp_url.(LIVE?'/':''), $gp_theme);
		//pree($relative);
		//pree($wp_url);
		//pree($gp_theme);
		//pree($_gp_absolute);exit;
		$_gp_relative = $relative;
		
		
		register_gp_menus();
		
		gp_add_taxonomies_to_pages();
		
		//pree($_gp_absolute);exit;
		//pree($gp_theme);exit;
		
		if(!is_admin() && GP_GO){
		
			$dir_html = $gp_theme.'/templates/html';
			$dir_html = glob($dir_html.'/*.html');
			foreach ($dir_html as $filename) {
				
				 $slug = basename($filename);
				 $slug = explode('.', $slug);
				 array_pop($slug);
				 $content = '<?php
						/**
						 * Template Name: '.gp_humanize(implode('-', $slug)).'
						 */
						 
				?>';
				//echo $filename.'<br />';
				//echo file_get_contents($filename).'<br />';exit;
				$content .= file_get_contents($filename);
				
				$filename = 'gp-'.implode('-', $slug).'.php';
				$tpl_file = $gp_theme.'/'.$filename;
				if(!file_exists($tpl_file)){
					$f = fopen($tpl_file, 'w+');
					fwrite($f, $content);
					fclose($f);
				}
			}
		}
		
	}	
	
	add_action( 'init', 'gp_init' );	
	
	function gp_slider($slider_id){
		//echo $slider_id;
		$shorcode = '[gsp ids="'.($slider_id).'"]';
		echo '<div class="slider-sec clearfix">'.do_shortcode($shorcode).'</div>';
	}
	
	if(!function_exists('pre')){
	function pre($data){
			if(isset($_GET['debug'])){
				pree($data);
			}
		}	 
	} 	
	if(!function_exists('pree')){
	function pree($data){
				echo '<pre>';
				print_r($data);
				echo '</pre>';	
		
		}	 
	} 
	
	function gp_add_taxonomies_to_pages() {
		  register_taxonomy_for_object_type( 'post_tag', 'page' );
		  register_taxonomy_for_object_type( 'category', 'page' );
	  } 
	
	
	
	function gp_login_scripts(){
		gp_custom_scripts('js/login');	
		gp_custom_scripts('css/login');	
	}
	
	function gp_scripts(){
		
		gp_custom_scripts('js/common');
		gp_custom_scripts('css/common');
		
		if(is_admin()){
			gp_custom_scripts('js/admin');	
			gp_custom_scripts('css/admin');	
			
		}else{
			gp_custom_scripts('js/front');	
			gp_custom_scripts('css/front');
			gp_custom_scripts('addons/gp-gallery');
			wp_localize_script( 
			'gp-custom.js', 
			'gp',
			array('wp_url' => get_bloginfo('url')) );	
			if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
				wp_enqueue_script( 'comment-reply' );
			}	
		}
	}
	
	if(is_admin())	
	add_action('admin_enqueue_scripts', 'gp_scripts');
	else
	add_action('wp_enqueue_scripts', 'gp_scripts');
	
	if(basename($_SERVER['PHP_SELF'])=='wp-login.php')
	add_action( 'login_enqueue_scripts', 'gp_login_scripts' );
	
	
	function is_paged_script($str){
		
		$pref = 'page-';
		
		$page = str_replace(array($pref), '', $str);
		
		$this_page = get_post_meta(get_the_ID(), 'css', true);
		
		$this_page = ($this_page!=''?$this_page:time());
		
		$is_paged = (substr($str, 0, strlen($pref))==$pref);
		
		$has_css = (substr($page, 0, strlen($this_page))==$this_page);
		
		//pree($is_paged.' > '.$has_css.' > '.$this_page.' > '.!$is_paged);
		
		$ret = (($is_paged && $has_css) || !$is_paged);
		
		return $ret;
	}
	
	function gp_numeric($string){
		return preg_replace("/[^0-9]/", "", $string);
	}
	
	function gp_unique($item){
		return ($item.'-'.rand(0,99));
	}
	
	function gp_custom_scripts($dir){
		global $_gp_absolute, $gp_theme, $_gp_unique;
		//pree($_gp_absolute);
		//pree($gp_theme);
		//pree($dir);
		//exit;
		$dir_rel = $gp_theme.'/'.$dir;
		
		$css_dir = glob($dir_rel.'/*.css');
		$js_dir = glob($dir_rel.'/*.js');
		//pree($js_dir);
		foreach ($css_dir as $filename) {
			
			 $slug = basename($filename);
			 $ext = pathinfo($slug, PATHINFO_EXTENSION);
			 
			 if(!array_key_exists($ext, $_gp_unique))
			 $_gp_unique[$ext] = array();
			 
			 $slug_id = str_replace(array('.'.$ext), '', $slug);
			 
			 if(in_array($slug_id, $_gp_unique[$ext]))
			 $slug_id = gp_unique($slug_id);
			 
			 $_gp_unique[$ext][] = $slug_id;
			 
			 //$LOADER_ARRAY['head']['css'][]  = $filename;
			
			 if(is_paged_script($slug))
			 wp_enqueue_style( 'gp-'.$slug_id, $_gp_absolute.'/'.$dir.'/'.$slug, array(), date('YmdHi') );
		}
		
		//LOADING SCRIPTS
		foreach ($js_dir as $filename) {
			 $slug = basename($filename);
			 $ext = pathinfo($slug, PATHINFO_EXTENSION);
			 //pree($ext);
			 if(!array_key_exists($ext, $_gp_unique))
			 $_gp_unique[$ext] = array();			 
			 
			 //$LOADER_ARRAY['head']['js'][]  = $filename;
			 $slug_id = str_replace(array('.'.$ext), '', $slug);
			 
			 //pree($slug_id.' > ');
			 //pree($_gp_unique);
			// pree(in_array($slug_id, $_gp_unique));
			 
			 if(in_array($slug_id, $_gp_unique[$ext]))
			 $slug_id = gp_unique($slug_id);
			 
			 $_gp_unique[$ext][] = $slug_id;
			 
			 			 
			 $to_enqueue = $_gp_absolute.'/'.$dir.'/'.$slug;
			 
			 //pree($to_enqueue);
			 //pree($slug_id);
			 //pree($slug);
			 
			 if(is_paged_script($slug)){
				 //pree($to_enqueue);
				 wp_enqueue_script( 'gp-'.$slug_id, $to_enqueue, array('jquery'), date('YmdH'), true );
			 }else{
				 //pree($to_enqueue);
			 }
			 
	
	
		}			//exit;
		
		//pree($_gp_unique);
	}
	
	function gp_image($id, $size='full'){
		$attachment = wp_get_attachment_image_src( $id, $size );
		return (!empty($attachment)?current($attachment):'');
	}
	
	function gp_post_image($id, $size = 'thumbnail', $array = false){
		$feat_image = wp_get_attachment_image_src( get_post_thumbnail_id( $id ), $size );
		if($array)
		return $feat_image;
		else
		return (!is_array($feat_image)?'':current($feat_image));
	}

	function gp_page($id=0, $key='', $type='text', $group='page_options', $repeater=false){
		
		
		$value = gp_page_option($id, $key, $type, $group, $repeater);
		return is_array($value)?$value:nl2br((string)$value); // repeater fields come back as arrays
	}
	function gp_page_shortcode($atts=array(), $content=''){
		$content = gp_place($atts['key'], $atts['type'], $atts['group']);
		return $content;
	}
	add_shortcode('gp_page', 'gp_page_shortcode');
		
	function gp_place($key, $type='text', $group='global_options', $args=array()){
		$value = gp_option($key, $type, $group, $args);
		return is_array($value)?$value:nl2br((string)$value);
	}
	function gp_place_shortcode($atts=array(), $content=''){
		$content = gp_place($atts['key'], $atts['type'], $atts['group']);
		return $content;
	}
	
	add_shortcode('gp_place', 'gp_place_shortcode');
	
	
	add_shortcode('gp_gallery', 'gp_gallery_shortcode');
	
	function gp_args($key, $args=array()){
		$gp_args = get_option('gp_args', array());
		if(!empty($args)){			
			$gp_args[$key] = $args;
			update_option('gp_args', $gp_args);
			
		}
		$gp_args = isset($gp_args[$key])?$gp_args[$key]:array();
		//pree($gp_args);
		return $gp_args;
	}
	
	function gp_option($key, $type='text', $group='global_options', $args=array()){
		global $_gp_core_arr, $_gp_core_groups;
		$_gp_core_arr[$type][] = $key;
		$_gp_core_groups[$group][] = $key;
		
		//WILL WORK FOR ALL FIELDS AND EXPECTED OPTIONS IN FUTURE
		if(!empty($args))
		gp_args($key, $args);
		
		//pree($_gp_core_arr);
		return gp_get_option($key);
	}
	
	function gp_page_option($id, $key, $type='text', $group='page_options', $repeater=false){
		global $_gp_page_arr, $_gp_page_groups;
		$_gp_page_arr[$type][] = array('key'=>$key, 'repeater'=>$repeater);
		$_gp_page_groups[$group][] = $key;
		//pree($_gp_page_arr);
		//pre($_gp_page_arr);$repeater
		return gp_get_meta($id, $key);
	}
		
		
	add_action( 'admin_init', 'gp_posts_order' ); 
	
	function gp_posts_order() 
	{
		
		$gp_core = gp_place('update_core');//exit;
		if($gp_core){
			gp_core_update();
			gp_remove('update_core');

			//exit;
		}
		
		
		add_post_type_support( 'post', 'page-attributes' );
		add_theme_support( 'post-thumbnails' ); 
		
		if(!empty($_POST)){
			//pree($_POST);exit;
			if(isset($_POST['_gp_options'])){
				$valid_keys = array_keys($_POST['_gp_options']);
				//pree($valid_keys);
				$_gp_core_arr_db = get_option('_gp_core_arr');
				if(!empty($_gp_core_arr_db)){
					$updated_gp_core_arr = array();
					$unique_check = array();
					foreach($_gp_core_arr_db as $type=>$fields){
						foreach($fields as $field){
							if(in_array($field, $valid_keys) && !in_array($field, $unique_check)){
								$updated_gp_core_arr[$type][] = $field;
								$unique_check[] = $field;
							}
						}						
					}
					//pree($updated_gp_core_arr);
					update_option('_gp_core_arr', $updated_gp_core_arr);
				}
				
				
			}
			//exit;
		}
	}	
	
	function toolbar_link_to_gpAdmin( $wp_admin_bar ) {
		$gp_info = gp_info();
		$args = array(
			'id'    => 'gp_panel',
			'title' => gp_humanize($gp_info['name']),
			'href'  => get_bloginfo('wpurl').'/wp-admin/admin.php?page=guava-pattern',
			'meta'  => array( 'class' => 'gp-toolbar-item' )
		);
		$wp_admin_bar->add_node( $args );
	}

	add_action( 'admin_bar_menu', 'toolbar_link_to_gpAdmin', 999 );	
	
	function gp_image_code($atts, $content = ""){
		$atts = shortcode_atts( array(
			'page' => '',
			'size' => 'full'
		), $atts);
		$img = gp_image(gp_place('gp_'.$atts['page'], 'image'), $atts['size']);
		$img = ($img!=''?'<div class="'.$atts['page'].'"><img src="'.$img.'" /></div>':'');
		return $img;		
	}
	add_shortcode('gp_image', 'gp_image_code');
		
	function gp_info($type='current'){
		$ret = array();

		$file = dirname( __FILE__ ) .'/README.md';

		$ret['current'] = gp_readme($file);

		// the upcoming release info is fetched from GitHub at most once every 12 hours
		$upcoming = function_exists('get_transient') ? get_transient('gp_info_upcoming') : false;

		if(!is_array($upcoming)){

			$master = 'https://raw.githubusercontent.com/fahadmahmood8/guava-pattern/master/README.md';

			$upcoming = gp_readme($master);

			if(function_exists('set_transient'))
			set_transient('gp_info_upcoming', $upcoming, 12 * HOUR_IN_SECONDS);
		}

		$ret['upcoming'] = $upcoming;

		if(isset($ret[$type]))
		$ret = $ret[$type];

		return $ret;
	}


	function gp_readme($file){
		$ret = array();

		if(preg_match('#^https?://#i', $file)){
			if(!function_exists('wp_remote_get'))
			return $ret;
			$response = wp_remote_get($file, array('timeout'=>3));
			$file = is_wp_error($response) ? '' : wp_remote_retrieve_body($response);
		}else{
			$file = is_readable($file) ? file_get_contents($file) : '';
		}

		$arr = preg_split('/\r\n|\r|\n/', (string)$file);
		$arr = array_filter(array_map('trim', $arr));
		if(!empty($arr)){
			foreach($arr as $k=>$v){
				if(strpos($v, ':')===false)
				continue;
				$elems = explode(':', $v);
				$key = current($elems);
				array_shift($elems);
				$val = implode(':', $elems);
				$ret[$key] = $val;
			}
		}
		return $ret;
	}

	if ( ! function_exists( 'gp_theme_comment_nav' ) ) :
	
	
	function gp_theme_comment_nav() {
		// Are there comments to navigate through?
		if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) :
		?>
		<nav class="navigation comment-navigation" role="navigation">
			<h2 class="screen-reader-text"><?php _e( 'Comment navigation', 'gp_theme' ); ?></h2>
			<div class="nav-links">
				<?php
					if ( $prev_link = get_previous_comments_link( __( 'Older Comments', 'gp_theme' ) ) ) :
						printf( '<div class="nav-previous">%s</div>', $prev_link );
					endif;
	
					if ( $next_link = get_next_comments_link( __( 'Newer Comments', 'gp_theme' ) ) ) :
						printf( '<div class="nav-next">%s</div>', $next_link );
					endif;
				?>
			</div><!-- .nav-links -->
		</nav><!-- .comment-navigation -->
		<?php
		endif;
	}
	endif;	
	
	function gp_remove($key){
		$_gp_core_arr_db = get_option('_gp_core_arr');
		$_gp_core_arr_db = remove_element_by_value($_gp_core_arr_db, $key);
		update_option('_gp_core_arr', $_gp_core_arr_db);
		$options = get_option( GP_OPTION );
		if(isset($options[$key])){
			unset($options[$key]);
			update_option(GP_OPTION, $options);
		}
		//pree($_gp_core_arr_db);exit;
	}
	
	function gp_shutdown(){
	
		if(is_admin())
		return;
	
		global 
				$_gp_core_arr, 
				$_gp_core_groups,
				
				$_gp_page_arr, 
				$_gp_page_groups,
				
				$_gp_repeater_groups;
		
		$id = get_the_ID();
		
		$_gp_core_arr_db = get_option('_gp_core_arr');
				
		$_gp_page_arr_db = get_option('_gp_page_arr');
		
		
		//pree($_gp_page_arr_db);
		
		//pree($_gp_core_arr);
		//pree($_gp_core_groups);
		//pree($_gp_core_arr_db);
		
		$_gp_core_arr_db = (is_array($_gp_core_arr_db)?$_gp_core_arr_db:array());
		
		$_gp_page_arr_db = (is_array($_gp_page_arr_db)?$_gp_page_arr_db:array());
		
		$_gp_repeater_groups_db = get_option('_gp_repeater_groups');
		$_gp_repeater_groups_db = (is_array($_gp_repeater_groups_db)?$_gp_repeater_groups_db:array());
		$_gp_repeater_groups = (is_array($_gp_repeater_groups)?$_gp_repeater_groups:array());
		
		//pree($_gp_page_arr_db);
				
		
		if(!empty($_gp_core_arr)){
			foreach($_gp_core_arr as $type=>$arr){
				$_gp_core_arr_db[$type] = (isset($_gp_core_arr_db[$type]) && is_array($_gp_core_arr_db[$type])?$_gp_core_arr_db[$type]:array());
				if(!empty($arr)){
					foreach($arr as $k){
						if(!in_array($k, $_gp_core_arr_db[$type])){
							$_gp_core_arr_db[$type][] = $k;
						}
					}
				}
			}
		}
		
		//pree($_gp_core_arr_db);exit;
		//$_gp_core_arr_db = array();
		
		update_option('_gp_core_arr', $_gp_core_arr_db);
		
	

		if(!empty($_gp_page_arr)){
			foreach($_gp_page_arr as $type=>$arr){
				$_gp_page_arr_db[$type] = (isset($_gp_page_arr_db[$type]) && is_array($_gp_page_arr_db[$type])?$_gp_page_arr_db[$type]:array());
				if(!empty($arr)){
					foreach($arr as $k){
						if(!in_array($k, $_gp_page_arr_db[$type])){
							$_gp_page_arr_db[$type][] = $k;
						}
					}
				}
			}
		}
		
		//pree($_gp_page_arr_db);exit;
		//$_gp_page_arr_db = array();
		
		update_option('_gp_page_arr', $_gp_page_arr_db);			
	
		
		
		
		$_gp_core_groups_db = get_option('_gp_core_groups');
		
		
		$_gp_core_groups_db = (is_array($_gp_core_groups_db)?$_gp_core_groups_db:array());		
		
		
		if(!empty($_gp_core_groups)){
			$updated_keys = array();
			foreach($_gp_core_groups as $group=>$arr){			
				//pree($group);
				//pree($arr);	
				if(!empty($arr)){
					foreach($arr as $k){
						if(!isset($_gp_core_groups_db[$group])){
							$_gp_core_groups_db[$group] = array();
						}
						if(isset($_gp_core_groups_db[$group]) && !in_array($k, $_gp_core_groups_db[$group])){
							$_gp_core_groups_db = remove_element_by_value($_gp_core_groups_db, $k);
							$_gp_core_groups_db[$group][] = $k;
						}
					}
				}
			}
		}
		//pree($_gp_core_groups_db);
		update_option('_gp_core_groups', $_gp_core_groups_db);		
		
		

	
		$_gp_page_groups_db = get_option('_gp_page_groups');
		
		
		$_gp_page_groups_db = (is_array($_gp_page_groups_db)?$_gp_page_groups_db:array());		
		
		
		if(!empty($_gp_page_groups)){
			$updated_keys = array();
			foreach($_gp_page_groups as $group=>$arr){			
				//pree($group);
				//pree($arr);	
				if(!empty($arr)){
					foreach($arr as $k){
						if(!isset($_gp_page_groups_db[$group])){
							$_gp_page_groups_db[$group] = array();
						}
						if(isset($_gp_page_groups_db[$group]) && !in_array($k, $_gp_page_groups_db[$group])){
							$_gp_page_groups_db = remove_element_by_value($_gp_page_groups_db, $k);
							$_gp_page_groups_db[$group][] = $k;
						}
					}
				}
			}
		}
		//pree($_gp_page_groups_db);
		update_option('_gp_page_groups', $_gp_page_groups_db);	
		

		if(!empty($_gp_repeater_groups)){
			foreach($_gp_repeater_groups as $type=>$arr){
				$_gp_repeater_groups_db[$type] = (isset($_gp_repeater_groups_db[$type]) && is_array($_gp_repeater_groups_db[$type])?$_gp_repeater_groups_db[$type]:array());
				if(!empty($arr)){
					foreach($arr as $k){
						if(!in_array($k, $_gp_repeater_groups_db[$type])){
							$_gp_repeater_groups_db[$type][] = $k;
						}
					}
				}
			}
		}
		
		//pree($_gp_repeater_groups_db);exit;
		
		//pree($_gp_repeater_groups_db);
		if(!empty($_gp_repeater_groups_db))
		update_option('_gp_repeater_groups', $_gp_repeater_groups_db);
		//pree(get_option('_gp_repeater_groups'));
									
	}
	
	function remove_element_by_value($arr, $val){
		$return = array(); 
		foreach($arr as $k => $v) {
		  if(is_array($v)) {
			 $return[$k] = remove_element_by_value($v, $val); //recursion
			 continue;
		  }
		  if($v == $val) continue;


		  $return[$k] = $v;
		}
		return $return;
	}
	
	if(!is_admin())
	add_action('shutdown', 'gp_shutdown');


	function gp_underscore($str){
		return str_replace(array('-', '_', '.', ',', ' '), '_', strtolower($str));
	}
	
	function gp_humanize($str){
		return ucwords(str_replace(array('-', '_', '.', ','), ' ', $str));
	}
	
	function gp_capital($str){
		return strtoupper(gp_humanize($str));
	}
	
	
	function gp_admin_scripts(){
?>
	<script type="text/javascript" language="javascript">
		jQuery(document).ready(function($){
			$('#page_template').find('option[value="functions.php"]').remove();
			$('#page_template').find('option[value="functions-extended.php"]').remove();
			
		});
	</script>
    <style type="text/css">
		.toplevel_page_guava-pattern #message{
			display:none;
		}
	</style>
<?php		
	}
	
	add_action('admin_footer', 'gp_admin_scripts');		
	
	function gp_taxonomy_terms($taxonomies, $arr=array()){
		$category = get_term_by('slug', $taxonomies[1], $taxonomies[0]);
		
		$args = array(
			'orderby'           => 'name', 
			'order'             => 'ASC',
			'hide_empty'        => false, 
			'exclude'           => array(), 
			'exclude_tree'      => array(), 
			'include'           => array(),
			'number'            => '', 
			'fields'            => 'all', 
			'slug'              => '',
			'parent'            => '',
			'hierarchical'      => true, 
			'child_of'          => $category->term_id,
			'childless'         => false,
			'get'               => '', 
			'name__like'        => '',
			'description__like' => '',
			'pad_counts'        => false, 
			'offset'            => '', 
			'search'            => '', 
			'cache_domain'      => 'core'
		); 
		//pree($args);
		$terms = get_terms($taxonomies[0], $args);		
		
		return $terms;
	}
	
	function gp_taxonomy_items($slug, $arr=array()){
		
		$ret = array();
		
		$args = array(
			'posts_per_page'   => -1,
			'offset'           => 0,
			'category'         => '',
			'category_name'    => $slug,
			'orderby'          => 'menu_order',
			'order'            => 'ASC',
			'include'          => '',
			'exclude'          => '',
			'meta_key'         => '',
			'meta_value'       => '',
			'post_type'        => 'post',
			'post_mime_type'   => '',
			'post_parent'      => '',
			'author'	   => '',
			'post_status'      => 'publish',
			'suppress_filters' => true 
		);
		
		$args = array_merge($args, $arr);
		//pree($args);
		
		$posts_array = get_posts( $args );	
		
		
		
		if(!empty($posts_array)){
			$c = 0;
			foreach($posts_array as $post){
				$metas = get_post_meta($post->ID);
				$feat_image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'thumbnail' );
				
				$posts_array[$c]->image = (!empty($feat_image)?current($feat_image):'');
				
				if(!empty($metas)){
					foreach($metas as $key=>$val){
						$posts_array[$c]->$key = (count($val)==1?current($val):$val);
						if(is_array(maybe_unserialize($posts_array[$c]->$key))){
							$posts_array[$c]->$key = maybe_unserialize($posts_array[$c]->$key);
							if(!empty($posts_array[$c]->$key)){
								foreach($posts_array[$c]->$key as $k=>$v){
										
											if(is_array(maybe_unserialize($v))){
												foreach(maybe_unserialize($v) as $ki=>$vi){
													$posts_array[$c]->$ki = $vi; 
												}
											}else{
											
												$posts_array[$c]->$k = $v; 
										
											}
										
															
								}
							}
						}
					}
				}
				
				
				$c++;
			}
		}
		
		return $posts_array;
	}
	
	function gp_gallery_shortcode($atts=array(), $content=''){
		
		$arr = array(
			'before' => isset($atts['before'])?$atts['before']:'<a href="LINK" title="TITLE">',
			'after' => isset($atts['after'])?$atts['after']:'</a>'
		);
		$content = gp_gallery($atts['key'], $arr, $atts['group']);
		
		return $content;
		
	}
	
	function gp_gallery($holder, $arr=array(), $group=''){
		
		$gallery = gp_place( $holder, 'gallery', $group);
		//pree($gallery);
		$images = array();
		if( ! empty( $gallery ) ) {
		
		  $ids = explode( ',', $gallery );
			//pre($arr);
		  foreach ( $ids as $id ) {
			$attachment = wp_get_attachment_image_src( $id, 'full' );
			
			$data = get_post($id);
			$meta = get_post_meta($id);
			$link = isset($meta['be_gp_url'])?current($meta['be_gp_url']):get_permalink($data->ID);
			$meta['be_gp_dm'] = (isset($meta['be_gp_dm'])?current($meta['be_gp_dm']):'');
			
			$display_on = ((isset($arr['display_on']) && $arr['display_on']!='' && $meta['be_gp_dm']!=$arr['display_on']));
			
			//pre($meta);
			
			//pre($display_on);
			if(empty($attachment) || $display_on)
			continue;
			
			$url = current($attachment);
			$image = '';
			
			if(isset( $arr['before']))
			$image .= $arr['before'];
			
			$image .= '<img src="'.$url.'" alt="'.$data->post_title.'" />';
			
			if(isset( $arr['after']))
			$image .= $arr['after'];
			
			
			
			$image = str_replace(array('TITLE', 'LINK', 'ALT'), array($data->post_title, $link, get_post_meta($id, '_wp_attachment_image_alt', true)), $image);
			
			if(isset($arr['caption'])){
				$caption = str_replace(array('TITLE', 'LINK', 'ALT'), array($data->post_title, $link, get_post_meta($id, '_wp_attachment_image_alt', true)), $arr['caption']);
				
				$image = str_replace('CAPTION', $caption, $image);
			}

			$images[] = $image;
		  }
		
		}		
		//pree($images);
		return implode('', $images);
	}
	
	function gp_str($arr){
		return (is_array($arr)?current($arr):$arr);
	}
	
	function gp_arr($str){
		return (is_array($str)?$str:(array)$str);
	}	
	
	function gp_video($arr, $dim, $final='image'){
		if(!is_array($arr)){
			$video_type = gp_video_type($arr);
			$arr = array($video_type.'_link'=>$arr);
		}
		return _gp_video($arr, $dim, $final);
	}
	
	function gp_video_type($link){
		$video_type = stristr($link, 'vimeo')?'vimeo':'';
		$video_type = $video_type==''?stristr($link, 'youtube')?'youtube':'':$video_type;		
		return $video_type;
	}
	
	function _gp_video($arr, $dim, $final='image'){
		
		$ret = '';
		
		$video_type = '';
		
		$arr = (array)$arr;
		
		if(isset($arr['vimeo_link'])){
			$video_type = 'vimeo';
		}elseif(isset($arr['youtube_link'])){
			$video_type = 'youtube';
		}elseif(!empty($arr)){
			
			$link = current($arr);
			if($link!='' && !is_array($link)){
				pre($link);
				$video_type = gp_video_type($link);
			}
			
		}
		 
		switch($video_type){
			case 'vimeo':
				$arr['vimeo_link'] = 'https://player.vimeo.com/video/'.basename(untrailingslashit(gp_str($arr['vimeo_link'])));
				$ret = ' <iframe src="'.$arr['vimeo_link'].'" width="'.$dim[0].'" height="'.$dim[1].'" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
			break;
			
			case 'youtube':
				$video_link = preg_replace('/^.*(?:v=|\/)/', '', strtok(gp_str($arr['youtube_link']), '&'));
				$arr['youtube_link'] = 'https://www.youtube.com/embed/'.$video_link;
				$ret = ' <iframe src="'.$arr['youtube_link'].'" width="'.$dim[0].'" height="'.$dim[1].'" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
			break;
			
			default:
				if($final=='image')
				$ret = '<a href="'.get_permalink($arr['ID']).'"><img src="'.$arr['image'].'" alt=""></a>';
			
			break;
		}
		
		
		return $ret;
	}
	
	function gp_addons($dir){
		global $gp_addons;
		$addon = $gp_addons.$dir.'/index.php';
		if(file_exists($addon)){
			include($addon);
		}
	}
	
	function gp_date($format, $str){
		if(is_array($str)){
			$str = current($str);
		}
		return date($format, strtotime($str));
	}
	
	function gp_bullets($placeholder='', $type = 'textarea', $page = ''){
		$items = gp_place($placeholder, $type, $page);
		$split = explode('<br />', $items);
		if(!empty($split)){
			$split = implode('</li><li>', $split);
			if($split!=''){
				$split = '<li>'.$split.'</li>';
			}
		}
		return $split;
	}
	
	function gp_the_content_filter($content) {
	  
	  $content = str_replace('IMAGES/', GP_IMAGES, $content);
	  $content = str_replace('UPLOADS/', get_bloginfo('url').'/wp-content/uploads/', $content);
	  // otherwise returns the database content
	  return $content;
	}
	
	add_filter( 'the_content', 'gp_the_content_filter' );	
	
	function admin_styles(){
?>
	<style type="text/css">
	<?php if(isset($_GET['page']) && $_GET['page']=='CF7DBPluginSubmissions'){
?>
	
	#wpbody-content > table tbody > tr:first-child {
		visibility: hidden;
	}
<?php		
		
	}
	?>
	</style>
<?php		
	}
	add_action('admin_head', 'admin_styles');
		
		
	function gp_login_logo_url() {
		return home_url();
	}
	add_filter( 'login_headerurl', 'gp_login_logo_url' );
	
	function gp_login_logo() { 
		
		if(function_exists('avia_get_option')){
			$logo = avia_get_option('logo');
		}else{
			$logo = gp_place('logo', 'image', 'branding');
		}
		
		if($logo){
?>
	<style type="text/css">
		.login h1 a {
			background-image: url(<?php echo $logo; ?>);
			padding-bottom: 30px;
		}
	</style>
<?php 	}
	}
	
	add_action( 'login_enqueue_scripts', 'gp_login_logo' );	
		
	function gp_login_logo_url_title() {
		return get_bloginfo('name');
	}
	add_filter( 'login_headertitle', 'gp_login_logo_url_title' );		
	
	function gp_repeater($group_id, $type='page'){
	
		global $_gp_repeater_groups;

		$_gp_repeater_groups = (is_array($_gp_repeater_groups)?$_gp_repeater_groups:array());

		if(!array_key_exists($type, $_gp_repeater_groups)){
			$_gp_repeater_groups[$type] = array();
		}
		
		if(!in_array($group_id, $_gp_repeater_groups[$type])){
			
			$_gp_repeater_groups[$type][] = $group_id;
			
		}
	}
	

	function gp_header_scripts(){
		global $post, $lang;
		$orgurl = (is_front_page() || empty($post->ID))?get_bloginfo('url'):get_permalink( $post->ID );
		$thisurl = $orgurl;
		if($lang!='' && substr($thisurl, -3, 2)=='_'.$lang)
		$thisurl = str_replace('_'.$lang, '', $orgurl);
		
		
?>
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:200,300,400,600,700,900" rel="stylesheet" />
	<script type="text/javascript" language="javascript">
	var siteurl = '<?php echo get_bloginfo('url'); ?>/';
	var orgurl = '<?php echo $orgurl; ?>';
	var thisurl = '<?php echo $thisurl; ?>';
	var lang = '<?php echo $lang; ?>';
	//console.log(thisurl);
	//console.log('<?php echo $lang; ?>');
	</script>
	<style type="text/css">
	

	
	</style>
<?php		

		gp_place('guava_pattern_skin_color', 'color_picker', 'Appearance');
		
		
		$key = 'contact_form_7_skin';
		
		if(defined('CF7SKINS_STYLES_PATH')){
			
			if(!is_admin()){
				global $wpdb;
				
				if ($handle = opendir(CF7SKINS_STYLES_PATH)) {
					$skins = array();
					while (false !== ($slug = readdir($handle))) {
						if ($slug != "." && $slug != "..") {
							$title = gp_humanize($slug);
							$skins[$slug] = $title;
						}
					}
					closedir($handle);
					
					if(!empty($skins)){
						$val = gp_place($key, 'select', 'Appearance', array('options'=>$skins));	
						if($val!='')	
						$wpdb->query("UPDATE $wpdb->postmeta SET meta_value='$val' WHERE meta_key='cf7s_style'");
					}
				}
			}
		}else{
			gp_remove($key);
		}
	}
	
	add_action('wp_head', 'gp_header_scripts');	
	
			
	function gp_content_type() {		
		return 'text/html';		
	}
	
	add_filter( 'wp_mail_content_type', 'gp_content_type' );
	
	function get_post_sibling($post_id){
		$sibling = get_post_meta($post_id, 'elbisc_sibling', true);
		return $sibling;		
	}	
	function get_post_language($post_id){
		$language = get_post_meta($post_id, 'language', true);
		return $language;
	}			
	function get_switched_language($post_id){
		$sibling = get_post_sibling($post_id);
		$language = get_post_language($sibling);
		return $language;		
	}				

	function post_misc_actions(){
			global $post;
			$sibling = get_post_sibling($post->ID);
			$pages = '<a class="button-secondary" href="post.php?post='.$post->ID.'&action=edit&switch_lang">Add/update language</a>';
		
			
			if(isset($_GET['switch_lang'])){
				$pages = '<select class="switchable_lang">';
				$args = array(
					'posts_per_page'   => -1,
					'offset'           => 0,
					'category'         => '',
					'category_name'    => '',
					'orderby'          => 'title',
					'order'            => 'ASC',
					'include'          => '',
					'exclude'          => '',
					'meta_key'         => '',
					'meta_value'       => '',
					'post_type'        => 'any',
					'post_mime_type'   => '',
					'post_parent'      => '',
					'author'	   => '',
					'post_status'      => 'publish',
					'suppress_filters' => true 
				);
				$posts_array = get_posts( $args );
				if(!empty($posts_array)){
					$pages .= '<option>Select Page/Post</option>';
					$arr = array();
					foreach($posts_array as $posts){
						
						
						$selected = ($sibling==$posts->ID?'selected="selected"':'');
						$item = '<option value="'.$posts->ID.'" '.$selected.'>';
						$item .= $posts->ID.'- '.$posts->post_title;
						$item .= '</option>';
						$arr[$posts->post_type][] = $item;
					}
					
					if(!empty($arr)){
						foreach($arr as $post_type => $items){
							$pages .= '<optgroup label="'.gp_humanize($post_type).'">';
							$pages .= implode('', $items);	
							$pages .= '</optgroup>';
						}
					}
					
				}
				
				$pages .= '</select>';
				}

				$html  = '<div id="major-publishing-actions" style="overflow:hidden" class="sl_area">';
				$html .= '<div id="publishing-action">';
				$html .= $pages;
				
				if($sibling>0){
					$language = get_post_language($sibling);
					$html .= '<a href="'.get_edit_post_link($sibling).'" class="button-primary"><small>Switch language to</small> <b>'.strtoupper($language).'</b></a>';
				}
				
				$html .= '</div>';
				$html .= '</div>';
				echo $html;
				
			
	}
	
	if(!function_exists('init_sessions')){
		function init_sessions(){
			if (!session_id()){
				ob_start();
				@session_start();
				gp_info();
			}
		}
	}		
	
	function elbisc_posts_where( $where ) {
		global $lang;
		//pree($where);
		$needle = " AND (wp_posts.ID = '";
		if(strstr($where, $needle)){
			$post_id = explode($needle, $where);
			if(!empty($post_id)){
				$post_id = explode("'", $post_id[1]);
				if(!empty($post_id)){
					$post_id = current($post_id);
					if(is_numeric($post_id) && $post_id>0){
						$spost = get_post_sibling($post_id);
						if($spost>0){
							$slang = get_post_language($spost);
							//pree($lang);
							//pree($slang);
							if($lang!='' && $lang==$slang)
							$where = str_replace("'$post_id'", "'$spost'", $where);
						}
					}
				}
			}
		}
		//pree($where);
		return $where;
	}	
	
	add_filter( 'posts_where' , 'elbisc_posts_where' );
	
	function get_within($tag='div', $content=''){
		$regex = '#<\s*?'.$tag.'\b[^>]*>(.*?)</'.$tag.'\b[^>]*>#s';
				
		preg_match_all($regex, $content, $matches);	
		
		return 	$matches;
	}
	
	function gp_remote_file_exists($url) {
		
		
		if(LIVE){
		
			$curl = curl_init($url);
		
			//don't fetch the actual page, you only want to check the connection is ok
			curl_setopt($curl, CURLOPT_NOBODY, true);
		
			//do request
			$result = curl_exec($curl);
		
			$ret = false;
		
			//if request did not fail
			if ($result !== false) {
				//if request was ok, check response code
				$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);  
		
				if ($statusCode == 200) {
					$ret = true;   
				}
			}
		
			curl_close($curl);
		
		}else{
			$ret = (substr($url, 0, strlen('http'))=='http');
		}
	
		return $ret;
	}
	
	
	function gp_valid_file($url, $strict=true){
					
		$parts = explode('.', $url);
		$ext = (strlen(end($parts))==3);
		
		
		if(!$ext && $strict){
						
			$headers = @get_headers($url);
			
			if(is_array($headers)){
				
				$ext = (
						array_search('Content-Type: image/jpeg', $headers) 
						||
						array_search('Content-Type: image/png', $headers) 
						||
						array_search('Content-Type: image/gif', $headers) 
						|| 
						array_search('Content-Type: application/pdf', $headers)
						|| 
						array_search('Content-Type: application/msword', $headers)
						|| 
						array_search('Content-Type: application/vnd.ms-excel', $headers)
						|| 
						array_search('Content-Type: application/vnd.ms-powerpoint', $headers)
						
						);
				//exit;

			}
		}	
		return $ext;
	}			
	
	function gp_footer_scripts(){
		global $lang;
		$t = wp_get_post_tags(get_the_ID());
		$arr = array();
		if(!empty($t)){
			foreach($t as $tag){
				$arr[] = $tag->slug;
			}						
		}	
		
		$gp_core = gp_place('update_core', 'switcher', 'Updates');
		
		$bootstrap_forms = gp_place('bootstrap_forms', 'switcher', 'Appearance');
?>
	<script type="text/javascript" language="javascript">
	jQuery(document).ready(function($){
		<?php 
		if(!in_array($lang, $arr)){
			$arr[] = $lang;
		} //pree($lang);exit;
		if(!empty($arr)): foreach($arr as $tag): ?>
		if(!$('body').hasClass('<?php echo $tag; ?>')){
			$('body').addClass('<?php echo $tag; ?>');
		}
		<?php endforeach; endif; ?>
		
		<?php if($bootstrap_forms): ?>
		if($(".container-fluid form").length>0){
			$(".container-fluid form").each(function(){
				var is_woocommerce = $(this).parent().hasClass('woocommerce');
				$($(this).find(':input')).each(function(){
				
					$(this).addClass('form-control');
					
					if($(this).is(':submit')){
						$(this).attr('class', 'btn btn-primary btn-lg');
					}				

										
					
					
					if(is_woocommerce){
						if($(this).is('select')){
							$(this).removeClass('form-control');
						}
					}
					

					
				});
			});
		}
		<?php endif; ?>
		console.log($('.woocommerce'));
		if($('body.woocommerce').length>0){
			var obj = $('#wrapper.container-fluid #container').addClass('col-md-9').find('#content').removeAttr('id');
			$.each($('a.button'), function(){
				$(this).removeClass('button').addClass('btn btn-default');
			});
		}
	});
	</script>
<?php					
	
	}
	
	add_action('wp_footer', 'gp_footer_scripts');	
	
		
	
	if ( ! function_exists( 'gp_theme_setup' ) ) :

	function gp_theme_setup() {
		
	
		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );
	
		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support( 'title-tag' );
	
		add_theme_support( 'custom-logo', array(
			'height'      => 240,
			'width'       => 240,
			'flex-height' => true,
		) );

	
		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link http://codex.wordpress.org/Function_Reference/add_theme_support#Post_Thumbnails
		 */
		add_theme_support( 'post-thumbnails' );
		set_post_thumbnail_size( 1200, 9999 );
	
		// This theme uses wp_nav_menu() in two locations.
		register_nav_menus( array(
			'primary' => 'Primary Menu',
			'social'  => 'Social Links Menu',
		) );
	
		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support( 'html5', array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
		) );
	
		/*
		 * Enable support for Post Formats.
		 *
		 * See: https://codex.wordpress.org/Post_Formats
		 */
		add_theme_support( 'post-formats', array(
			'aside',
			'image',
			'video',
			'quote',
			'link',
			'gallery',
			'status',
			'audio',
			'chat',
		) );
	
	
		// Indicate widget sidebars can use selective refresh in the Customizer.
		add_theme_support( 'customize-selective-refresh-widgets' );
	}
	endif; 
	add_action( 'after_setup_theme', 'gp_theme_setup' );	
	
	
	function gp_core_update(){		
		$upload_dir = wp_upload_dir();
		$basedir = $upload_dir['basedir'];
		$file = $basedir.'/gp-core.zip';
		//pree($upload_dir);
		//exit;
		if(!file_exists($file)){
			copy('https://github.com/fahadmahmood8/guava-pattern/archive/master.zip', $file);
		}
		
		$path = get_template_directory();//pathinfo(realpath($file), PATHINFO_DIRNAME);
		$path = str_replace("\\","/",$path); 
		//pree(get_template_directory());
		//pree($path);
		//exit;
		$dirname = '';
		$zip = new ZipArchive;
		if ($zip->open($file) === true) {
			//pree($zip);		   
			for($i = 0; $i < $zip->numFiles; $i++) {
				$filename = $zip->getNameIndex($i);
				$fileinfo = pathinfo($filename);
				if($dirname==''){
					$dirname = $fileinfo['basename'];
				}else{
					//pree($fileinfo);
					$target = $fileinfo['dirname'].'/'.$fileinfo['basename'];
					$target = str_replace($dirname.'/', $path.'/', $target);
					
					if(isset($fileinfo['extension'])){
						copy("zip://".$file."#".$filename, $target);
					}else{
						if(!is_dir($target)){
							mkdir($target, 0700, true);							
						}
					}
					//echo $target.'<br />';
					
				}
			}
						   
			$zip->close();
			unlink($file);
						   
		}
		//exit;
	}
	
	
	
	