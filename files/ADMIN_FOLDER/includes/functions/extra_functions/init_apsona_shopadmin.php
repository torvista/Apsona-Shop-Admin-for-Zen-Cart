<?php
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
} 

if (function_exists('zen_register_admin_page')) {
    if (!zen_page_key_exists ('apsona_shopadmin')) {
        // Add the link to Apsona ShopAdmin
        zen_register_admin_page('apsona_shopadmin', 'BOX_TOOLS_APSONA_SHOPADMIN', 'FILENAME_APSONA_SHOPADMIN', '', 'tools', 'Y', 15);
    }
}
// Don't have closing bracket below - otherwise Zen Cart sends headers, causing trouble.

