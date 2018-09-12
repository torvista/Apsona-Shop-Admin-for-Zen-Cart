# Apsona-Shop-Admin-for-Zen-Cart

Apsona Shop Admin for Zen Cart - Updated fileset for Zen Cart 1.55f (mysqli, php7)

Apsona does not support this code anymore but maintains their servers operational to allow existing code to work: each time you use Apsona Shop Admin, their "current" code (and any customisations done by Apsona for you) is downloaded to your browser.

Pre-Installation
---------------
Sign up at 
http://www.apsona.com/pages/ec/sa.html

to get your user id and the original installation files for your own reference.

Installation
------------
The fileset and update from Apsona needs to be overwritten with this updated code: the original Apsona code uses mysql instead of mysqli, uses definitions deprecated in php7 and relies on constants moved/removed in Zen Cart 155a. 

1) Using this fileset, copy the contents of ADMIN_FOLDER into your admin folder.

2) Open apsona_config.php and paste your id number in the obvious place.

Refresh your Admin and "Apsona Shop Admin" should be listed in the tools menu.

Use
---
Clicking on the menu item should start the Shop Admin page (showing a header with your user details), then download the Shop Admin code to create a dashboard in the rest of the page.
If not, and only the top section of the page is displayed with no account info...your id is probably wrong.
If the account info is shown and the footer, but nothing in the middle and it is ZC155, check the admin/includes/configure.php does not still have apsona constants in there from ZC154 as detailed below.

Otherwise, read the support thread: http://www.zen-cart.com/showthread.php?153990-Apsona-ShopAdmin-a-free-tool-for-import-mass-update-reporting
Do NOT send support emails to Apsona about this service, they will not respond. It was a test bed for what they are doing now and they know it works.

FAQ
---
1) In the case of Zen Cart 1.54 ONLY, admin/includes/configure.php used a function zen_parse_url to auto-determine the current admin path for the constants DIR_WS_ADMIN and DIR_WS_HTTPS_ADMIN.
This function cannot be accessed by the Apsona code so the Apsona Shop Admin page will not load.
There is a browser console error associated with this problem:
<br />
<b>Fatal error</b>:  Call to undefined function zen_parse_url() in <b>YourSiteAdminAddress\includes\configure.php</b> on line
 <b>29</b><br /> 
 
The only solution to this is to replace the two dynamically-created constants with hard-coded paths in ADMIN/includes/configure.php

a) Original
define('DIR_WS_ADMIN', preg_replace('#^' . str_replace('-', '\-', zen_parse_url(HTTP_SERVER, '/path')) . '#', '', dirname($_SERVER['SCRIPT_NAME'])) . '/');

Replace with
//define('DIR_WS_ADMIN', preg_replace('#^' . str_replace('-', '\-', zen_parse_url(HTTP_SERVER, '/path')) . '#', '', dirname($_SERVER['SCRIPT_NAME'])) . '/');//ZC154 original, commented out for Apsona
define('DIR_WS_ADMIN', '/YourAdminFolder/');//Apsona for ZC154 ONLY. REMOVE FOR ZC155 ONWARDS 

b) Original
define('DIR_WS_HTTPS_ADMIN', preg_replace('#^' . str_replace('-', '\-', zen_parse_url(HTTP_SERVER, '/path')) . '#', '', dirname($_SERVER['SCRIPT_NAME'])) . '/');  

Replace with 
//define('DIR_WS_HTTPS_ADMIN', preg_replace('#^' . str_replace('-', '\-', zen_parse_url(HTTP_SERVER, '/path')) . '#', '', dirname($_SERVER['SCRIPT_NAME'])) . '/');//ZC154 original, commented out for Apsona  
define('DIR_WS_HTTPS_ADMIN', DIR_WS_ADMIN);//Apsona for ZC154 ONLY. REMOVE FOR ZC155 ONWARDS 

2) For Apsona to open in a new tab instead of changing browser window focus.

in ADMIN/includes/header_navigation.php

Change
              <ul class="dropdown-menu">
                <?php foreach ($pages as $page) { ?>
                  <li><a href="<?php echo zen_href_link($page['file'], $page['params']) ?>"><?php echo $page['name'] ?></a></li>
                <?php } ?>
              </ul>

To
              <ul class="dropdown-menu">
                <?php foreach ($pages as $page) { ?>
					<?php if ($page['file']=='apsona_index') { //steve hack to open Apsona in a new browser window ?>
						<li><a href="<?php echo zen_href_link($page['file'], $page['params']) ?>" target="_blank"><?php echo $page['name']; ?></a></li>
						<?php } else { ?>
                  <li><a href="<?php echo zen_href_link($page['file'], $page['params']); ?>"><?php echo $page['name'] ?></a></li>
                <?php }} ?>
              </ul>

==================================================



Changelog
---------
2016 10 09 torvista: changed logfile location to standard ZC. Moved filename constant out of language define.
2016 torvista: added code to deal with the removal of constants in ZC155 and use php7 constructs. 
2015 torvista: changed code to use mysqli

2012 fileset 3 from Apsona: "shopAdminUpdateForZenCart1dot5"
only additions for registering the menu item in the Tools menu
2010 fileset 2 from Apsona: "apsona-zencart-addon-update-latest"
changes to 
apsona_functions.php
apsona_index.php
apsona_svc.php
2010 fileset 1 from Apsona: "apsona-shopadmin-addon"