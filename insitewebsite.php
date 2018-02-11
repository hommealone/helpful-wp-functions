<?php
/**
 * Custom functions that we commonly use in our Wordpress themes
 *
 * @package _tk
 */

/*
 * Site functions for this theme
 * Author: Paul Solomon
*/

/*
 * Remove garbage from head
*/

// Remove emoji inline CSS and js
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );

// Remove meta generator tag (WordPress version)
remove_action('wp_head', 'wp_generator');

/**
 *  Remove Customizer item from admin bar 
 */
add_action( 'admin_bar_menu', 'iw_remove_from_admin_bar', 999 );
function iw_remove_from_admin_bar( $wp_admin_bar ) {
    $wp_admin_bar->remove_menu( 'customize' );
};

/**
 * Adds custom classes to the array of body classes.
 */
function _tk_body_classes( $classes ) {
	global $post; // added to enable retrieval of slug
	// get slug:
	$post_slug = $post->post_name;
	// Add the page slug as a body class
	if ( $post_slug ) {
		$classes[] = 'page-'.$post_slug;
	};
	return $classes;
};
// uncomment next line if not already included in the theme. In _tk, check includes/extras.php
//add_filter( 'body_class', '_tk_body_classes' );

/*
 * Add year shortcode: [year]
*/
function iw_year_func( $atts ){
    $current_year = date('Y');
    return $current_year;
};
add_shortcode( 'year', 'iw_year_func' );

/**
 * Add short tag [date] [date format='short'] [date format='long']
 */
function iw_date_shortcode( $atts ) {
	// Attributes
	extract(shortcode_atts(
		array(
			'format' => '',
		),
		$atts)
	);
	// NOTE: this is not working properly... PS
	if ($format=='short') {
	    $date_tag = date('F j, Y');
	} elseif ($format=='long') {
	    $date_tag = date('l, F j, Y');
	} else {
	    $date_tag = date('l, F jS, Y');
	};
	return $date_tag;
}
add_shortcode( 'date', 'iw_date_shortcode' );

/*
 * Allow PHP in text widgets
*/
// un-comment the next line if you need this
// add_filter('widget_text','execute_php',100);
function execute_php($html){
     if(strpos($html,"<"."?php")!==false){
          ob_start();
          eval("?".">".$html);
          $html=ob_get_contents();
          ob_end_clean();
     };
     return $html;
};

/*
 * replace WordPress Howdy with Hi in WordPress 3.3+
*/
function replace_howdy( $wp_admin_bar ) {
    $my_account=$wp_admin_bar->get_node('my-account');
    $newtitle = str_replace( 'Howdy,', 'Hi,', $my_account->title );            
    $wp_admin_bar->add_node( array(
        'id' => 'my-account',
        'title' => $newtitle,
    ) );
};
add_filter( 'admin_bar_menu', 'replace_howdy',25 );

/**
 * just for TESTIING script handles:
 * puts a list of script handles in the head; useful if you are trying to find a script handle.
 * comment out next line when NOT testing
 */
//add_action( 'wp_print_scripts', 'wpa54064_inspect_scripts');
function wpa54064_inspect_scripts() {
    global $wp_scripts;
	echo ' ' . PHP_EOL . '<!-- ';
    foreach( $wp_scripts->queue as $handle ) :
        echo $handle,' | ';
    endforeach;
	echo '-->' . PHP_EOL;
}

/**
 * add script handle as script tag attribute
 * author: Paul Solomon http://www.insitewebsite.com/
 *
 * utilizes WP hook: script_loader_tag
 * https://developer.wordpress.org/reference/hooks/script_loader_tag/
 *
 * suggested and adapted from: https://allenmoore.me/filtering-html-script-tags-with-script_loader_tag/
 *
 * If you prefer to use the ID attribute:
 *  return str_replace( "></script>", " id='" . $handle . "-js'></script>", $tag );
 */
function iw_add_handle_as_tag_attr( $tag, $handle, $src ) {
    if ( ! $handle ) :
        return $tag;
    endif;
    return str_replace( "></script>", " data-script-handle='" . $handle . "'></script>", $tag );
}
add_filter( 'script_loader_tag', 'iw_add_handle_as_tag_attr', 10, 3 );

/**
 * change tinymce's paste-as-text functionality
 * Force paste as plain text
 * see: http://www.wizzud.com/2014/02/14/force-paste-as-text-on-in-wordpress/
 * This helps prevent sloppy users from adding all sorts of garbage code.
 */
function change_paste_as_text($mceInit, $editor_id){
	//turn on paste_as_text by default
	//NB this has no effect on the browser's right-click context menu's paste!
	$mceInit['paste_as_text'] = true;
	return $mceInit;
}
//un-comment next line if you want to use this.
//add_filter('tiny_mce_before_init', 'change_paste_as_text', 1, 2);

/**
 * Add CPT archive pages as insertable items in admin > appearance > menus
 * See: http://stackoverflow.com/questions/20879401/how-to-add-custom-post-type-archive-to-menu
 */
function prefix_add_metabox_menu_posttype_archive(){
  add_meta_box( 'prefix_metabox_menu_posttype_archive', __( 'Archives' ), 'prefix_metabox_menu_posttype_archive', 'nav-menus', 'side', 'default' );
}
add_action( 'admin_head-nav-menus.php', 'prefix_add_metabox_menu_posttype_archive' );

function prefix_metabox_menu_posttype_archive(){
  $post_types = get_post_types( array( 'show_in_nav_menus' => true, 'has_archive' => true ), 'object' );
  if( $post_types ){
    foreach( $post_types as $post_type ){
      $post_type->classes = array( $post_type->name );
      $post_type->type = $post_type->name;
      $post_type->object_id = $post_type->name;
      $post_type->title = $post_type->labels->name;
      $post_type->object = 'cpt_archive';
    };
    $walker = new Walker_Nav_Menu_Checklist( array() );?>
    <div id="cpt-archive" class="posttypediv">
      <div id="tabs-panel-cpt-archive" class="tabs-panel tabs-panel-active">
        <ul id="ctp-archive-checklist" class="categorychecklist form-no-clear"><?php
        echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $post_types ), 0, (object) array( 'walker' => $walker ) );?>
        </ul>
      </div>
    </div>
    <p class="button-controls">
      <span class="add-to-menu">
        <input type="submit"<?php disabled( $nav_menu_selected_id, 0 ); ?> class="button-secondary submit-add-to-menu" value="<?php esc_attr_e( 'Add to Menu' ); ?>" name="add-ctp-archive-menu-item" id="submit-cpt-archive" />
      </span>
    </p><?php
  };
};

function prefix_cpt_archive_menu_filter( $items, $menu, $args ){
  foreach( $items as &$item ){
    if( $item->object != 'cpt_archive' ) continue;
    $item->url = get_post_type_archive_link( $item->type );
    if( get_query_var( 'post_type' ) == $item->type ){
      $item->classes []= 'current-menu-item';
      $item->current = true;
    };
  };
  return $items;
};
add_filter( 'wp_get_nav_menu_items', 'prefix_cpt_archive_menu_filter', 10, 3 );

/* 
 * Prevent hackers from finding usernames. Why?...
 * see: https://www.wp-tweaks.com/hackers-can-find-your-wordpress-username/
 * This can be done with lower overhead by editing your htaccess file;
 * see referenced page for the code.
 */
function redirect_to_home_if_author_parameter() {
	$is_author_set = get_query_var( 'author', '' );
	if ( $is_author_set != '' && !is_admin()) {
		wp_redirect( home_url(), 301 );
		exit;
	};
};
// if you use htaccess for this instead, comment out the next line.
add_action( 'template_redirect', 'redirect_to_home_if_author_parameter' );

/*
 * Add image size
 * or in this case modify medium_large size
 * We theme with Bootstrap for 5 responsive breakpoints
 *
 */
function add_medium_large_image() {
	add_image_size( 'medium_large', 768, 768 );
};
add_action( 'after_setup_theme', 'add_medium_large_image' );

/**
 * Make custom image sizes selectable from the WordPress admin
*/
add_filter( 'image_size_names_choose', 'iw_image_size_names_choose' );
function iw_image_size_names_choose( $sizes ) {
	return array_merge( $sizes, array(
		'medium_large' => __( 'Medium Large' ),
	) );
};

/* 
 * Automatically set the image Title, Alt-Text, Caption & Description upon upload.
 * see: https://brutalbusiness.com/automatically-set-the-wordpress-image-title-alt-text-other-meta/
 * Comment or uncomment portions as appropriate.
 */
add_action( 'add_attachment', 'iw_set_image_meta_upon_image_upload' );
function iw_set_image_meta_upon_image_upload( $post_ID ) {
	// Check if uploaded file is an image, else do nothing
	if ( wp_attachment_is_image( $post_ID ) ) {

		$my_image_title = get_post( $post_ID )->post_title;

		// Sanitize the title:  remove hyphens, underscores & extra spaces:
		$my_image_title = preg_replace( '%\s*[-_\s]+\s*%', ' ',  $my_image_title );

		// Sanitize the title:  capitalize first letter of every word (other letters lower case):
		/* $my_image_title = ucwords( strtolower( $my_image_title ) ); */

		// Create an array with the image meta (Title, Caption, Description) to be updated
		// Note:  comment out the Excerpt/Caption or Content/Description lines if not needed
		$my_image_meta = array(
			'ID'		=> $post_ID,			// Specify the image (ID) to be updated
			'post_title'	=> $my_image_title,		// Set image Title to sanitized title
			'post_excerpt'	=> $my_image_title,		// Set image Caption (Excerpt) to sanitized title
			'post_content'	=> $my_image_title,		// Set image Description (Content) to sanitized title
		);

		// Set the image Alt-Text
		update_post_meta( $post_ID, '_wp_attachment_image_alt', $my_image_title );

		// Set the image meta (e.g. Title, Excerpt, Content)
		wp_update_post( $my_image_meta );
	};
};