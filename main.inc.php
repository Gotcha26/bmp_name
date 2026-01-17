<?php
/*
Plugin Name: Batch Manager - Picture Name selector
Version: 1.0
Description: Batch Manager, Add Prefilter with / with no picture's name and add action on name.
Plugin URI: http://piwigo.org/ext/extension_view.php?eid=1061
Author: Gotcha
Author URI: https://www.julien-moreau.fr
*/

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

global $prefixeTable, $template;

define('BMPN_DIR' , basename(dirname(__FILE__)));
define('BMPN_PATH' , PHPWG_PLUGINS_PATH . BMPN_DIR . '/');

add_event_handler('loading_lang', 'batch_manager_photo_noname_loading_lang');	  
function batch_manager_photo_noname_loading_lang(){
  load_language('plugin.lang', BMPN_PATH);
}

// Plugin for admin
if (script_basename() == 'admin'){
  include_once(dirname(__FILE__).'/initadmin.php');
}

?>