<?php
require_once('../../../wp-load.php' );

wp();


function articleUrl($articleName){
    global $issue;
    return '/issue/'.$issue['name'].'/'.$articleName;
}
	
$issueSlug = $_GET['issue'];
$articleName = $_GET['article'];

$args = array(
    'post_type' => 'simplemag-issue',
    'name'=> $issueSlug
);
$issueQ = new WP_Query($args);
$issueQ->the_post();

$issue = array();
$cover = wp_get_attachment_image_src( get_post_thumbnail_id(), 'full' );
$issue['cover'] = $cover[0];
$issue['title'] = get_the_title();
$issue['id'] = get_the_ID();
$issue['name'] = $issueSlug;


$output = '';
$title = $issue['title'];
$prev_post = null;
$next_post = null;

if(!$issue['id']){
     $output .= '<div class="header"><h1>Error</h1></div>';
     $output .= '<div class="content">';
     $output .= 'No issue with the name "'.$issueSlug.'" was found';
     $output .= '</div>';
}
elseif(empty($articleName) || $articleName == 'cover'){
    $prev_post = null;
    $next_post = 'toc';
    $output = '<div class="content" style="padding:0px;">
    		    <a href="'.articleUrl('toc').'"><img src="'.$issue['cover'].'" class="fitToWidth" /></a>
    		</div>';
}
elseif($articleName == 'toc'){
    $args = array(
    'post_type' => 'simplemag-article',
    'meta_key'=> 'simplemag_issue',
      'meta_value' => $issue['id'],
      'orderby'=> 'menu_order'
      );
    $articles = new WP_Query($args);
    $output .= '<div class="header"><h1>Table of Contents</h1></div>';
    $output .= '<div class="content">';
    $output .= '<ol class="toc">';
    while ( $articles->have_posts() ) :
    	$articles->the_post();
    	$post_data = get_post(get_the_ID(), ARRAY_A);
    	$output .= '<li><a href="'.articleUrl($post_data['post_name']).'"><div class="img article-'.get_the_ID().'"></div>'.get_the_title().'</a></li>';
    	
    	if(!$next_post) $next_post = $post_data['post_name'];
    endwhile;
    $output .= '</ol>';
    $output .= '</div>';
    
    $prev_post = 'cover';
    $title .= ' - Table of Contents';
}
else{
    
    $args = array(
    'post_type' => 'simplemag-article',
    'meta_key'=> 'simplemag_issue',
      'meta_value' => $issue['id'],
      'orderby'=> 'menu_order'
      );
    $articles = new WP_Query($args);
    $i = 0;
    while ( $articles->have_posts() ) :
    	$articles->the_post();
    	$post_data = get_post(get_the_ID(), ARRAY_A);
    	$temp = $post_data;
    	
        if($articleName == $post_data['post_name']){
            $output .= '<div class="header article-'.get_the_ID().'">';
            $output .= '<h1>'.get_the_title().'</h1>';
            $output .= '</div>';
            $output .= '<div class="content">';
            $output .= get_the_content();
            $output .= '</div>';
            
            $title .= ' - '.get_the_title();
            
            $prev_post = $articles->posts[$i-1]->post_name;
            $next_post = $articles->posts[$i+1]->post_name;
        }
        $i++;
    endwhile;
    if(!$prev_post) $prev_post = 'toc';
} 

?><!DOCTYPE html>
<html>
<head>
	<title><?php echo $title; ?></title>
	<meta charset="utf-8">
	<meta name="viewport" content="initial-scale=1.0"/>
	<?php if($googleAnalytics = get_option('simplemag-apple-app-id')){ ?>
	<meta name="apple-itunes-app" content="app-id=556057345">
	<?php } ?>
	
	<link rel="stylesheet" type="text/css" href="<?php echo SIMPLEMAG_URL; ?>css/simplemag.css" />
	<?php if($googleAnalytics = get_option('simplemag-google-analytics')){ ?>
    	<script type="text/javascript">
          var _gaq = _gaq || [];
          _gaq.push(['_setAccount', '<?=$googleAnalytics?>']);
          _gaq.push(['_trackPageview']);
        
          (function() {
            var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
            ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
            var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
          })();
        
        </script>
    <?php } ?>

<style>
    <?php
    $args = array(
        'post_type' => 'simplemag-article',
        'meta_key'=> 'simplemag_issue',
          'meta_value' => $issue['id'],
          'orderby'=> 'menu_order'
          );
        $articles = new WP_Query($args);
        while ( $articles->have_posts() ) :
            $articles->the_post();
            $img = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full' );
        	echo '.article-'.get_the_ID().'{ ';
        	echo ($img[0])?'background-image:url('.$img[0].') !important;':'';
        	echo '}';
        endwhile;
        wp_reset_postdata();
    ?>
    
    </style>

</head>
<body>		
	<a href="<?php echo articleUrl($prev_post); ?>" id="prevPage" <?php echo(!$prev_post)?' class="hidden"':'';?>></a>
	<a href="<?php echo articleUrl($next_post); ?>" id="nextPage" <?php echo(!$next_post)?' class="hidden"':'';?>></a>
	<div id="loadContent">
    	<div id="page">
            <?php echo $output; ?>
    	</div>
	</div>
</body>
</html>