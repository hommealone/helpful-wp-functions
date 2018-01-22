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

/*
 * Add year shortcode: [year]
*/
function year_func( $atts ){
    $current_year = date('Y');
    return $current_year;
};
add_shortcode( 'year', 'year_func' );


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
