<?php
		
/**
 * loader example file.
 *
 * @author Nenad Zivkovic <nenad@freetuts.org>
 * @link http://www.freetuts.org
 * @copyright 2014-present freetuts.org
 * @license http://www.freetuts.org/site/page?view=licensing
 */


/**
 * Script that is loading core classes and libraries, 
 * plus it contains auto load function for loading models
 */
//---------------------------------------------------------------------------------------------------------------------- 
 
/**
 * File path constants
 */
 
// DIRECTORY_SEPARATOR is a PHP pre-defined constant ( \ for windows, / for Unix )
defined('DS') ? null : define('DS', DIRECTORY_SEPARATOR); 
 
// Site root path for the file system
defined('SITE_ROOT') ? null : define('SITE_ROOT', $_SERVER["DOCUMENT_ROOT"].DS.'example'.DS);

// Path to "includes" folder
defined('INCLUDES') ? null : define('INCLUDES', SITE_ROOT.'includes'.DS);

// Path to "models" folder
defined('MODELS') ? null : define('MODELS', SITE_ROOT.'models'.DS);

//----------------------------------------------------------------------------------------------------------------------

/**
 * Config and Database loader
 */
require_once(INCLUDES.'config.php');
require_once(INCLUDES.'DB.php');
 
//----------------------------------------------------------------------------------------------------------------------

// PHP 5.3.0 class auto load. Loads models if they are not loaded.
spl_autoload_register(function ($class) {
    require_once MODELS . $class . '.php';   
});

//---------------------------------------------------------------------------------------------------------------------- 

?>
