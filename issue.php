<?php
require_once('../../../wp-load.php' );

wp();
	
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
    		    <a onclick="gotoPage(1);" href="toc/"><img src="'.$issue['cover'].'" class="fitToWidth" /></a>
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
    	$output .= '<li><a onclick="return gotoArticle('.get_the_ID().');" href="'.$post_data['post_name'].'"><div class="img article-'.get_the_ID().'"></div>'.get_the_title().'</a></li>';
    	
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
	<meta name="apple-itunes-app" content="app-id=556057345">
	
	<link rel="stylesheet" type="text/css" href="<?php echo SIMPLEMAG_URL; ?>css/simplemag.css" />
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js"></script>
	<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-34862517-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>

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
    		
	<a href="<?php echo $prev_post; ?>" id="prevPage" <?php echo(!$prev_post)?' class="hidden"':'';?>></a>
	<a href="<?php echo $next_post; ?>" id="nextPage" <?php echo(!$next_post)?' class="hidden"':'';?>></a>
	<div id="loadContent">
    	<div id="page">
            <?php echo $output; ?>
    	</div>
	</div>
	<script>
	    var ajaxurl = "http:\/\/christofferok.com\/wp-admin\/admin-ajax.php";
	    var issue = <?php echo json_encode($issue); ?>;
		var currentPage = 0;
        <?php
        $postIDs = array('"cover"','"TOC"');
        // Get all issues
        $args = array(
        'post_type' => 'simplemag-article',
        'meta_key'=> 'simplemag_issue',
          'meta_value' => $issue['id'],
          'orderby'=> 'menu_order'
          );
        $articles = new WP_Query($args);
        while ( $articles->have_posts() ) :
        	$articles->the_post();
        	$postIDs[] = '"'.get_the_ID().'"';
        endwhile;
        ?>
        var pages = [<?php echo implode(",", $postIDs); ?>];
        $("#prevPage").click(function(){ return true; prevPage(); return false; });
        $("#nextPage").click(function(){ return true; nextPage(); return false; });
        
        
        function nextPage()
        {
        	if(currentPage < pages.length-1)
        	{
        	    console.log(currentPage);
        		currentPage++;
	        	loadArticle(pages[currentPage]);
        	}
        }
        
        function prevPage()
        {
        	if(currentPage > 0)
        	{
	        	loadArticle(pages[--currentPage]);
        	}
        }
        
        function gotoPage(pageNumber)
        {
	        currentPage = pageNumber;
	        loadArticle(pages[currentPage]);
        }
        
        function gotoArticle(articleID)
        {
            return true; //Disable ajax navigation
            
            page = 0;
            for(p in pages){
                if(pages[p] == articleID){
                    currentPage = page;
                    console.log(page,pages);
        	        loadArticle(pages[currentPage]);
        	        return false;
                }
                page++;
            }
            return false;
	        
        }
        
		function loadArticle(articleID)
		{
		    var data = {
        		action: 'getsimplemagPage',
        		issue: issue.id,
        		postID: articleID
        	};
        	// We can also pass the url value separately from ajaxurl for front end AJAX implementations
        	jQuery.post(ajaxurl, data, function(response) {
        		$("#page").html(response.html);
        		console.log(response);
        		window.history.pushState(null,issue.title+" - "+response.title, "/issue/"+issue.name+"/"+response.name);
        	},'json');
			scrollToTop();
			showHideArrows();
		}
		
		function scrollToTop()
		{
			$('body,html').animate({ scrollTop: 0 }, 500);
		}
		
		function showHideArrows()
		{
			if(currentPage <= 0){ $("#prevPage").hide(); }
			else{ $("#prevPage").show(); }
			
			if(currentPage >= (pages.length-1)){ $("#nextPage").hide(); }
			else{ $("#nextPage").show(); }
			
		}
	</script>
	
	
</body>
</html>