<?php
/*
* @version 1.1 PRO
* @package social sharing
* @copyright 2012 OrdaSoft
* @author 2012 Andrey Kvasnekskiy (akbet@ordasoft.com )
* @description social sharing, sharing WEB pages in LinkedIn, FaceBook, Twitter and Google+ (G+)
*/
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
$document = JFactory::getDocument();
$docType = $document->getType();
$_fb = 0;
$_google = 0;
$_tw = 0;
$_in = 0;
$document = JFactory::getDocument();
$enable_like = $params->get('enable_like');
$enable_share = $params->get('enable_share');
$enable_comments = $params->get('enable_comments');
$enable_twitter = $params->get('enable_twitter');
$enable_google = $params->get('enable_google');
$enable_in = $params->get('enable_in');
if ($params->get('auto_language')) {
    $language = str_replace('-', '_', JFactory::getLanguage()->getTag());
} else {
    $language = $params->get('language');
}
$enable_app = $params->get('enable_app');
$app_id = $params->get('app_id');
$type = $params->get('type');
$meta = "";
$title = $document->getTitle();
$uri = JURI::getInstance();
$url = $uri->toString();
if (($enable_share == 1) || ($enable_like == 1) || ($enable_comments == 1) || ($enable_google == 1) || ($enable_twitter == 1) || ($enable_in == 1)) {
    $head_data = array();
    foreach($document->getHeadData() as $tmpkey => $tmpval) {
        if (!is_array($tmpval)) {
            $head_data[] = $tmpval;
        } else {
            foreach($tmpval as $tmpval2) {
                if (!is_array($tmpval2)) {
                    $head_data[] = $tmpval2;
                }
            }
        }
    }
    $head = implode(',', $head_data);
    if (($enable_share == 1) || ($enable_like == 1) || ($enable_comments == 1)) {
        $meta.= "<meta property=\"og:description\" content=\"" . $document->getMetaData("description") . "/>" . PHP_EOL;
    }
    if (preg_match('/<meta property="og:url"/i', $head) == 0) {
        $meta.= "<meta property=\"og:url\" content=\"$url\"/>" . PHP_EOL;
    }
    if (preg_match('/<meta property="og:title"/i', $head) == 0) {
        $meta.= "<meta property=\"og:title\" content=\"$title\"/>" . PHP_EOL;
    }
    if (preg_match('/<meta property="my:fb"/i', $head) == 0) {
        $meta.= "<meta property=\"my:fb\" content=\"on\"/>" . PHP_EOL;
        $_fb = 1;
    } else {
        $_fb = 2;
    }
}
if ($enable_google == 1) {
    if (preg_match('/<meta property="my:google"/i', $head) == 0) {
        $meta.= "<meta property=\"my:google\" content=\"on\"/>" . PHP_EOL;
        $_google = 1;
    } else {
        $_google = 2;
    }
}
if ($enable_twitter == 1) {
    if (preg_match('/<meta property="my:tw"/i', $head) == 0) {
        $meta.= "<meta property=\"my:tw\" content=\"on\"/>" . PHP_EOL;
        $_tw = 1;
    } else {
        $_tw = 2;
    }
}
if ($enable_in == 1) {
    if (preg_match('/<meta property="my:in"/i', $head) == 0) {
        $meta.= "<meta property=\"my:in\" content=\"on\"/>" . PHP_EOL;
        $_in = 1;
    } else {
        $_in = 2;
    }
}
if ($meta != "") {
    $document->addCustomTag($meta);
}
if (($enable_like == 1) || ($enable_share == 1) || ($enable_comments == 1)) {
    if ($_fb == 1) {
          $FbCode = "
        var js, fjs = document.getElementsByTagName('script')[0];
        if (!document.getElementById('facebook-jssdk')) {
        js = document.createElement('script');
        js.id = 'facebook-jssdk';
        js.async = true;
        js.src = '//connect.facebook.net/".$language."/all.js#xfbml=1';
        fjs.parentNode.insertBefore(js, fjs);
        }";
        $document->addScriptDeclaration($FbCode);
        } else {
        $document->addScript("//connect.facebook.net/$language/all.js#xfbml=1");
    }
}
if ($enable_twitter == 1) {
    if ($_tw == 1) {
        $TwCode = "
          var js,fjs=document.getElementsByTagName('script')[0];
          if(!document.getElementById('twitter-wjs')){
            js=document.createElement('script');
            js.id='twitter-wjs';
            js.async=true;
            js.src=\"//platform.twitter.com/widgets.js\";
            fjs.parentNode.insertBefore(js,fjs);
          }";
        $document->addScriptDeclaration($TwCode);
    } else {
        $document->addScript("//platform.twitter.com/widgets.js");
    }
}
if ($enable_google == 1) {
    if ($_google == 1) {
        if ($params->get('auto_language')) {
            $language_google = JFactory::getLanguage()->getTag();
        } else {
            $language_google = $params->get('language_google', 'en-US');
        }
        $GoogleCode = "
          var js,fjs=document.getElementsByTagName('script')[0];
          if(!document.getElementById('google-wjs')){
            js=document.createElement('script');
            js.id='google-wjs';
            js.async=true;
            js.src=\"//apis.google.com/js/plusone.js\";
            js.text={lang: '" . $language_google . "'}
            fjs.parentNode.insertBefore(js,fjs);
          }";
        $document->addScriptDeclaration($GoogleCode);
    }
}
if ($enable_in == 1) {
    if ($_in == 1) {
        $InCode = "
          var js,fjs=document.getElementsByTagName('script')[0];
          if(!document.getElementById('linkedin-js')){
            js=document.createElement('script');
            js.id='linkedin-js';
            js.async=true;
            js.src=\"//platform.linkedin.com/in.js\";
            fjs.parentNode.insertBefore(js,fjs);
          }";
        $document->addScriptDeclaration($InCode);
    }
}
$htmlCodeBut = '';
if ((($enable_like == 1) || ($enable_share == 1) || ($enable_twitter == 1) || ($enable_google == 1) || ($enable_in == 1))) {
    
    $code_like = '';
    $code_share = '';
    $code_twitter = '';
    $code_google = '';
    $code_in = '';
    //FB like button
    if ($enable_like == 1) {
        $layout_style = $params->get('layout_style', 'button_count');
        $verb_to_display = $params->get('verb_to_display', '1');
        if ($verb_to_display == 1 || $verb_to_display == "like") {
            $verb_to_display = "like";
        } else {
            $verb_to_display = "recommend";
        }
        $font = $params->get('font');
        $color_scheme = $params->get('color_scheme', 'light');
        $tmp = "<fb:like href=\"$url\" layout=\"$layout_style\" show_faces=\"true\" send=\"true\" action=\"$verb_to_display\" font=\"$font\" colorscheme=\"$color_scheme\"></fb:like>";
        $tmp = $tmp . PHP_EOL;
        $code_like = "<div class=\"cmp_like_container\">$tmp</div>";
    }
    //Twitter button
    if ($enable_twitter == 1) {
        if ($params->get('auto_language')) {
            $language_twitter = substr(JFactory::getLanguage()->getTag(), 0, 2);
        } else {
            $language_twitter = $params->get('language_twitter', 'en');
        }
        $data_via_twitter = $params->get('data_via_twitter');
        $data_related_twitter = $params->get('data_related_twitter');
        $show_count_twitter = $params->get('show_count_twitter', 'horizontal');
        $hashtags_twitter = $params->get('hashtags_twitter', '');
        $asynchronous_twitter = $params->get('asynchronous_twitter', '0');
        $datasize_twitter = $params->get('datasize_twitter', 'medium');
        if ($language_twitter != "en") {
            $language_twitter = "data-lang=\"$language_twitter\"";
        } else {
            $language_twitter = '';
        }
        if ($data_via_twitter != "") {
            $data_via_twitter = "data-via=\"$data_via_twitter\"";
        } else {
            $data_via_twitter = '';
        }
        if ($data_related_twitter != "") {
            $data_related_twitter = "data-related=\"$data_related_twitter\"";
        } else {
            $data_related_twitter = '';
        }
        if ($hashtags_twitter != "") {
            $hashtags_twitter = "data-hashtags=\"$hashtags_twitter\"";
        }
        if ($datasize_twitter != "") {
            $datasize_twitter = "data-size=\"$datasize_twitter\"";
        }
        $tmp = "<a href=\"//twitter.com/share\" class=\"twitter-share-button\" ";
        $tmp.= "$language_twitter $data_via_twitter $hashtags_twitter $data_related_twitter ";
        $tmp.= "data-url=\"$url\" ";
        $tmp.= "data-text=\"$title\" ";
        $tmp.= "data-count=\"$show_count_twitter\">Tweet</a>";
        $tmp.= PHP_EOL;
        $code_twitter.= "<div class=\"cmp_twitter_container\" >$tmp</div>";
    }
    //Google +1 button
    if ($enable_google == 1) {
        $html5_google = $params->get('html5_google', '0');
        $size_google = $params->get('size_google', 'standard');
        $annotation_google = $params->get('annotation_google', 'bubble');
        if ($params->get('auto_language')) {
            $language_google = JFactory::getLanguage()->getTag();
        } else {
            $language_google = $params->get('language_google', 'en-US');
        }
        $container_google = $params->get('container_google', '1');
        if ($annotation_google != "bubble") {
            if ($html5_google) {
                $annotation_google = "data-annotation=\"$annotation_google\"";
            } else {
                $annotation_google = "annotation=\"$annotation_google\"";
            }
        } else {
            $annotation_google = "";
        }
        $tmp = "";
        if ($html5_google) {
            $tmp.= "<div class=\"g-plusone\" data-size=\"$size_google\" data-href=\"$url\" $annotation_google></div>";
        } else {
            $tmp.= "<g:plusone size=\"$size_google\" href=\"$url\" $annotation_google></g:plusone>";
        }
        $tmp = $tmp . PHP_EOL;
        $code_google.= "<div class=\"cmp_google_container\">$tmp</div>";
    }
    //FB share button
    if ($enable_share == 1) {
        $share_button_style = $params->get('share_button_style', 'button_count');
        switch ($share_button_style) {
            case "icontext":
                $tmp = "<script>function fbs_click() {u=$url;t=$title;window.open('//www.facebook.com/sharer.php?u=$url&amp;t=$title','sharer','toolbar=0,status=0,width=626,height=436');return false;}</script><style> html .fb_share_link { padding:2px 0 0 20px; height:16px; background:url(//static.ak.facebook.com/images/share/facebook_share_icon.gif?6:26981) no-repeat top left; }</style><a rel=\"nofollow\" href=\"//www.facebook.com/share.php?u=$url\" onclick=\"return fbs_click()\" share_url=\"$url\" target=\"_blank\" class=\"fb_share_link\">Share on Facebook</a>";
            break;
            case "button_count":
                $tmp = "<a name=\"fb_share\" type=\"button_count\" share_url=\"$url\" href=\"//www.facebook.com/sharer.php?u=$url&amp;t=$title\">Share</a><script src=\"//static.ak.fbcdn.net/connect.php/js/FB.Share\" type=\"text/javascript\"></script>";
            break;
            case "box_count":
                $tmp = "<a name=\"fb_share\" type=\"box_count\" share_url=\"$url\" href=\"//www.facebook.com/sharer.php?u=$url&amp;t=$title\">Share</a><script src=\"//static.ak.fbcdn.net/connect.php/js/FB.Share\" type=\"text/javascript\"></script>";
            break;
            case "text":
                $tmp = "<script>function fbs_click() {u=$url;t=document.title;window.open('//www.facebook.com/sharer.php?u=$url&amp;t=$title','sharer','toolbar=0,status=0,width=626,height=436');return false;}</script><a rel=\"nofollow\" href=\"//www.facebook.com/share.php?u=$url\" share_url=\"$url\" onclick=\"return fbs_click()\" target=\"_blank\">Share on Facebook</a>";
            break;
            case "icon":
                $tmp = "<script>function fbs_click() {u=$url;t=$title;window.open('//www.facebook.com/sharer.php?u=$url&amp;t=$title','sharer','toolbar=0,status=0,width=626,height=436');return false;}</script><style> html .fb_share_button { display: -moz-inline-block; display:inline-block; padding:1px 20px 0 5px; height:15px; border:1px solid #d8dfea; background:url(//static.ak.facebook.com/images/share/facebook_share_icon.gif?6:26981) no-repeat top right; } html .fb_share_button:hover { color:#fff; border-color:#295582; background:#3b5998 url(//static.ak.facebook.com/images/share/facebook_share_icon.gif?6:26981) no-repeat top right; text-decoration:none; } </style> <a rel=\"nofollow\" href=\"//www.facebook.com/share.php?u=$url\" share_url=\"$url\" class=\"fb_share_button\" onclick=\"return fbs_click()\" target=\"_blank\" style=\"text-decoration:none;\">Share</a>";
            break;
        }
        $tmp = $tmp . PHP_EOL;
        $code_share = "<div style=\"margin-right:20px;\" class=\"cmp_share_container\">$tmp</div>";
    }
    //LinkedIn button
    if ($enable_in == 1) {
        $data_counter_in = $params->get('data_counter_in', 'none');
        $data_showzero_in = $params->get('data_showzero_in', '0');
        if ($data_counter_in == "none") {
            $data_counter_in = "";
            $data_showzero_in = "";
        } else {
            $data_counter_in = "data-counter=\"$data_counter_in\"";
            if ($data_showzero_in == "0") {
                $data_showzero_in = "";
            } else {
                $data_showzero_in = "data-showzero=\"true\"";
            }
        }
        $tmp = "";
        $tmp.= "<script type=\"IN/Share\" data-url=\"$url\" $data_counter_in $data_showzero_in></script>";
        $tmp = $tmp . PHP_EOL;
        $code_in.= "<div class=\"cmp_in_container\">$tmp</div>";
    }
    $htmlCodeBut.= $code_share;
    $htmlCodeBut.= $code_twitter;
    $htmlCodeBut.= $code_like;
    $htmlCodeBut.= $code_google;
    $htmlCodeBut.= $code_in;
}
$htmlCode = "";
if ($enable_comments == 1) {
    $idrnd = 'fbcom' . rand();
    $number_comments = $params->get('number_comments');
    $width = $params->get('width_comments');
    $box_color = $params->get('box_color');
    $autofit = $params->get('autofit_comments', 0);
    $htmlCode.= "<div id=\"" . $idrnd . "\" class=\"cmp_comments_container\" >";
    $tmp = "";
    $tmp = "<fb:comments href=\"$url\" num_posts=\"$number_comments\" width=\"$width\" colorscheme=\"$box_color\"></fb:comments>";
    if ($autofit) {
        $tmps = "function autofitfbcom() {";
        $tmps.= "var efbcom = document.getElementById('" . $idrnd . "');";
        $tmps.= "if (efbcom.currentStyle){";
        $tmps.= "var pl=efbcom.currentStyle['paddingLeft'].replace(/px/,'');";
        $tmps.= "var pr=efbcom.currentStyle['paddingRight'].replace(/px/,'');";
        $tmps.= "var wfbcom=efbcom.offsetWidth-pl-pr;";
        $tmps.= "try {efbcom.firstChild.setAttribute('width',wfbcom);}";
        $tmps.= "catch(e) {efbcom.firstChild.width=wfbcom+'px';}";
        $tmps.= "} else {";
        $tmps.= "var pl=window.getComputedStyle(efbcom,null).getPropertyValue('padding-left' ).replace(/px/,'');";
        $tmps.= "var pr=window.getComputedStyle(efbcom,null).getPropertyValue('padding-right').replace(/px/,'');";
        $tmps.= "efbcom.childNodes[0].setAttribute('width',efbcom.offsetWidth-pl-pr);" . PHP_EOL;
        $tmps.= "}}";
        $tmps.= "autofitfbcom();";
        $tmp.= "<script type=\"text/javascript\">" . PHP_EOL . "//<![CDATA[" . PHP_EOL . $tmps . PHP_EOL . "//]]> " . PHP_EOL . "</script>" . PHP_EOL;
    }
    $htmlCode.= $tmp;
    $htmlCode.= "</div>";
}
?>

  <style type="text/css">
  #mainBut div {
  float:left;
  }
  #mainF div {
  float:bottom;
  }
  #mainCom {
  margin:40px 0px;
  }
  
  </style>
  
  <div id="mainF"> 
  <div id="mainBut"> <?php echo $htmlCodeBut; ?> </div>
  <div id="mainCom"> <?php echo $htmlCode; ?> </div>
  </div>
