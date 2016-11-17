<?php
/* 
 * @version 1.2
 * @package 1.2
 * @copyright 2013 OrdaSoft
 * @author 2012 Emleninov Anatoliy 
 * @description OrdaSoft template module - frontend features settings
 * Homepage: http://www.ordasoft.com
 */
if (!defined('_VALID_MOS') && !defined('_JEXEC'))
    die('Direct Access to ' . basename(__FILE__) . ' is not allowed.');
$app = &JFactory::getApplication();
$templateq = $app->getTemplate(true); 

$params = new JRegistry;
$params->loadString($templateq->params);

$categories = $params->toArray(); //print_r($template); exit;
// an array of possible fonts
$params_H = array('Abel', 'Arial', 'Dosis', 'Droid Sans', 'Francois One', 'Lato', 'Lobster', 
'Lora', 'Open Sans Condensed', 'Open Sans', 
'Oswald', 'Oxygen', 'PT Sans Narrow', 'PT Sans', 'Prosto One', 'Quicksand', 'Roboto Condensed', 'Share', 
'Source Sans Pro', 'Ubuntu Condensed', 'Ubuntu');
// matching array of fonts with an array of all defined parameters
$sort_H = array();
foreach ($params_H as $key2=> $val2){
	foreach ($categories as $key=> $val){if(strstr($val,$val2)!=''){$i = strstr($val,$val2);$sort_H[$key]=$i;}}
}
foreach ($sort_H as $key=> $val){$key = substr($key,0,strlen($key)-5);} 
$url = JURI::base();
$doc = JFactory::getDocument();
$app = JFactory::getApplication();
$doc->addScript($url."modules/".$app->scope."/js/colorpicker.js");
$doc->addScript($url."modules/".$app->scope."/js/eye.js");
$doc->addScript($url."modules/".$app->scope."/js/layout.js");
$doc->addScript($url."modules/".$app->scope."/js/utils.js");
$doc->addStyleSheet($url."modules/".$app->scope."/css/colorpicker.css");
$doc->addStyleSheet($url."modules/".$app->scope."/css/layout.css");
?>
<script type="text/javascript">	
var selected_fonts_value=0;var selected_fonts=0;
jQuery(document).ready(function(){
		colorpickerHolder();
		jQuery('div.demoPatterns div').click(function()
			{
				jQuery('div.demoPatterns div').removeClass('active_pattern');jQuery(this).addClass('active_pattern');
			});
});
function colorpickerHolder(){
	jQuery('#colorSet').ColorPicker({flat: true});
}
function slideColorSet(){
	jQuery('#colorSet').toggleClass('open');
	jQuery('#colorSet').hasClass('open')?jQuery('#colorSet').animate({'left': '230px'}):jQuery('#colorSet').animate({'left': '-355px'})
}
function slideAllModule(){
	if (jQuery('#bodyFrame').find('div').hasClass('arrow_open')) {
		jQuery('.arrow_open').removeClass().addClass('arrow_close');

	} else {
		jQuery('.arrow_close').removeClass().addClass('arrow_open');
		jQuery('#colorSet').toggleClass('open').animate({'left': '-355px'},'fast');
	}
		jQuery('#bodyFrame').toggleClass('leftBodyFrame').animate({'left': '0px'},'normal');
		jQuery('.leftBodyFrame').animate({'left': '-196px'});
}
function addPattern(i){
	jQuery('body').removeClass().addClass('pattern'+i);
};
//////////////////////////////////////////////////////////////////////////
function addFont(value,font){
	if (selected_fonts=='links') {selected_fonts='a';}
	if (selected_fonts=='nav_links') {selected_fonts='.main_menu .menu li a';}
	if (selected_fonts=='top_menu') {selected_fonts='.top_menu .menu li a';}
	if (selected_fonts=='menu') {selected_fonts='.menu li a';}
	if (selected_fonts=='footer') {selected_fonts='.footer a';}
	if (selected_fonts_value!=null && selected_fonts!=null) 
		{
			jQuery(selected_fonts).css('font-family',selected_fonts_value);
			styleSelect = jQuery('.selectVariants').val();
			if(styleSelect != null) {
			console.log("STYLE SELECTED: "+styleSelect);
				var style = styleSelect; style = style.slice(3,10); 
				var weight = styleSelect; weight = weight.slice(0,3);
				jQuery(selected_fonts).css('font-style',style);
				jQuery(selected_fonts).css('font-weight',weight);
			}
		}
};
////////////////////////////////////////////////////////////////////////
function changeFunc() {
    var selectBox = document.getElementById("selectBox");
    var selectedValue = selectBox.options[selectBox.selectedIndex].text;
    selected_fonts_value=selectedValue;
/////////////////|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
jQuery.getJSON('<?php  echo JURI::base() ."modules/".end(explode(DIRECTORY_SEPARATOR,dirname(__FILE__)))."/js/fonts.json"; ?>', 
function(data) {
  var items = [];
  jQuery.each(data.items, function(key, val) {
   if(val.family == selected_fonts_value) {
	jQuery.each(val.variants, function(key,val) {
	if(!/[^[0-9]/.test(val)) {
		items.push('<option value="' + val + 'normal">' + val + ' normal</option>');
		weight = val;
		style = "normal";
	 } else {
		switch (val) {
			case "regular" : {
				val = "400normal";
				break;
			}
			case "italic" : {
				val = "400italic";
				break;
			}
			default : {
				break;
			}
		}
		var weight = val.slice(0,3);
		var style = val.slice(3,10);
		items.push('<option value="' +val+ '">' + weight + ' '+style+'</option>');
	 }
	});
	tmp=items.join('');
	jQuery('.selectVariants').html(tmp);
	return;
   }
  });
  addFont(selected_fonts_value,selected_fonts);
});
}
////////////////////////////////////////////////////////////////////////
function selectTarget(tar){ /// dlya kogo
	var arrayClasses = [<?php
    foreach ($sort_H as $key=>$val) {
                    $key = substr($key,0,strlen($key)-5);
                    if($key == "links")
                        $key = "a";
					echo "'".$key."',";
                     }
                    ?>];
    if (arrayClasses[tar] !== undefined) {
                jQuery('div.select_bg.second select option').attr('value',arrayClasses[tar]);
                                        jQuery('div.select_bg.second').addClass(arrayClasses[tar]);
                                        jQuery('.'+arrayClasses[tar]).css('display','block');
										selected_fonts=arrayClasses[tar];
										addFont(selected_fonts_value,selected_fonts);
    } else  {jQuery(element).css({'display':'none'});}
};
//-----Add fonts from google-font(script)-----//
//-----Fonts add in array, copy font in google-fonts(script)
//-----and pasted it in to the end of an array
//-----for sample:
//-----WebFontConfig = {
//-----			google: { families: [ 'Oxygen::latin','Open+Sans+Condensed:300:latin','Share::latin',
//-----					'Quicksand::latin','Droid+Sans::latin','Dosis::latin','Wire+One::latin', 
//-----					'Prosto+One::latin', 'some+your::font'] }
//-----			};
WebFontConfig = {
  google: { families: [ 'Oxygen::latin','Open+Sans+Condensed:300:latin','Share::latin',
						'Quicksand::latin','Droid+Sans::latin','Dosis::latin','Wire+One::latin', 'Prosto+One::latin'] }
};

(function() {var wf = document.createElement('script');wf.src = ('https:' == document.location.protocol ? 'https' : 'http') +
    '://ajax.googleapis.com/ajax/libs/webfont/1/webfont.js';wf.type = 'text/javascript';wf.async = 'true';
  var s = document.getElementsByTagName('script')[0];s.parentNode.insertBefore(wf, s);
})();
</script>
<style>
.pattern1 {background-image: url('<?php echo $url."templates/".$app->getTemplate()."/images/01.png" ?>') !important;}
.pattern2 {background-image: url('<?php echo $url."templates/".$app->getTemplate()."/images/02.png" ?>') !important;}
.pattern3 {background-image: url('<?php echo $url."templates/".$app->getTemplate()."/images/03.png" ?>') !important;}
.pattern4 {background-image: url('<?php echo $url."templates/".$app->getTemplate()."/images/04.png" ?>') !important;}
.pattern5 {background-image: url('<?php echo $url."templates/".$app->getTemplate()."/images/05.png" ?>') !important;}
.demoPattern1 {background-image: url('<?php echo $url."templates/".$app->getTemplate()."/images/01.png" ?>') !important;}
.demoPattern2 {background-image: url('<?php echo $url."templates/".$app->getTemplate()."/images/02.png" ?>') !important;}
.demoPattern3 {background-image: url('<?php echo $url."templates/".$app->getTemplate()."/images/03.png" ?>') !important;}
.demoPattern4 {background-image: url('<?php echo $url."templates/".$app->getTemplate()."/images/04.png" ?>') !important;}
.demoPattern5 {background-image: url('<?php echo $url."templates/".$app->getTemplate()."/images/05.png" ?>') !important;}
.demoPattern6 {background-image: url('<?php echo $url."templates/".$app->getTemplate()."/images/06.png" ?>') !important;}
</style>
<div id="colorSet"><!--don't change--></div>
<div id="bodyFrame" class="leftBodyFrame">
	<div class="blockMain">
		<div class="wrapper_arrow">
			<div class="arrow_open" onclick="slideAllModule();"></div>
		</div>
		<div class="blockPattern">
			<h4>Choose Your Pattern</h4>
			<div class="demoPatterns">
				<div class="demoPattern1" title="1" onclick="addPattern(this.title);"></div>
				<div class="demoPattern2" title="2" onclick="addPattern(this.title);"></div>
				<div class="demoPattern3" title="3" onclick="addPattern(this.title);"></div>
				<div class="demoPattern4" title="4" onclick="addPattern(this.title);"></div>
				<div class="demoPattern5" title="5" onclick="addPattern(this.title);"></div>                    
			</div>
			<div style="position:relative;">
				<h4>Change background color</h4>
				<div id="colorSelector2">
					<div style="background-color: #000"></div>
				</div>
	            <div id="colorpickerHolder2" onclick="colorpickerHolder()"></div>
			</div>
			<form class="formFrame">
				<h4>Change Fonts</h4>
				<div class="select_bg">
					<select class="selectFonts" onchange="selectTarget(this.value);">		
						<?php  $i=0;
						foreach ($sort_H as $key=>$val) {
						echo '<option value="'.$i.'" onclick="selectTarget(this.value);">'.substr($key,0,strlen($key)-5).'</option>';
						$i++; }
						?>
					</select>
				</div>
				<div class="select_bg second">
					<select class="selectFonts" id="selectBox" onchange="changeFunc();">
						<?php 
						foreach($params_H as $key=>$val ){
						echo'<option value="'.$key.'" label="'.$val.'">'.$val.'</option>';
						}?>					
					</select>
				</div>	
				<div class="select_bg third">	
					<select class="selectVariants" id="selectBox" onchange="addFont();">
										
					</select>
				</div>
			</form>
			<div style="margin:82px 0px 6px 0px;">
				<a href="http://www.ordasoft.com"><img src='<?php  echo $url."modules/".$app->scope."/images/ordasoft-logo.png"; ?>' width="107" height="17" alt="OrdaSoft"/></a>
			</div>
		</div>
	</div>
</div>

