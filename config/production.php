<?php 

// Define constants that are used only on a production environment
// See file boot.php for more info

/**
 * Performance tricks for production environments.
 * 
 * Disable callback getters/setters. If you allways use 
 * AkActiveRecord::get('attribute_name') and AkActiveRecord::set('attribute_name', $value)
 * instead of AkActiveRecord::getAttributeName and AkActiveRecord::setAttributeName
 * you might want to disable thath functionality by uncommenting the next two line.
 */
// ak_define('ACTIVE_RECORD_ENABLE_CALLBACK_SETTERS', false); 
// ak_define('ACTIVE_RECORD_ENABLE_CALLBACK_GETTERS', false); 

/**
 * These settings can also help on heavy load sites, but please TEST that these new defaults
 * don't break you working application.
 * 
 * DONT CHANGE ANYTHING IF YOU DON'T KNOW WHAT IT MEANS.
 */
// ak_define('ACTIVE_RECORD_VALIDATE_TABLE_NAMES', false);
// ak_define('ACTIVE_RECORD_SKIP_SETTING_ACTIVE_RECORD_DEFAULTS', true);
// ak_define('ACTIVE_RECORD_ENABLE_PERSISTENCE', true);
// ak_define('ACTIVE_RECORD_CACHE_DATABASE_SCHEMA', true);
// ak_define('ACTIVE_RECORD_CACHE_DATABASE_SCHEMA_LIFE', 86400);

?>
