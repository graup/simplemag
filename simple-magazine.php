<?php
/**
 * @package Simple Magazine
 * @version 1.0
 */
/*
Plugin Name: Simple Magazine
Plugin URI: http://christofferok.com/
Description: A simple magazine style plugin 
Author: Christoffer Korvald
Version: 1.0
Author URI: http://christofferok.com/
License: MIT
*/

define('WP_DEBUG', true);

define('SIMPLEMAG_VERSION', 		'1.0' );
define('SIMPLEMAG_URL', 			plugin_dir_url( __FILE__ ) );
define('SIMPLEMAG_PATH', 			plugin_dir_path( __FILE__ ) );
define('SIMPLEMAG_BASENAME', 		plugin_basename( __FILE__ ) );
define('SIMPLEMAG_REL_DIR', 		dirname( SIMPLEMAG_BASENAME ) );



function add_simplemag_fields( $simplemag_id, $simplemag) {
    // Check post type for movie reviews
    if ( $simplemag->post_type == 'simplemag-article' ) {
        // Store data in post meta table if present in post data
        if ( isset( $_POST['simplemag_issue'] ) && $_POST['simplemag_issue'] != '' ) {
            update_post_meta( $simplemag_id, 'simplemag_issue', $_POST['simplemag_issue'] );
        }
    }
}

function simplemag_meta_box() {
    add_meta_box( 'simplemag_meta_box',
        'Simple Magazine Article Details',
        'display_simplemag_meta_box',
        'simplemag-article', 'normal', 'high'
    );
}

function display_simplemag_meta_box( $simplemag_article ) {
    // Retrieve current name of the Director and Movie Rating based on review ID
    $issue = intval( get_post_meta( $simplemag_article->ID, 'simplemag_issue', true ));
    ?>
    <table>
        <tr>
            <td style="width: 100%">Issue</td>
            <td>
            <select name="simplemag_issue">
            <option value="">- Select issue -</option>
            <?php
            // Get all issues
            $issues = new WP_Query(array('post_type' => 'simplemag-issue'));
            
            while ( $issues->have_posts() ) :
            	$issues->the_post();
            	echo '<option value="' . get_the_ID() . '"';
            	if($issue == get_the_ID()) echo ' selected="selected"';
            	echo '>' . get_the_title() . '</option>';
            endwhile;
            ?>
            </select>
            </td>
        </tr>
    </table>
    <?php
}

function create_simplemag() {
    register_post_type( 'simplemag-article',
        array(
            'labels' => array(
                'name' => 'Articles',
                'singular_name' => 'Article',
                'add_new' => 'Add New',
                'add_new_item' => 'Add New Article',
                'edit' => 'Edit',
                'edit_item' => 'Edit Article',
                'new_item' => 'New Article',
                'view' => 'View',
                'view_item' => 'View Article',
                'search_items' => 'Search Articles',
                'not_found' => 'No articles found',
                'not_found_in_trash' => 'No articles found in Trash',
                'parent' => 'Parent Article'
            ),
            'hierarchical' => false,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'supports' => array( 'title', 'editor','author',  'thumbnail','page-attributes' ),
            'taxonomies' => array( '' ),
            'menu_icon' => plugin_dir_url( __FILE__ ).'images/icon-16.png',
            'has_archive' => true,
            'show_in_menu' => 'simplemag',
            'register_meta_box_cb' => 'simplemag_meta_box'
        )
    );
    
    register_post_type( 'simplemag-issue',
        array(
            'labels' => array(
                'name' => 'Issues',
                'singular_name' => 'Issue',
                'add_new' => 'Add New',
                'add_new_item' => 'Add New Issue',
                'edit' => 'Edit',
                'edit_item' => 'Edit Issue',
                'new_item' => 'New Issue',
                'view' => 'View',
                'view_item' => 'View Issue',
                'search_items' => 'Search Issues',
                'not_found' => 'No issues found',
                'not_found_in_trash' => 'No issues found in Trash',
                'parent' => 'Parent Issue'
            ),
            'hierarchical' => false,
            'public' => true,
            'publicly_queryable' => false,
            'supports' => array( 'title', 'excerpt',  'thumbnail'),
            'taxonomies' => array( '' ),
            'menu_icon' => plugin_dir_url( __FILE__ ).'images/icon-16.png',
            'has_archive' => true,
            'show_in_menu' => 'simplemag',
            'rewrite'=>array(
                'slug'=>'issue',
                'with_front' => false,
                'pages' => false
                )
            
        )
    );
    
    
    
}


function cleanPermalink($str, $replace=array(), $delimiter='-') {
	if( !empty($replace) ) {
		$str = str_replace((array)$replace, ' ', $str);
	}
	
	$clean = str_replace("æ","ae",$clean);
	$clean = str_replace("ø","oe",$clean);
	$clean = str_replace("å","a",$clean);

	$clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
	$clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
	$clean = strtolower(trim($clean, '-'));
	$clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

	return $clean;
}

add_filter('name_save_pre', 'save_name');
function save_name($name) {
	global $post;
	if($post->post_type == 'simplemag-article'){
        $post->post_name = cleanPermalink($post->post_title);
          return $post->post_name;
	}
	return $name;
}



add_action( 'init', 'create_simplemag' );
add_action( 'save_post', 'add_simplemag_fields', 10, 2 );




add_action( 'admin_menu', 'simplemag_plugin_menu' );
function simplemag_plugin_menu() {
	add_menu_page('Simple Magazine', 'Simple Magazine', 'edit_pages', 'simplemag', null,SIMPLEMAG_URL.'/images/icon-16.png',9);
}



// Custom column for issue on article
add_filter('manage_simplemag-article_posts_columns', 'simplemag_article_columns', 10);  
add_action('manage_simplemag-article_posts_custom_column', 'simplemag_article_custom_column', 10, 2);  
function simplemag_article_columns($defaults) {  
    $defaults['issue'] = 'Issue';  
    return $defaults;  
}  
function simplemag_article_custom_column($column_name, $post_ID) {  
    if ($column_name == 'issue') {  
        $issueID = get_post_meta($post_ID, 'simplemag_issue', true); 
        $issue = get_post($issueID, ARRAY_A);
        //var_dump($issue);
        echo $issue['post_title'];
    } 
} 


/* This is only done on activation */
register_activation_hook( __FILE__, 'simplemag_activate' );
function simplemag_activate() {
    global $wp_rewrite;
    create_simplemag();
    add_rewrite_rule('issue/([0-9A-Za-z-]*)/?([0-9A-Za-z-]*)?/?',substr(SIMPLEMAG_PATH,1).'issue.php?issue=$1&article=$2','top');
    flush_rewrite_rules();
}

/* This is only done on deactivation */
register_deactivation_hook( __FILE__, 'simplemag_deactivate' );
function simplemag_deactivate() {
	flush_rewrite_rules();
}



?>
