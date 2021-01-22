# helpful-wp-functions
Helpful functions to add to your WordPress themes.

You can add these functions a la carte to your functions.php file, or keep them all as a distinct file and add using an include() in your functions.php file. Comment in or out those which you are/are not using.

```
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

// Remove meta generator tag (WordPress version) from head AND RSS feed
function iw_remove_version() {
	return '';
}
add_filter('the_generator', 'iw_remove_version');

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
	global $template; // added to enable retrieval of template file name
	// get slug:
	$post_slug = $post->post_name;
	// Add the page slug as a body class
	if ( $post_slug ) {
		$classes[] = 'page-'.$post_slug;
	}
	// get template file name
	$template_file = basename($template);
	if ( $template_file ) {
		$classes[] = 'template-'.$template_file;
	}
	return $classes;
};
// uncomment next line if not already included in the theme. In _tk, check includes/extras.php
//add_filter( 'body_class', '_tk_body_classes' );

/**
 * Alternate - Adds custom classes to the array of body classes.
 * Check if your theme already has this kind of funtion, and if it does you can use bits of this to modify it.
 */
function insite_starter_body_classes( $classes ) {
	global $post; // added to enable retrieval of slug
	global $template; // added to enable retrieval of template file name
	// Adds a class of group-blog to blogs with more than 1 published author.
	if ( is_multi_author() ) {
		$classes[] = 'group-blog';
	}
	// Adds a class of hfeed to non-singular pages.
	if ( ! is_singular() ) {
		$classes[] = 'hfeed';
	}
    if ( get_theme_mod( 'theme_option_setting' ) && get_theme_mod( 'theme_option_setting' ) !== 'default' ) {
        $classes[] = 'theme-preset-active';
    }
	// get slug:
	$post_slug = $post->post_name;
	// Add the page slug as a body class
	if ( $post_slug ) {
		$classes[] = 'page-'.$post_slug;
	}
	// get template file name
	$template_file = basename($template);
	if ( $template_file ) {
		$classes[] = 'template-'.$template_file;
	}
	return $classes;
}
add_filter( 'body_class', 'insite_starter_body_classes' );

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
/* Step one: */
function redirect_to_home_if_author_parameter() {
	$is_author_set = get_query_var( 'author', '' );
	if ( $is_author_set != '' && !is_admin()) {
		wp_redirect( home_url(), 301 );
		exit;
	};
};
// if you use htaccess for this instead, comment out the next line.
add_action( 'template_redirect', 'redirect_to_home_if_author_parameter' );
/* Step two: */
function disable_rest_endpoints ( $endpoints ) {
    if ( isset( $endpoints['/wp/v2/users'] ) ) {
        unset( $endpoints['/wp/v2/users'] );
    }
    if ( isset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] ) ) {
        unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
    }
    return $endpoints;
}
add_filter( 'rest_endpoints', 'disable_rest_endpoints');

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

/* 
 * Force login on development sites without a plugin.
 * see: https://trickspanda.com/force-users-login-viewing-wordpress/
 */
function iw_getUrl() {
  $url  = isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ? 'https' : 'http';
  $url .= '://' . $_SERVER['SERVER_NAME'];
  $url .= in_array( $_SERVER['SERVER_PORT'], array('80', '443') ) ? '' : ':' . $_SERVER['SERVER_PORT'];
  $url .= $_SERVER['REQUEST_URI'];
  return $url;
}
function iw_forcelogin() {
  if( !is_user_logged_in() ) {
    $url = iw_getUrl();
    $whitelist = apply_filters('iw_forcelogin_whitelist', array());
    $redirect_url = apply_filters('iw_forcelogin_redirect', $url);
    if( preg_replace('/\?.*/', '', $url) != preg_replace('/\?.*/', '', wp_login_url()) && !in_array($url, $whitelist) ) {
      wp_safe_redirect( wp_login_url( $redirect_url ), 302 ); exit();
    }
  }
}
add_action('init', 'iw_forcelogin');

/* 
 * Dis-allow theme and plugin file editing from the WP admin
 * See: https://www.wpbeginner.com/wp-tutorials/how-to-disable-theme-and-plugin-editors-from-wordpress-admin-panel/
 * Note: often inserted into wp-config.php, this also works in theme function files.
 * Uncomment for use.
 */
//define( 'DISALLOW_FILE_EDIT', true );

/** 
 * Integrate Clear Cache for Me with Autoptimize
 * This function allows the Clear Cache For Me dashboard button to also clear the Autoptimize cache.
 * see: https://gist.github.com/benjaminpick/94b487ce995454797143
 * see: https://wordpress.org/plugins/clear-cache-for-widgets/  (AKA Clear Cache For Me)
 */
function yt_cache_enable($return) {
	if (class_exists('autoptimizeCache'))
		return true;
	
	return $return;
}
add_filter('ccfm_supported_caching_exists', 'yt_cache_enable');

/**
 * Load Contact Form 7 on Contact page only.
 * Deregister Contact Form 7 stylesheets on all pages without a form.
 * Deregister Contact Form 7 JavaScript files on all pages without a form.
 * See: https://code.tutsplus.com/articles/optimizing-contact-form-7-for-better-performance--wp-31255
 * Also deregister bootstrap for contact form 7 plugin files.
 * Also dequeue google recaptcha v3.
 * See: https://wordpress.org/support/topic/disable-recaptcha-v3-for-all-pages-except-the-one-with-a-contact7-form/
 **/
add_action( 'wp_print_styles', 'iw_deregister_styles', 100 );
function iw_deregister_styles() {
    if ( ! is_page( 'contact' ) ) {
    // if you have a contact form on several pages, substitute the next line
    // if ( !is_page( array( 'contact','some-other-page-with-form' ) ) ) {
        wp_deregister_style( 'contact-form-7' );
        wp_deregister_style( 'contact-form-7-bootstrap-style' );
    }
}
add_action( 'wp_print_scripts', 'iw_deregister_javascript', 100 );
function iw_deregister_javascript() {
    if ( ! is_page( 'contact' ) ) {
    // if you have a contact form on several pages, substitute the next line
    // if ( !is_page( array( 'contact','some-other-page-with-form' ) ) ) {
        wp_deregister_script( 'contact-form-7' );
    }
}
/* Remove Google ReCaptcha V3 code/badge everywhere apart from select pages */
add_action('wp_print_scripts', function () {
    if ( ! is_page( 'contact' ) ) {
    // if you have a contact form on several pages, substitute the next line
    // if ( !is_page( array( 'contact','some-other-page-with-form' ) ) ) {
        wp_dequeue_script( 'google-recaptcha' );
        wp_dequeue_script( 'google-invisible-recaptcha' );
    }
});
/** ==========================================================================
 * Enhancements for Quicktags
 * These can be enhancements for the AddQuicktags plugin or independent from it.
 * 
 * See: https://kinsta.com/blog/wordpress-text-editor/
 * See also: https://wordpress.org/plugins/addquicktag/
 */
function custom_quicktags() {

	if ( wp_script_is( 'quicktags' ) ) {
	?>
	<script type="text/javascript">
	
	QTags.addButton( 'p-tag', 'p', '<p>', '</p>', '', 'paragraph tag', 3 );
	QTags.addButton( 'div-tag', 'div', '<div>', '</div>', '', 'div tag', 4 );
	QTags.addButton( 'h2-tag', 'h2', '<h2>', '</h2>', '', 'h2 tag', 5 );
	QTags.addButton( 'h3-tag', 'h3', '<h3>', '</h3>', '', 'h3 tag', 6 );
	QTags.addButton( 'h4-tag', 'h4', '<h4>', '</h4>', '', 'h4 tag', 7 );
	
	QTags.addButton( 'class_button_tag', 'class', css_callback );

	function css_callback(){
		var css_class = prompt( 'Class name:', '' );

		if ( css_class && css_class !== '' ) {
			QTags.insertContent(' class="' + css_class +'"');
		}
	}
	
	QTags.addButton( 'style_button_tag', 'style', style_callback );

	function style_callback(){
		var css_style = prompt( 'Styles:', '' );

		if ( css_style && css_style !== '' ) {
			QTags.insertContent(' style="' + css_style +'"');
		}
	}
	
	QTags.addButton( 'target-tag', 'target', ' target="_blank"', '', '', 'target attribute' );
	
	</script>
	<?php
	}
}

add_action( 'admin_print_footer_scripts', 'custom_quicktags' );

/** ==========================================================================
 * Add custom favicon meta to head and keep it neat; 
 * see: https://developer.wordpress.org/reference/hooks/wp_head/
 * See: https://realfavicongenerator.net/
 * Use priority 15, or greater as needed, to keep this at the end of the head.
 * meta name="theme-color" controls the menu bar color in Chrome mobile; keep it rather light.
 * meta name="application-name" controls the title of the application tile in Windows OS tiles.
 * All icon files have been added to /favicon/ directory outside of WordPress installation,
 * except favicon.ico (multi-size icon file), browserconfig.xml, and site.manifest, which are in the root.
 * browserconfig.xml and site.manifest point to files in the subdirectory.
 * Be sure that the domain name matches your site's root.
 */
function iw_hook_favicon() {
	$favicon_meta = PHP_EOL.'<!-- favicons for multiple devices -->'.PHP_EOL;
	$favicon_meta .= '<link rel="apple-touch-icon" sizes="180x180" href="https://example.com/favicon/apple-touch-icon.png?v=190312">'.PHP_EOL;
	$favicon_meta .= '<link rel="icon" type="image/png" sizes="32x32" href="https://example.com/favicon/favicon-32x32.png?v=190312">'.PHP_EOL;
	$favicon_meta .= '<link rel="icon" type="image/png" sizes="16x16" href="https://example.com/favicon/favicon-16x16.png?v=190312">'.PHP_EOL;
	$favicon_meta .= '<link rel="mask-icon" href="https://example.com/favicon/safari-pinned-tab.svg?v=190312" color="#46aa48">'.PHP_EOL;
	$favicon_meta .= '<link rel="manifest" href="https://example.com/site.webmanifest?v=190312">'.PHP_EOL;
	$favicon_meta .= '<link rel="shortcut icon" href="https://example.com/favicon.ico?v=190312">'.PHP_EOL;
	$favicon_meta .= '<meta name="msapplication-config" content="https://example.com/browserconfig.xml?v=190312">'.PHP_EOL;
	$favicon_meta .= '<meta name="apple-mobile-web-app-title" content="Example">'.PHP_EOL;
	$favicon_meta .= '<meta name="application-name" content="Example">'.PHP_EOL;
	$favicon_meta .= '<meta name="msapplication-TileColor" content="#00a300">'.PHP_EOL;
	$favicon_meta .= '<meta name="theme-color" content="#7bcd7d">'.PHP_EOL;
	$favicon_meta .= PHP_EOL;
	echo $favicon_meta;
}
add_action('wp_head', 'iw_hook_favicon', 15);

/** ==========================================================================
 * Modify the tags allowed in comments
 * see: https://crunchify.com/the-best-way-to-allow-html-tags-in-wordpress-comment-form/
 */
function insite_remove_html_attributes_in_commentform() {
    global $allowedtags;
	/* we will remove all tags and then add back just the few we want so they will be in sensible order */
    /* remove insite_tags_to_remove tags */
    $insite_tags_to_remove = array(
		'a',
		'blockquote',
		'abbr',
		'acronym',
		'q',
		's',
		'strike',
        'cite',
        'code',
        'del',
        'pre',
		'b',
		'i',
		'strong',
		'em'
        );
    foreach ( $insite_tags_to_remove as $tag )
        unset( $allowedtags[$tag] );
    /* add wanted tags */
    $insite_newTags = array(
        /* 'span' => array(
            'lang' => array()), */
		'b' => array(),
		'strong' => array(),
		'i' => array(),
		'em' => array(),
		'blockquote' => array()
        );
    $allowedtags = array_merge( $allowedtags, $insite_newTags );
}
add_action('init', 'insite_remove_html_attributes_in_commentform', 11 );

/** =========================================================================
 * hide "+ Add New Category" in Categories meta box
 * make it harder for editors to add categories
 */
function iw_hide_new_category_option() {
  echo '<style>#category-adder {display: none;}</style>';
}
add_action('admin_head', 'iw_hide_new_category_option');

/** =========================================================================
 * Remove archive type from archive page title
 * see: https://wordpress.stackexchange.com/questions/179585/remove-category-tag-author-from-the-archive-title
 */
add_filter( 'get_the_archive_title', function ($title) {
    if ( is_category() ) {
            $title = single_cat_title( '', false );
        } elseif ( is_tag() ) {
            $title = single_tag_title( '', false );
        } elseif ( is_author() ) {
            $title = '<span class="vcard">' . get_the_author() . '</span>' ;
        } elseif ( is_post_type_archive() ) {
			$title = post_type_archive_title( '', false );
		}
    return $title;
});

/** =========================================================================
 * Add asynch and defer attributes to enqueued scripts where needed.
 * We add this to our enqueue functions.
 */
function insite_script_tag_async_defer_attrs( $tag, $handle, $src ) {
    // the handles of the enqueued scripts we want to async and/or defer
    // 1: list of script handles to defer.
    $scripts_to_defer = array('script-handle1', 'google-map-api', '_tk-font-awesome');
    // 2: list of script handles to async.
    $scripts_to_async = array('lazysizes', 'google-map-api');
   
    // async scripts
    foreach($scripts_to_async as $async_script){
        if ( in_array( $handle, $scripts_to_async ) && (false === stripos($tag, 'async')) )
        $tag = str_replace( ' src', ' async src', $tag );
    }
    // defer scripts
    foreach($scripts_to_defer as $defer_script){
        if ( in_array( $handle, $scripts_to_defer ) && (false === stripos($tag, 'defer')) )
        $tag = str_replace( ' src', ' defer src', $tag );
    }
    return $tag;
}
add_filter( 'script_loader_tag', 'insite_script_tag_async_defer_attrs', 10, 3 );

/** =========================================================================
 * Add custom post types to search results. Requires CPT UI plugin.
 * see: https://docs.pluginize.com/article/23-post-type-posts-in-search-results
 */
 /** Version one: all CPTs */
 function my_cptui_add_all_post_types_to_search( $query ) {
	if ( is_admin() ) {
		return;
	}

	if ( $query->is_search() ) {
		$cptui_post_types = cptui_get_post_type_slugs();
		$query->set(
			'post_type',
			array_merge(
				array( 'post', 'page' ), // May also want to add the "page" post type.
				$cptui_post_types
			)
		);
	}
}
//add_filter( 'pre_get_posts', 'my_cptui_add_all_post_types_to_search' );
/** Version two: selected CPTs */
function my_cptui_add_post_type_to_search( $query ) {
	if ( $query->is_search() ) {
		// Replace these slugs with the post types you want to include.
		$cptui_post_types = array( 'my_post_type', 'my_other_post_type' );

		$query->set(
			'post_type',
			array_merge(
				array( 'post' ),
				$cptui_post_types
			)
		);
	}
}
//add_filter( 'pre_get_posts', 'my_cptui_add_post_type_to_search' );

/** =========================================================================
 * Easy way to test code on a live site. Hide it from everyone except yourself.
 * Substitute your user ID in this code.
 * DON'T put this in your functions.php file; use it in a template file.
 */
if (get_current_user_id() == 7) {
	//do something
}

/*
 * Remove author category from WordPress XML sitemap
 * see: https://duaneblake.co.uk/wordpress/how-to-remove-author-sitemaps-from-wordpress/
*/
function remove_author_category_pages_from_sitemap($provider, $name)
{
    if ('users' === $name) {
        return false;
    }
    return $provider;
}
add_filter('wp_sitemaps_add_provider', 'remove_author_category_pages_from_sitemap', 10, 2);

/* Disable double update notifications caused by conflict between Easy Updates Manager and WP Core update feature.
 * see: https://wordpress.org/support/topic/email-notifications-on-plugin-updates/page/2/
 */
/* Disable auto-update email notifications for plugins. */
add_filter( 'auto_plugin_update_send_email', '__return_false' );

/* Disable auto-update email notifications for themes. */
add_filter( 'auto_theme_update_send_email', '__return_false' );

/*
 * Remove WP code to add html margin-top
 * Use in themes with hide-away navbar
 * see: https://css-tricks.com/snippets/wordpress/remove-the-28px-push-down-from-the-admin-bar/
 */
function iw_remove_admin_bar_bump() {
  remove_action('wp_head', '_admin_bar_bump_cb');
}
add_action('get_header', 'iw_remove_admin_bar_bump');

/** =======================================================================
 * Workaround for broken autoptimize nag issue. 1/2021: this should eventually get fixed and this workaround can be removed. PS
 * see: https://wordpress.org/support/topic/delete-notice-check-out-the-autoptimize-extra-settings-to-activate-this-option/page/4/
 */
add_filter( 'autoptimize_filter_main_imgopt_plug_notice', '__return_empty_string' );

```
