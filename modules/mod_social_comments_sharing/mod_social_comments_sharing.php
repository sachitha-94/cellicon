<?php
/*
* @version 1.1 FREE
* @package social sharing
* @copyright 2012 OrdaSoft
* @author 2012 Andrey Kvasnekskiy (akbet@ordasoft.com )
* @description social sharing, sharing WEB pages in LinkedIn, FaceBook, Twitter and Google+ (G+)
*/
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
require_once __DIR__ . '/helper.php';

$list = modSocialCommentHelper::getLink($params);

require JModuleHelper::getLayoutPath('mod_social_comments_sharing',$params->get('layout', 'default'));