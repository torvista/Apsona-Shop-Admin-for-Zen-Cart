<?php
//2014 08 26 torvista: modified for mysqli
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

class ApsonaService {
    public function __construct ($baseURL) {
        $this->baseUrl = $baseURL;
        $this->useCurl = false;
    }


    private function _getViaCurl ($url, $postData) {
        $headers = $this->_getHeaders();
        $hdrs = array();
        foreach ($headers as $name => $value) {
            $hdrs[] = "$name: $value";
        }
        $process = curl_init ($url);
        curl_setopt ($process, CURLOPT_HTTPHEADER, $hdrs);
        curl_setopt ($process, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($process, CURLOPT_ENCODING, $headers["Accept-Encoding"]);
        // curl_setopt ($process, CURLOPT_HEADER, true);
        if ($postData) {
            curl_setopt ($process, CURLOPT_POST, 1);
            curl_setopt ($process, CURLOPT_POSTFIELDS, $postData);
        }
        $return = curl_exec ($process);
        if ($return === false) {
            return array ("error" => curl_error ($process));
        }
        $response = curl_getinfo ($process);
        curl_close($process);
        return array ("content" => $return, "headers" => get_headers ($response['url']));
    }
    

    private function _getHeaders() {
       foreach ($_SERVER as $name => $value) {
           if (substr($name, 0, 5) == 'HTTP_') {
               $hdr = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
               $hdrLower = strtolower ($hdr);
               if ($hdr != "host") $headers[$hdr] = $value;
           }
       }
       return $headers;
    }

    private function _copyServerHeaders ($headers) {
        $isGzip = false;
        foreach ($headers as $hdr) {
            if (is_string ($hdr) && preg_match ("/^[A-Za-z0-9_-]+:/", $hdr) > 0) {
                $hdrLower = strtolower ($hdr);
                if (substr ($hdrLower, 0, 22) == "content-encoding: gzip") {
                    $isGzip = true;
                } else if (substr ($hdrLower, 0, 17) != "transfer-encoding") {
                    header ($hdr);
                }
            }
        }
        return $isGzip;
    }
    
    private function _showError ($msg, $url) {
        if (substr ($url, strlen($url) - 3) == ".js") {
            echo "window.apsona_error = (window.apsona_error || '') + '<br/>' + " .  encodeJSON($msg);
        } else {
            echo $msg;
        }
    }
    
    public function perform ($svcUrl) {
        $qs = $_SERVER['QUERY_STRING'];
        $qs = $qs == null ? "" :  (substr ($qs, 0, 1) == "?" ? $qs : ("?" . $qs));
        $url = $this->baseUrl . $svcUrl . $qs;
        switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (!$this->useCurl) {
                $opts = array('http' => array('method' => 'GET','header'  => $this->_getHeaders(), 'timeout' => 3.0));
                $stream = @fopen($url, 'rb', false, stream_context_create ($opts));
                $str = @stream_get_contents ($stream);
                if ($str === null  || trim($str) === "") {
                    $this->_showError ("$svcUrl: get failed: $php_errormsg", $svcUrl);
                    break;
                }
                $isGzip = $this->_copyServerHeaders ($http_response_header);
                if ($isGzip) {
                    $str = apsona_compatible_gzinflate ($str);
                    if ($str === null  || trim($str) === "") {
                        $this->_showError ("$svcUrl: gzinflate failed: $php_errormsg", $svcUrl);
                    }
                }
                fclose ($stream);
                echo $str;
            } else {
                // Try CURL
                $curlRes = $this->_getViaCurl ($url, null);
                if ($curlRes['error']) {
                    $this->_showError ("Unable to get ShopAdmin resources: " . $curlRes['error']);
                    return;
                }
                $this->_copyServerHeaders ($curlRes["headers"]);
                echo $curlRes['content'];
            }
            flush();
            break;

        case 'POST':
            $data = file_get_contents("php://input");
            $params = array ('http' => array( 'method' => 'POST', 'content' => $data));
            $ctx = stream_context_create ($params);
            $fp = @fopen ($url, 'rb', false, $ctx);
            if ($fp) {
                fpassthru ($fp);
                fclose ($fp);
            } else { // Try CURL
                $curlRes = $this->_getViaCurl ($url, $data);
                if ($curlRes['error']) {
                    $this->_showError ("Unable to get ShopAdmin resources: " . $curlRes['error']);
                    return;
                }
                $this->_copyServerHeaders ($curlRes["headers"]);
                echo $curlRes['content'];
                flush();
                break;
            }
            flush();
            break;
        }
    }

    private $baseUrl;
}

class ApsonaDbResultSet {
    public function __construct ($qResult) {
        $this->qResult = $qResult;
        $index = 0;
    }

    public function close () {
        //mysql_free_result ($this->qResult);//original
		mysqli_free_result ($this->qResult);//torvista added i
    }

    public function next() {
        //return mysql_fetch_row ($this->qResult);//original
		return mysqli_fetch_row ($this->qResult);//torvista added i
    }

    public function fieldCount() {
        //return mysql_num_fields ($this->qResult);//original
		return mysqli_num_fields ($this->qResult);//torvista added i
    }

    public function fieldName ($i) {
        //return mysql_field_name ($this->qResult, $i);//original
		return mysqli_fetch_field_direct ($this->qResult, $i)->name;//torvista added i
    }

    public function seekTo ($i) {
        //return mysql_data_seek ($this->qResult, $i);//orginal
		return mysqli_data_seek ($this->qResult, $i);//torvista added i
    }
    
    private $qResult;
    private $index;
}

class ApsonaDbConnection {

    public function __construct ($hostName, $userName, $password, $databaseName) {
        //$this->connection = mysql_connect ($hostName, $userName, $password);//original
 		$this->connection = mysqli_connect ($hostName, $userName, $password);//torvista added i
		//mysql_select_db ($databaseName, $this->connection);//original
		mysqli_select_db ($this->connection, $databaseName);//torvista added i, swapped parameters around
        //mysql_query('SET NAMES "utf8"', $this->connection);//original
		mysqli_query($this->connection, 'SET NAMES "utf8"');//torvista added i, swapped parameters around
        //mysql_query('SET group_concat_max_len = 100000', $this->connection);//original
		mysqli_query($this->connection, 'SET group_concat_max_len = 100000');//torvista added i, swapped parameters around
    }


    public function getTableNames() {
        //$result = mysql_query ("show tables", $this->connection);//original
		$result = mysqli_query ($this->connection, "show tables");//torvista added i, swapped parameters around
        if ($result) {
            $output = array();
            //while ($row = mysql_fetch_array($result)) {//original
			while ($row = mysqli_fetch_array($result)) {//torvista added i
                $output[] = $row[0];
            }
            //mysql_free_result ($result);//original
			mysqli_free_result ($result);//torvista added i
        }
        return $output;            
    }

    
    public function getMetadata ($tableName) {
        //$cols = mysql_query ("show columns from $tableName", $this->connection);//original
		$cols = mysqli_query ($this->connection, "show columns from $tableName");//torvista added i, swapped parameters around
        if ($cols) {
            $output = array();
            $fields = array();
            //while ($col = mysql_fetch_assoc ($cols)) {//original
			while ($col = $cols->fetch_assoc()) {//torvista changed
                $colName = strtolower ($col["Field"]);
                $colType = strtolower ($col["Type"]);
                preg_match ("/([a-zA-Z0-9_]*)(\((.*?)\))?/", $colType, $matches);
                $colType = $this->_apsonaDataType ($matches[1]);
                //$size = $matches[3] != null ? $matches[3] : 0;//original
				$size = isset($matches[3]) ? $matches[3] : 0;//torvista to fix PHP Notice:  Undefined offset: 3 
                $fields[] = array ("fieldId" => $colName, "fieldType" => $colType, "size" => $size, "disallowEmpty" => $col["Null"] == "NO");
                if ($col["Key"] == "PRI") {
                    //if ($output["keyColNames"] == null) {//original
					if (!isset($output["keyColNames"])) {//torvista to fix PHP Notice:  Undefined index: keyColNames
                        $output["keyColNames"] = array();
                    }
                    $output["keyColNames"][] = $colName;
                }
            }
            $output["fields"] = $fields;
            //mysql_free_result ($cols);//original
			mysqli_free_result ($cols);//torvista added i
        }
        return $output;
    }


    public function getPicklist ($query) {
        $result = $this->execute ($query);
        $returnVal = array();
        if (!$result) return $returnVal;
        //if ($result['error']) {//original
		if (isset($result['error']) && $result['error']) {//torvista to fix PHP Notice:  Undefined index: error	
            $returnVal = $result;
        } else {
            $iter = $result['recordIterator'];
            $fieldNames = $result['fieldNames'];
            $nFields = count($fieldNames);
            for ($ndx = 0; ($row = $iter->next()) != null; $ndx++) {
                $entry = array ("value" => addslashes($row[0]), "text" => addslashes ($row[1]));
                for ($i = 2; $i < $nFields; $i++) {
                    $entry [$fieldNames[$i]] = addSlashes ($row[$i]);
                }
                $returnVal[] = $entry;
            }
            $iter->close();
        }
        return $returnVal;
    }

    
    public function execute ($sqlString) {
        //$qResult = mysql_query ($sqlString, $this->connection);//original
		$qResult = mysqli_query ( $this->connection, $sqlString);//torvista added i, swapped parameters around
        $queryPrefix = strtolower (substr ($sqlString, 0, 6));
        switch ($queryPrefix) {
        case "select":
            if ($qResult) {
                $fieldNames = array();
                $fieldTypes = array();
                //$nFields = mysql_num_fields ($qResult);//original
				$nFields = mysqli_num_fields ($qResult);//torvista added i
                for ($i = 0; $i < $nFields; $i++) {
                    //$fieldNames[] = mysql_field_name ($qResult, $i);//original
                    $fieldNames[] = mysqli_fetch_field_direct ($qResult, $i)->name;//torvista added
					//$fieldTypes[] = mysql_field_type ($qResult, $i);//original
					$fieldTypes[] = mysqli_fetch_field_direct ($qResult, $i)->type;//torvista added
                }
                //return array ("fieldNames" => $fieldNames, "fieldTypes" => $fieldTypes, "recordIterator" => new ApsonaDbResultSet ($qResult), "recordCount" => mysql_num_rows($qResult));//original
				return array ("fieldNames" => $fieldNames, "fieldTypes" => $fieldTypes, "recordIterator" => new ApsonaDbResultSet ($qResult), "recordCount" => mysqli_num_rows($qResult));//torvista added i
            }
            //$err = mysql_error ($this->connection);//original
			$err = mysqli_error ($this->connection);//torvista added i
            if ($err) {
                // errorLog ($err . "\nSQL was\n" . $sqlString);
                return array ("error" => $err, "sql" => $sqlString);
            }
            return null;

        case "insert":
            //return $qResult ? array ("insert_id" => mysql_insert_id ($this->connection)) : array ("error" => mysql_error ($this->connection));//original
			return $qResult ? array ("insert_id" => mysqli_insert_id ($this->connection)) : array ("error" => mysqli_error ($this->connection));//torvista added and i
        default:
            //return $qResult ? null : array ("error" => mysql_error ($this->connection), "sql" => $sqlString);//original
			return $qResult ? null : array ("error" => mysqli_error ($this->connection), "sql" => $sqlString);//torvista added i
        }
    }


    public function close () {
        if ($this->connection) {
            //mysql_close ($this->connection);//original
			mysqli_close ($this->connection);//torvista added i
        }
    }

    
    function sqlify ($valueString, $type) {
        switch ($type) {
        case "string":
            return "'" . preg_replace ('/\n/', '\\n', addslashes ($valueString)) . "'";

        case "number":
        default:
            return $valueString;
        }
    }

//torvista edited
    private function _ensureMetadata ($tableName) {
        //if ($this->tableInfo == null) {//original
			if (ApsonaDbConnection::$tableInfo == null) {//torvista
            //$this->tableInfo = array();//original
			ApsonaDbConnection::$tableInfo = array();//torvista
        }
        //if ($this->tableInfo[$tableName] == null) {//original
			if (ApsonaDbConnection::$tableInfo[$tableName] == null) {//torvista
            //$this->tableInfo[$tableName] = $this->getMetadata ($tableName);//original
			ApsonaDbConnection::$tableInfo[$tableName] = $this->getMetadata ($tableName);//torvista
        }
        //return $this->tableInfo[$tableName];//original
		return ApsonaDbConnection::$tableInfo[$tableName];//torvista
    }

    /**
     * Insert or update data records into the given table.
     */
    public function storeRecords ($tableName, $fieldNamesArray, $recordsArray) {
        $nFields = count ($fieldNamesArray);
        if ($nFields <= 1) {
            return array (); // Do nothing - need at least 2 fields to insert or update
        }
        // Set up the SQL to check if the record already exists, and the positions of the columns
        $this->_ensureMetadata ($tableName);
        //$keyColNames = $this->tableInfo[$tableName]["keyColNames"];//original
		$keyColNames = ApsonaDbConnection::$tableInfo[$tableName]["keyColNames"];//torvista
        $keyColPos = array(); // Map: table key column name -> position in record
        $isKeyCol = array(); // Map: position (#) -> true if the column at that position is a table key column
        $selSql = "select " . implode (",", $keyColNames) . " from $tableName";
        $whereSql = "where ";
        $allKeyColsGiven = true;
        $nKeyCols = count($keyColNames);
        for ($i = 0; $i < $nKeyCols; $i++) {
            $whereSql .= ($i == 0 ? "" : " and ") . $keyColNames[$i] . " = ?$i?";
            $pos = array_search ($keyColNames[$i], $fieldNamesArray);
            if ($pos !== false) {
                $keyColPos[$keyColNames[$i]] = $pos;
                $isKeyCol[$pos] = true;
            } else {
                $allKeyColsGiven = false;
                break;
            }
        }
        $fieldTypes = array();
        for ($i = 0; $i < nFields; $i++) {
            $fieldType = $this->tableInfo[$tableName]["fieldTypes"][$fieldNamesArray[$i]];
            if (!$fieldType) {
                return array ("error" => "Cannot find field " . $fieldNamesArray[$i] . " in table $tableName");
            }
            $fieldTypes[] = $fieldType;
        }
        for ($i = 0, $n = count($recordsArray); $i < $n; $i++) {
            $record = $recordsArray[$i];
            if (count ($record) <= 1) {
                $results[] = array ("error" => "Empty record");
                continue; // Ignore empty records
            }
            if (count ($record) != $nFields) {
                $results[] = array ("error" => "Field count mismatch: Expecting $nFields fields, found " . count($record) . " fields.");
                continue;
            }
            $recordExists = false;
            $whereClauseForRec = "";
            if ($allKeyColsGiven) {
                $searchValues = array();
                $replacements = array();
                for ($j = 0; $j < $nKeyCols; $j++) {
                    $kcName = $keyColNames[$j];
                    $searchValues[] = "?$j?";
                    //$replacements[] = $this->sqlify ($record[$keyColPos[$kcName]], $this->tableInfo[$tableName]["fieldTypes"][$kcName]);//original
					$replacements[] = $this->sqlify ($record[$keyColPos[$kcName]], ApsonaDbConnection::$tableInfo[$tableName]["fieldTypes"][$kcName]);//torvista
                }
                $whereClauseForRec = str_replace ($searchValues, $replacements, $whereSql);
                $selSqlForRec = $selSql . " " . $whereClauseForRec;
                $result = $this->execute ($selSqlForRec);
                $recordExists = $result != null && $result['recordCount'] >= 1;
            }
                
            if ($recordExists) {
                $updates = "";
                for ($j = 0, $m = count($fieldNamesArray); $j < $m; $j++) {
                    $value =  $record [$j];
                    $fieldName = $fieldNamesArray[$j];
                    $fieldType = $fieldTypes[$j];
                    if (!isset ($fieldType)) $fieldType = "string";
                    if (!$isKeyCol[$j]) {
                        $updates .= ($updates != "" ? ", " : "") . $fieldNamesArray[$j] . " = " . $this->sqlify ($record[$j], $fieldType);
                    }
                }
                if (strlen($updates) > 0) {
                    $updSql = "update $tableName set $updates $whereClauseForRec";
                    $result = $this->execute ($updSql);
                    $result['op'] = "update";
                    $results[] = $result;
                } else {
                    $results[] = array(); // Did nothing, because no fields to update
                }
            } else {
                $fields = "";
                $values = "";
                for ($j = 0, $m = count($fieldNamesArray); $j < $m; $j++) {
                    $value =  $record [$j];
                    $fieldName = $fieldNamesArray[$j];
                    $fieldType = $fieldTypes[$j];
                    if (!isset ($fieldType)) $fieldType = "string";
                    $sep = $j > 0 ? ", " : "";
                    $fields .= $sep . $fieldNamesArray[$j];
                    $values .= $sep . $this->sqlify ($record[$j], $fieldType);
                }
                $result = $this->execute ("insert into $tableName (" . $fields . ") values (" . $values . ")");
                $result['op'] = "insert";
                $results[] = $result;
            }
        }
        return $results;
    }


    private function _getWhereClauseForUpdateDelete ($tableName, $whereStr) {
        $keys = $this->_getKeyValues ($tableName, $whereStr);
        if ($keys && $keys['error']) return $keys;
        $whereSql = null;
        if ($keys && count($keys) > 0) {
            $tableInfo = $this->_ensureMetadata ($tableName);
            $whereSql = "";
            $keyColNames = $tableInfo["keyColNames"];
            $nKeys = count ($keyColNames);
            if ($nKeys > 1) {
                for ($i = 0, $m = count($keys); $i < $m; $i++) {
                    if ($i > 0) {
                        $whereSql .= " or ";
                    }
                    for ($j = 0; $j < $nKeys; $j++) {
                        $kcName = $keyColNames[$j];
                        $type = $tableInfo["fieldTypes"][$kcName];
                        $value = $keys[$i][$j];
                        $whereSql .= ($j > 0 ? " and " : "") . $kcName . " = " . $this->sqlify ($value, $type);
                    }
                }
            } else {
                $whereSql .= $keyColNames[0] . " in (" . implode(",", $keys) . ")";
            }
        }
        return array ("keys" => $keys, "where" => $whereSql);
    }
    
    public function updateRecords ($tableName, $where, $valueMap) {
        $whereCond = $this->_getWhereClauseForUpdateDelete ($tableName, $where);
        if (!$whereCond || $whereCond['error']) return $whereCond;
        $wClause = $whereCond['where'];
        if ($wClause) {
            $tableInfo = $this->_ensureMetadata ($tableName);
            $str = "";
            foreach ($valueMap as $key => $value) {
                $type = $tableInfo["fieldTypes"][$fieldName];
                if (!$type) $type = "string";
                $str .= ($str == "" ? "" : ", ") . $key . " = " . $this->sqlify ($value, $type);
            }
            $sql = "update $tableName set $str where " . $wClause;
            $sqlResult = $this->execute ($sql);
            $result = $sqlResult && $sqlResult['error'] ? $sqlResult : array ("ids" => $whereCond['keys']);
        }
        return $result;
    }

    public function deleteRecords ($tableName, $where) {
        $whereCond = $this->_getWhereClauseForUpdateDelete ($tableName, $where);
        if (!$whereCond || $whereCond['error']) return $whereCond;
        $wClause = $whereCond['where'];
        if ($wClause) {
            $sql = "delete from $tableName where $wClause";
            $sqlResult = $this->execute ($sql);
            $result = $sqlResult && $sqlResult['error'] ? $sqlResult : array ("ids" => $whereCond['keys']);
        }
        return $result;
    }


    private function _getKeyValues ($tableName, $where) {
        // Return an array containing the primary keys for the records matching the given where clause. If there is only one pk column, the returned array
        // contains the key values as scalars; otherwise, it contains arrays, each of which are the key values.
        $tableInfo = $this->_ensureMetadata ($tableName);
        $keyFields = $tableInfo['keyColNames'];
        $sql = "select " . implode (",", $keyFields) . " from $tableName" . ($where ? " where $where" : "");
        $result = $this->execute ($sql);
        $returnVal = array();
        $isSinglePk = count($keyFields) == 1;
        if ($result['error']) {
            $returnVal = $result;
        } else {
            $iter = $result['recordIterator'];
            for ($ndx = 0; ($row = $iter->next()) != null; $ndx++) {
                $returnVal[] = $isSinglePk ? $row[0] : $row;
            }
            $iter->close();
        }
        return $returnVal;
    }
    
//     public function storeCSV ($tableName, $csvString) {
//         $rowNo = 0;
//         $records = array();
//         $maxMem = 25 * 1024 * 1024; // 25 MB
//         $fp = fopen ("php://temp/maxmemory:$maxMem", 'r+');
//         fputs ($fp, $csvString);
//         rewind ($fp);
//         while (($data  = fgetcsv ($fp)) !== FALSE) {
//             $data = array_map ("rtrim", array_map ("trim", $data));
//             if ($rowNo == 0) {
//                 $headers = $data;
//             } else {
//                 $records[] = $data;
//             }
//             $rowNo++;
//         }
//         fclose($fp);
//         return $this->storeRecords ($tableName, $headers, $records);
//     }

    private function _apsonaDataType ($sqlType) {
        switch ($sqlType) {
        case "varchar":
        case "char":
        case "tinytext":
            return "string";

        case "blob":
            return "text";
            
        case "int":
        case "smallint":
        case "mediumint":
            return "integer";

        case "float":
        case "decimal":
            return "number";

        case "tinyint":
            return "boolean";

        case "date":
            return "date";

        case "datetime":
        case "timestamp":
            return "datetime";

        default:
            return $sqlType;
        }
    }

    private $connection;
    private static $tableInfo;
};



function dieWithError ($erMsg) {
    echo '{"error": "' . addslashes($erMsg) . '"}';
    exit();
}

function apsona_compatible_gzinflate($gzData) {
    // From http://www.mydigitallife.info/2010/01/17/workaround-to-fix-php-warning-gzuncompress-or-gzinflate-data-error-in-wordpress-http-php/
    if ( substr($gzData, 0, 3) == "\x1f\x8b\x08" ) {
	$i = 10;
	$flg = ord( substr($gzData, 3, 1) );
	if ( $flg > 0 ) {
            if ( $flg & 4 ) {
                list($xlen) = unpack('v', substr($gzData, $i, 2) );
                $i = $i + 2 + $xlen;
            }
            if ( $flg & 8 )
                $i = strpos($gzData, "\0", $i) + 1;
            if ( $flg & 16 )
                $i = strpos($gzData, "\0", $i) + 1;
            if ( $flg & 2 )
                $i = $i + 2;
	}
	return @gzinflate( substr($gzData, $i, -8) );
    } else {
	return false;
    }
}


function getDataJSON ($query, $first, $count, $isRef, $db) {
    $timeNow = microtime(true);
    $db->execute ("set sql_big_selects=1");
    $result = $db->execute ($query);
    if (!$result) {
        return;
    }
    if ($result['error']) {
        echo encodeJSON ($result);
        return;
    }
    $nRecs = $result['recordCount'];
    if ($nRecs == null) $nRecs = 0;
    $iter = $result['recordIterator'];
    echo '{ "totalRecords": ' . $nRecs . ',"records": [';
    if ($first <= 0 || $iter->seekTo ($first) ) {
        for ($ndx = 0; ($row = $iter->next()) != null && $ndx < $count; $ndx++) {
            if ($isRef) {
                // The data includes references, return them as pairs
                $newRec = array();
                for ($j = 0, $m = count($row); $j < $m; $j++) {
                    if ($isRef[$j]) {
                        $newRec[] = array($row[$j], $row[$j+1]);
                        $j++;
                    } else {
                        $newRec[] = $row[$j];
                    }
                }
                $row = $newRec;
            }
            if ($ndx > 0) echo ",";
            echo encodeJSON ($row);
            echo "\n";
        }
    }
    echo ']';
    echo ', "timeStats": { "totalMS": ' . (microtime(true) - $timeNow)*1000 . '}';
    echo '}';
    $iter->close();
}




function getPicklistForField ($fieldName, $db, $languageId) {
    $picklistSQL = array (
        "products_type"      => "select type_id, type_name from " . TABLE_PRODUCT_TYPES . " order by 2",
        "product_categories" => "select " . TABLE_CATEGORIES . ".categories_id, " .  TABLE_CATEGORIES_DESCRIPTION . ".categories_name, " .  TABLE_CATEGORIES . ".parent_id " .
            " from " .  TABLE_CATEGORIES . "," . TABLE_CATEGORIES_DESCRIPTION . " where " .  TABLE_CATEGORIES . ".categories_id = " .  TABLE_CATEGORIES_DESCRIPTION . ".categories_id " .
            " and  language_id = " . $languageId . " order by 2",
        "orders_status"      => "select orders_status_id, orders_status_name from " . TABLE_ORDERS_STATUS . " where language_id = " . $languageId . " order by 2",
        "entry_zone_id"      => "select zone_id, zone_name from " . TABLE_ZONES . " order by 2",
        "entry_country_id"   => "select countries_id, countries_name from " . TABLE_COUNTRIES . " order by 2",
        "group_pricing"      => "select group_id, concat(group_name, ' (', group_percentage, '%)' ) from " . TABLE_GROUP_PRICING . " order by 2",
        "tax_class"          => "select tax_class_id, tax_class_title from " . TABLE_TAX_CLASS . " order by 2",
        "language"           => "select languages_id, name, code from " . TABLE_LANGUAGES . " order by sort_order"
    );

    if ($fieldName == "customers_gender") {
        return array (array ("value" => "m", "text" => "Male"), array ("value" => "f", "text" => "Female"));
    }
    $sqlStr = $picklistSQL[$fieldName];
    return !empty ($sqlStr) ? $db->getPicklist ($sqlStr) : null;
}

    

function getDataCSV ($tableName, $query, $fields, $db) {
    $db->execute ("set sql_big_selects=1");
    $result = $db->execute ($query);
    if (!$result) {
        return;
    }
    if ($result['error']) {
        echo $result['error'];
        return;
    }
    $nFields = count($fields);
    for ($i = 0; $i < $nFields; $i++) {
        if ($i > 0) echo ",";
        echo '"' . str_replace ('"', '""', $fields[$i]['label']) . '"';
    }
    echo "\n";
    $picklistMap = array();
    for ($i = 0; $i < $nFields; $i++) {
        $plValues = $fields[$i]['choicesList'];// getPicklistForField ($tableName, $fields[$i], $db);
        if ($plValues) {
            $kvMap = array();
            for ($j = 0; $j < count($plValues); $j++) {
                $plPair = $plValues[$j];
                $kvMap[$plPair['value']] = $plPair['text'];
            }
            $picklistMap[$i] = $kvMap;
        }
    }
    $iter = $result['recordIterator'];
    while (($row = $iter->next()) != null) {
        for ($i = 0; $i < $nFields; $i++) {
            if ($i > 0) echo ",";
            $value = $row[$i];
            $plMap = $picklistMap[$i];
            if ($plMap) {
                $valueParts = explode ("##", $value);
                for ($j = count($valueParts)-1; $j >= 0; $j--) {
                    $valueParts[$j] = $plMap[$valueParts[$j]];
                }
                $value = implode ("\n", $valueParts);
            }
            echo '"' . str_replace ('"', '""', $value) . '"';
        }
        echo "\n";
    }
    $iter->close();
}

// function getMetadata ($tbl, $db) {
//     $metadata = $db->getMetadata ($tbl);
//     if (!$metadata) return;
//     echo '{ "keyFieldId": "' . $metadata["keyFieldId"] . "\"\n,\"fields\": [";
//     $flds = $metadata["fields"];
//     for ($i = 0; $i < count ($flds); $i++) {
//         echo "\n";
//         if ($i > 0) echo ",";
//         echo encodeJSON ($flds[$i]);
//     }
//     echo "\n]}";
// }

function parseParameters () {
    $parameterStr = $_GET['parameters'];
    $parameters = array();
    if (!$parameterStr) {
        $parameterStr = $_POST['parameters'];
    }
    if ($parameterStr) {
        if (get_magic_quotes_gpc()) {
            $parameterStr = stripslashes ($parameterStr);
        }
        //$pStr = preg_replace("#(\\\x[0-9A-Fa-f]{2})#e", "chr(hexdec('$1'))", $parameterStr);  // Decode Original
		$pStr = preg_replace_callback(//torvista replacement for /e modifier
			"#(\\\x[0-9A-Fa-f]{2})#",
			function ($m) {
				return chr(hexdec('$m[1]'));
				},
 			$parameterStr
			);
        $parameterStr = $pStr != null ? $pStr : $parameterStr;
        $parameters = json_decode ($parameterStr, true);
    }
    return $parameters;
}

function initApsona ($db) {
    $tbl = $db->getMetadata ("apsona_report");
    if (!$tbl || !$tbl['fields']) {
        $sql = array (
            "create table apsona_report ( report_id int(11) not null auto_increment, name varchar(200) not null, description varchar(4000), entity_name varchar(64) not null, report_descriptor text, layout_descriptor text, last_run_time_msec integer, created_date timestamp, modified_date timestamp, constraint report_pk primary key (report_id))",
            "create table apsona_filter ( filter_id int(11) not null auto_increment, name varchar (200) not null, description varchar (4000), entity_name varchar(64) not null, filter_condition text, created_date timestamp, modified_date timestamp, constraint filter__pk primary key (filter_id))"
            );
        for ($i = 0; $i < count ($sql); $i++) {
            $db->execute ($sql[$i]);
        }
        $db->execute (file_get_contents (DIR_FS_ADMIN . "apsona_init_reports.sql"));
        $db->execute (file_get_contents (DIR_FS_ADMIN . "apsona_init_filters.sql"));
    }
}


function storeDashboard () {
    $parameterStr = $_GET['parameters'];
    $parameters = array();
    if (!$parameterStr) {
        $parameterStr = $_POST['parameters'];
    }
    $dashboardFilePath = DIR_FS_ADMIN . "apsona_dashboard.js";
    file_put_contents ($dashboardFilePath, "Apsona.dashboard = {" . $parameterStr . "};");
}


// function errorLog ($str) {
//     error_log (strftime('%Y-%m-%d %H:%M:%S') . " [Apsona ShopAdmin error] " . $str . "\n");
// }


function debugLog ($str) {//torvista to enable output of some debug info:  in apsona_svc.php, look for "//$dbg = print_r" and uncomment the line
    //$fp = fopen (DIR_FS_ADMIN . "apsona_debug.txt", 'a+');//original in admin root
    $fp = fopen (DIR_FS_LOGS . "/DEBUG_Apsona.log", 'a+');//torvista changed to standard log folder
    fputs ($fp, $_SERVER['REQUEST_URI'] . ": " . $_SERVER['QUERY_STRING'] . ": " .$str . "\n");
    fclose ($fp);
}

function encodeJSON ($a=false) {
    if (is_null($a)) return 'null';
    if ($a === false) return 'false';
    if ($a === true) return 'true';
    if (is_scalar($a)) {
        if (is_float($a)) {
            // Always use "." for floats.
            return floatval(str_replace(",", ".", strval($a)));
        }

        if (is_string($a)) {
            static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
            return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
        }
        return $a;
    }
    $isList = true;
    for ($i = 0, reset($a); $i < count($a); $i++, next($a)) {
        if (key($a) !== $i) {
            $isList = false;
            break;
        }
    }
    $result = array();
    if ($isList) {
        foreach ($a as $v) $result[] = encodeJSON($v);
        return '[' . join(',', $result) . ']';
    }
    foreach ($a as $k => $v) $result[] = encodeJSON($k) . ':' . encodeJSON($v);
    return '{' . join(',', $result) . '}';
}

if (!function_exists ('json_decode')) {

    require_once ('apsona_JSON.php');
    
    function json_decode ($v) {
        $jsonSvc = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
        return $jsonSvc->decode ($v);
    }
}
?>
