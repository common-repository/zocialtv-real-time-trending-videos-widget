<?php
/*
Plugin Name: ZOCIAL.tv
Plugin URI: http://zocial.tv
Description: Top Trending Videos shared on Twitter & Facebook. <br></br> Use this widget to show trending Video Ranks in your side-bar. ZOCIAL.tv ranks updated every hour. Customize Video Category, quantity of videos to show, font color & size... 
Version: 1.0
Author: ZOCIAL.tv
Author URI: http://ZOCIAL.tv
*/
error_reporting(E_ALL);
add_action("widgets_init", array('zocialtv_wp_widget', 'register'));
register_activation_hook( __FILE__, array('zocialtv_wp_widget', 'activate'));
register_deactivation_hook( __FILE__, array('zocialtv_wp_widget', 'deactivate'));

  function showMyWidget(){
  	$data = get_option('zocialtv_wp_widget');
	$rank = getLastRanking();
	$widget = $rank.' 
	<script type="text/javascript" src="http://widget.zocial.tv/resources/wpwidget.js"></script>'.
	$data['cachedhtml'].'

	</div>	
	<script type="text/javascript">
	try{new ViralVideoChart({title: "'. (($data['category'] !== "All" ) ? $data['category'] : "") .' Videos Trending Today", 
	numberToShow: "'.$data['numbertoshow'].'", 
	width: "", 
	background: "'.$data['backcolor'].'", 
	fontColor: "'.$data['fontcolor'].'", 
	ruleColor: "#CCCCCC", 
	hoverBackground: "#F2F1EC", 
	titleFontSize: "12px", 
	fontSize: "'.$data['fontsize'].'", 
	showThumbnails: true, 
	thumbnailScale: "'.$data['thumbscale'].'", 
	numbersOnThumb: false, 
	showChartPos: true, 
	entrySpacing: "5", 
	feedUrl: "/", 
	SITE_URL: "http://zocial.tv"},"vvc_widgets_1");
    }catch(e){}
	</script>';
	echo $widget;
  }
  
  function getTitle(){
  	$data = get_option('zocialtv_wp_widget');
  	echo (($data['category'] !== "All" ) ? $data['category'] : "") .' Videos Trending Today';
  }
  
  function getLastRanking(){
  	$data = get_option('zocialtv_wp_widget');
  	$now = time();
  	if(($now - $data['cachestamp']) > 3600 || $data['category'] !== $data['cachedcategory']){
  		//new category or old data so.. => cached for next 60 minutes
  		$cachedCategory = categoryToCache();
  	} else {
  		$cachedCategory = $data['ranking'];
  	}
	return '<script type="text/javascript"> '.$cachedCategory.'</script>';
  }
  
  function categoryToCache(){
  	    $data = get_option('zocialtv_wp_widget');
	  	if( !class_exists( 'WP_Http' ) )
	    	include_once( ABSPATH . WPINC. '/class-http.php' );
	    $request = new WP_Http;
		$result = $request->request( 'http://widget.zocial.tv/category/'.$data['category']);
		if($result !== null && $result['response']['code'] == '200'){
			$data['ranking'] = $result['body'];
			$data['cachestamp'] = time();
			$data['cachedcategory'] = $data['category'];
			$data['cachedhtml'] = generateHTML($data);						
			update_option('zocialtv_wp_widget', $data);
		}				
		return $data['ranking'];
  }

  function generateHTML($data){
  	$start =strpos($data['ranking'],'{');
	$aux = substr($data['ranking'],$start);
	$json = json_decode($aux,true);	    		
  	$html = '
	<div id="vvc_widgets_1" style="overflow-x: hidden; overflow-y: hidden; font-family: verdana; font-size: 11px; color: rgb(119, 119, 119); background-image: initial; background-attachment: initial; background-origin: initial; background-clip: initial; background-color: '.$data['backcolor'].'; background-position: initial initial; background-repeat: initial initial; " class="VVCWidget"><img src="http://zocial.tv/resource/img/spinner.gif" id="vvc_spinner_1" style="display:none; float: right">
	<div id="vvc_title_1" style="color: #777; font-size: 12px; font-weight: bold; text-align: center; border-bottom: solid #CCCCCC 2px; padding-bottom: 4px"></div>
	<div id="vvc_feed_display_1">
	<table width="100%" cellspacing="0" cellpadding="1" style="color: rgb(119, 119, 119); font-size: 11px; font-family: verdana; ">
	<tbody>';
	for($i=1; $i <= $data['numbertoshow']; $i++){		
		$itemTitle = $json['entries'][$i-1]["title"];	
		$itemLink = $json['entries'][$i-1]["link"];		
		$itemThumb = $json['entries'][$i-1]["thumbnailUrl"];
		$thumbW = 120*$data['thumbscale'];
		$thumbH = round(90*$data['thumbscale']);		
		$itemToShow ='
		<tr style="cursor: pointer; background-image: initial; background-attachment: initial; background-origin: initial; background-clip: initial; background-color: transparent; background-position: initial initial; background-repeat: initial initial; ">
		<td style="padding-right: 0px; padding-left: 0px; padding-top: 3px; padding-bottom: 3px; margin-top: 0px; margin-right: 0px; margin-bottom: 0px; margin-left: 0px; text-align=center;" width="'.($data['thumbscale']+5).'" height="'.$thumbH.'">
		<div style="position: relative; ">
		<a href="'.$itemLink.'" style="color: rgb(119, 119, 119); text-decoration: none; ">
		<img src="'.$itemThumb.'" width="'.$thumbW.'" height="'.$thumbH.'" border="0">
		</a>
		</div>
		</td>
		<td style="padding-right: 0px; margin-top: 0px; margin-right: 0px; margin-bottom: 0px; margin-left: 0px; padding-top: 3px; padding-bottom: 3px; padding-left: 4px; vertical-align=top;">
		<a href="'.$itemLink.'" style="text-decoration:none;color: '.$data['fontcolor'].'; font-size: '.$data['fontsize'].'; font-family: verdana; vertical-align:top;">'.$i." - ".$itemTitle.'</a>
		</td>
		</tr>';		
		$html .= $itemToShow;	
	}
	$html .= '
	</tbody>
	</table>
	</div>
	<div style="padding: 2px 2px 5px 2px; text-align: right">  
	<span id="vvc_prev_1">&lt; prev '.$data['numbertoshow'].' | </span> 
	<span id="vvc_next_1">
	<a href="#" id="vvc_next_a_1" style="color: #777;">next '.$data['numbertoshow'].' &gt;</a>
	</span>
	</div>	
	';
 	return $html;
  }
  
  class zocialtv_wp_widget {

  function activate(){
    $data = array( 'title' => 'Videos Trending Today' ,'numbertoshow' => 5,'category'=>'All',
    			   'thumbscale'=>'0.75','fontsize'=>'','backcolor'=>'transparent','fontcolor'=>'#777','ranking'=>'','cachedcategory'=>'',
    				'cachestamp'=>0,'cachedhtml'=>'');
    if ( ! get_option('zocialtv_wp_widget')){
      add_option('zocialtv_wp_widget' , $data);
    } else {
      update_option('zocialtv_wp_widget' , $data);
    }
    categoryToCache();
    if( !class_exists( 'WP_Http' ) )
	    	include_once( ABSPATH . WPINC. '/class-http.php' );
	$request = new WP_Http;
	$result = $request->request( 'http://widget.zocial.tv/activate/on/?site='.get_home_url());

  }
  
  function deactivate(){
  	if( !class_exists( 'WP_Http' ) )
	    	include_once( ABSPATH . WPINC. '/class-http.php' );
	$request = new WP_Http;
	$result = $request->request( 'http://widget.zocial.tv/activate/off/?site='.get_home_url());
    delete_option('zocialtv_wp_widget');
  }

  function widget($args){  	
  	$data = get_option('zocialtv_wp_widget');
    echo $args['before_widget'];
    echo $args['before_title'] . $data['title'] . $args['after_title'];
    showMyWidget();
    echo $args['after_widget'];
  }
  
  function register(){
    register_sidebar_widget('ZOCIAL.tv - Trending Videos', array('zocialtv_wp_widget', 'widget'));
    register_widget_control('ZOCIAL.tv - Trending Videos', array('zocialtv_wp_widget', 'control'));
  }
 
  function control(){
	  $data = get_option('zocialtv_wp_widget');
	  $cats= array('All','Animals','Autos','Comedy','Education','Entertainment','Film', 'Games','Howto',
				'Music','News','Nonprofit','People','Shows','Sports','Tech','Travel');
	  $fontsizes = array('9px','10px','11px','12px');
	  ?>				
		<p><label>Category</label>
					<select name="zocialtv_wp_widget_category" style="float:right;"><optgroup label="Category"></optgroup>
						<?php foreach($cats as $eachCategory){
							if ($data['category'] == $eachCategory) 
								echo '<option selected="selected">'.$eachCategory.'</option>';
							else
								echo '<option>'.$eachCategory.'</option>';
						}	
						?>					
					</select>
		</p>
		<p><label>Videos to Show<input name="zocialtv_wp_widget_numbertoshow" type="text" style="float:right;width:50px;" value="<?php echo $data['numbertoshow']; ?>" /></label></p>
		<p><label>Thumb Scale (0.75 = 75%)<input name="zocialtv_wp_widget_thumbscale" type="text" style="float:right;width:50px;" value="<?php echo $data['thumbscale']; ?>" /></label></p>
		<p><label>Background Color<input name="zocialtv_wp_widget_backcolor" type="text" style="float:right;width:60px;" value="<?php echo $data['backcolor']; ?>" /></label></p>
		<p><label>Font Color<input name="zocialtv_wp_widget_fontcolor" type="text" style="float:right;width:60px;" value="<?php echo $data['fontcolor']; ?>" /></label></p>		
		<p><label>Font Size (px)
					<select name="zocialtv_wp_widget_fontsize" style="float:right;"><optgroup ></optgroup>
						<?php foreach($fontsizes as $size){
							if ($data['fontsize'] == $size) 
								echo '<option selected="selected">'.$size.'</option>';
							else
								echo '<option>'.$size.'</option>';
						}	
						?>					
					</select>
		</p>										
		<hr></hr>
		<p style="font-weight:bold; font-size:1.1em;color:#333;">Widget Preview</p>		
		<div style="width:100%"><?php echo $data['cachedhtml']?></div></div>
	 <?php
	   if (isset($_POST['zocialtv_wp_widget_category'])){	    
	    $data['numbertoshow'] = attribute_escape($_POST['zocialtv_wp_widget_numbertoshow']);
	    $data['thumbscale'] = attribute_escape($_POST['zocialtv_wp_widget_thumbscale']);
	    $data['fontsize'] = attribute_escape($_POST['zocialtv_wp_widget_fontsize']);
	    $data['fontcolor'] = attribute_escape($_POST['zocialtv_wp_widget_fontcolor']);
	    $data['backcolor'] = attribute_escape($_POST['zocialtv_wp_widget_backcolor']);
	    $data['category'] = attribute_escape($_POST['zocialtv_wp_widget_category']);
	    $newTitle =  ((attribute_escape($_POST['zocialtv_wp_widget_category']) !== "All" ) ? attribute_escape($_POST['zocialtv_wp_widget_category']) : "") .' Videos Trending Today';
	    $data['title'] = $newTitle;
	    update_option('zocialtv_wp_widget', $data);
	    categoryToCache();
	    $data = get_option('zocialtv_wp_widget');
	    $data['cachedhtml'] = generateHTML($data);
	    update_option('zocialtv_wp_widget', $data);	    
	  }
  }
}

?>