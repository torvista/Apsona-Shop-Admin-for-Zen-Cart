<?php

/**
 *
 * --------------------------------------------------------------------------------------------------- 
 * Copyright (c) 2010 apsona.com
 * All rights reserved
 * You may not modify, redistribute, reverse-engineer, decompile or disassemble this software product.
 * Please see http://apsona.com/pages/ec/tos.html for full copyright details.
 * --------------------------------------------------------------------------------------------------- 
 *
*/

require ('includes/application_top.php'); // Use application_top so that unauthorized users can't hit this page

//torvista for ZC155
if (!defined('DIR_WS_HTTPS_ADMIN')) define('DIR_WS_HTTPS_ADMIN', DIR_WS_ADMIN);//https://www.zen-cart.com/showthread.php?219753-DIR_WS_HTTPS_ADMIN-Constant-appears-to-be-removed-but-is-still-used-referenced&p=1307089#post1307089

define('APSONA_SA_VERSION', '1.07.1');
define('APSONA_MIN_PHP_VERSION', '5.1');
define('APSONA_SVC_URI', 'apsona_svc.php');

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php if (version_compare (PHP_VERSION, APSONA_MIN_PHP_VERSION) < 0) { ?>
  <body>
    <h3>Unable to start ShopAdmin</h3>
    Sorry, Apsona ShopAdmin requires PHP version <?php echo APSONA_MIN_PHP_VERSION ?> or better.<br/>
    Your PHP version is <?php echo PHP_VERSION ?>
  </body>
</html>
<?php
    exit();
}
?>
<?php
require ('apsona_config.php');
require ('apsona_functions.php');
error_reporting (E_ALL ^ E_NOTICE);
//error_reporting(-1);//steve enable all error reporting
?>
<head>
  <title>Apsona ShopAdmin <?php echo APSONA_SA_VERSION ?> for Zen Cart</title>
  <meta http-equiv="X-UA-Compatible" content="chrome=1" />
  <meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
  <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script><!-- steve changed to https -->
  <script type="text/javascript">
  $(function () {
      if (window.apsona_error) {
          $("#apsona-load-failed-error").html (window.apsona_error);
          $("#apsona-load-failed").show();
      }
  });
  </script>
  <script type="text/javascript" src="<?php echo $APSONA_BASE_URL; ?>/ec/zencart/apsona.min.js"></script>
  <script type="text/javascript">
    if (typeof(Apsona) == "undefined") Apsona = { ZenCart: {}};// Should never happen
  </script>
  <script type="text/javascript" src="apsona_dashboard.js"></script>
  <script type="text/javascript">
  Apsona.ZenCart.AddonVersion = '<?php echo APSONA_SA_VERSION ?>';
  Apsona.ZenCart.CartDbInfo = <?php
      $languageId = isset($_GET["languageId"]) ? $_GET["languageId"] :  $_SESSION['languages_id'];
      $apsonaDbConn = new ApsonaDbConnection (DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE);
      initApsona ($apsonaDbConn);
      echo '{"tblPrefix": "' . DB_PREFIX . '"';    
      echo ', "languageId": "' . $languageId . '"';
      echo ', "picklists": {';
      echo '  "products_type": '      . encodeJSON (getPicklistForField ("products_type",      $apsonaDbConn, $languageId));
      echo ', "product_categories": ' . encodeJSON (getPicklistForField ("product_categories", $apsonaDbConn, $languageId));
      echo ', "orders_status": '      . encodeJSON (getPicklistForField ("orders_status",      $apsonaDbConn, $languageId));
      echo ', "zone": '               . encodeJSON (getPicklistForField ("entry_zone_id",      $apsonaDbConn, $languageId));
      echo ', "country": '            . encodeJSON (getPicklistForField ("entry_country_id",   $apsonaDbConn, $languageId));
      echo ', "group_pricing": '      . encodeJSON (getPicklistForField ("group_pricing",      $apsonaDbConn, $languageId));
      echo ', "tax_class": '          . encodeJSON (getPicklistForField ("tax_class",          $apsonaDbConn, $languageId));
      echo ', "language": '           . encodeJSON (getPicklistForField ("language",           $apsonaDbConn, $languageId));
      echo '}';
      echo '}';
      $apsonaDbConn->close();
    ?>;
  </script>
</head>
<body>
  <!--[if IE]>
    <style>.chromeFrameOverlayContent { top: 400px;}</style>
    <h2 align="center"></h2>
  <![endif]-->
  <div id="apsona-load-failed" style="display: none;">
    <h2>There was a problem</h2>
    <div style="white-space: normal; font-family: courier new,fixed,monospace; font-size: 14px;padding: 0 30px 20px 30px;">
      <div id="apsona-load-failed-error" style="margin-bottom: 15px;"></div>
      ShopAdmin add-on for Zen Cart, version <?php echo APSONA_SA_VERSION ?>
    </div>
    <p>Please <a href="javascript:window.location.reload()">try again</a>. If that fails, please send a screen shot of this screen to <a href="mailto:support@apsona.com">support@apsona.com</a>.</p>
  </div>
  <img style="display: block;margin: 40px auto;text-align: center;" src="<?php echo $APSONA_BASE_URL ?>/img/loading.gif" alt="page loading" title="page loading" />
<!--[if IE]>
  <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/chrome-frame/1/CFInstall.min.js"></script>
  <script>
       if (jQuery.browser.msie && jQuery.browser.version < "7") {
           alert ("Apsona ShopAdmin requires Internet Explorer Version 7 or higher.");
       }
       window.onload = function () {
          Apsona.hasGCF = CFInstall.isAvailable();
          Apsona.start = function () {
              if (Apsona.hasGCF) {
                  if (Apsona.ZenCart.init) Apsona.ZenCart.init ("<?php echo $APSONA_BASE_URL ?>", "<?php echo $APSONA_APP_ID ?>", "<?php echo APSONA_SVC_URI ?>?uri_offset=", "<?php echo DB_PREFIX ?>");
              } else {
                  $("h2").html ("Apsona requires Google Chrome Frame to run under Internet Explorer.");
              }
          };
          setTimeout(function () {
              Apsona.start();
          }, 250);
          CFInstall.check({
              mode: "overlay",
              node: document.getElementById("gcf"),
              onmissing: function () { Apsona.hasGCF = false; },
              oninstall: function () { alert ("Please restart your browser."); }
          });
      };
  </script>
<![endif]-->
<script type="text/javascript">
    $(function () {
        if (!jQuery.browser.msie && Apsona.ZenCart.init) {
            Apsona.ZenCart.init ("<?php echo $APSONA_BASE_URL ?>", "<?php echo $APSONA_APP_ID ?>", "<?php echo APSONA_SVC_URI ?>?uri_offset=", "<?php echo DB_PREFIX ?>");
        }
    });
</script>
</body>
</html>
<?php require ('includes/application_bottom.php'); ?>