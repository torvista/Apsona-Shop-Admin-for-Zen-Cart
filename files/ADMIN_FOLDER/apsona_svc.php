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
if (file_exists('includes/local/configure.php')) {
    include ('includes/local/configure.php');
} else {
include ('includes/configure.php');}
//torvista for ZC155
if (!defined('DIR_WS_ADMIN')) include ('includes/defined_paths.php');//from Zen Cart 1.55

require (DIR_FS_CATALOG . DIR_WS_INCLUDES . 'database_tables.php');
require ('apsona_config.php');
require ('apsona_functions.php');
error_reporting (E_ALL ^ E_NOTICE);

// Parse the parameters:
$uri = $_SERVER['REQUEST_URI'];

$pathInfo = $_GET['uri_offset'];
if (!$pathInfo) {
    $pos = strpos ($uri, "/apsona_svc.php/");
    if ($pos > 0) {
        $pathInfo = substr ($uri, $pos + strlen ("/apsona_svc.php"));
        $qPos = strpos ($pathInfo, "?");
        if ($qPos > 0) {
            $pathInfo = substr ($pathInfo, 0, $qPos);
        }
    }
}

if (!$pathInfo) {
    dieWithError ("Cannot find path info for URI '$uri'");
}

$op  = $pathInfo;

$parameters = parseParameters();

//$dbg = print_r ($parameters, true); debugLog ("parameters\n$dbg");//uncomment for debug info
// Dispatch to the right process
if (substr ($pathInfo, 0, 5) == "/svc/") {
    $service = new ApsonaService ($APSONA_BASE_URL);
    $service->perform (substr ($pathInfo, 4));
    exit();
}

if (substr ($op, 0, 10) == "/get/data/") {
    $query = $parameters['q'];
    if (!$query || $query == "") {
        dieWithError ("$pathInfo: No query given.");
    }
    $query = str_rot13 ($query);
    $fields = $parameters['fields'];
    $first = $parameters['firstRecord'];
    if ($first == null) $first = 0;
    $count = $parameters['recordCount'];
    if ($count == null) $count = 1000000;
    $tableName = substr ($op, 10);
    if (preg_match ("/\.js$/", $tableName)) {
        $tableName = substr ($tableName, 0, strlen($tableName)-3);
    } else if (preg_match ("/\.csv$/i", $tableName)) {
        $tableName = substr ($tableName, 0, strlen($tableName)-4);
    }
    $db = new ApsonaDbConnection (DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE);
    if (strtolower (substr ($op, strlen($op)-4)) == ".csv") {
        header ("Content-type: text/csv");
        header ("Content-disposition: attachment;filename=" . $tableName . ".csv");
        getDataCSV ($tableName, $query, $fields, $db);
    } else {
        header ("Content-type: text/javascript");
        getDataJSON ($query, $first, $count, $parameters['isRef'], $db);
    }
    $db->close();
    exit();
}

if (substr ($op, 0, 13) == "/get/metadata") {
    $tables = $parameters['tableNames'];
    header ("Content-type: text/javascript");
    $db = new ApsonaDbConnection (DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE);
    echo "{";
    for ($i = 0; $i < count($tables); $i++) {
        echo "\n";
        if ($i > 0) echo ",";
        echo '"' . $tables[$i] . '": ';
        echo encodeJSON ($db->getMetadata ($tables[$i]));
    }
    echo "\n}";
    $db->close();
    exit();
}

if (substr ($op, 0, 10) == "/put/data/") {
    header ("Content-type: text/javascript");
    $tableName = substr ($op, 10);
    if (preg_match ("/\.js$/", $tableName)) {
        $tableName = substr ($tableName, 0, strlen($tableName)-3);
    }
    $db = new ApsonaDbConnection (DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE);
    $result = $db->storeRecords ($tableName, $parameters['fieldIds'], $parameters['records']);
    $db->close();
    echo encodeJSON ($result);
    exit();
}

if ($op == "/put/dashboard.js") {
    storeDashboard ();
    header ("Content-type: text/javascript");
    echo "{}";
    exit();
}


if (substr ($op, 0, 13) == "/update/data/") {
    header ("Content-type: text/javascript");
    $tableName = substr ($op, 13);
    if (preg_match ("/\.js$/", $tableName)) {
        $tableName = substr ($tableName, 0, strlen($tableName)-3);
    }
    $where = $parameters['where'];
    $valueMap = $parameters['valueMap'];
    $result = null;
    if ($where && $valueMap) {
        $where = str_rot13 ($where);
        $db = new ApsonaDbConnection (DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE);
        $result = $db->updateRecords ($tableName, $where, $valueMap);
        $db->close();
    }
    echo $result ? encodeJSON ($result) : "{}";
    exit();
}

if (substr ($op, 0, 13) == "/delete/data/") {
    header ("Content-type: text/javascript");
    $tableName = substr ($op, 13);
    if (preg_match ("/\.js$/", $tableName)) {
        $tableName = substr ($tableName, 0, strlen($tableName)-3);
    }
    $where = $parameters['where'];
    $result = null;
    if ($where) {
        $where = str_rot13 ($where);
        $db = new ApsonaDbConnection (DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE);
        $result = $db->deleteRecords ($tableName, $where);
        $db->close();
    }
    echo $result ? encodeJSON ($result) : "{}";
    exit();
}

?>
