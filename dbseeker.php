#!/usr/bin/env php
<?php
// DB CONFIGS
$host = "localhost";
$database = "";
$user = "root";
$password = "";

if (! isset($_SERVER['argv'][1])) {
    echo "Please, give a pattern to search enclosed by double quotes, ex. : \"http:\"\n";
    exit (0);
} else {
    $regexpPattern = $_SERVER['argv'][1];
}
if (isset($_SERVER['argv'][2])) {
    $replace = $_SERVER['argv'][2];
    echo "Replace string given, all occurences of ".$regexpPattern." will be replaced by ".$replace."\n";
    echo "Do you want to perform replacement ? [y/N]";
    $confirmation  =  trim(fgets(STDIN));
    if ( $confirmation !== 'y' ) {
        echo "exiting...\n";
        exit (0);
    }
}

// CONFIGS
// $tabFieldsTypeText = array("varchar", "tinytext", "text", "mediumtext", "longtext");
$tabFieldsTypeText = null;
$excludedTables = array();

printf("return entries containing pattern '%s'", $regexpPattern);

echo "\nTable   +++     First Field value   +++     field with pattern";
echo "\n________________________________________________________________";

$link = mysql_connect($host, $user, $password)
or die("Impossible de se connecter : " . mysql_error());

mysql_select_db($database, $link)
or die('Could not select database.');

$strQuery = "Show tables ";
$resSet = mysql_query($strQuery);
$replacementsDone = false;
while ($table = mysql_fetch_array($resSet)) {
    $tableName = $table[0];
        if (! in_array($tableName, $excludedTables)) {
            $aFields = textFields($tableName, $tabFieldsTypeText);
            foreach ($aFields as $field) {
                $strQuery= "select * from ".$tableName. " where ".$field." regexp '".$regexpPattern."' ";
                $resSet2 = mysql_query($strQuery);
                if (is_resource($resSet2)) {
                    $found = false;
                    while ($entryFound = mysql_fetch_array($resSet2)) {
                        $found = true;
                        printf("\n%s   +++     %s   +++     %s",
                            $tableName, $entryFound[0], $field);

                    }
                    if (isset($replace) && $found) {
                        $strQuery= "update ".$tableName. " set ".$field."= replace(".$field.",'".$regexpPattern."','".$replace."');";
                        mysql_query($strQuery);
                        $replacementsDone = true;
                        printf("\n All '%s' occurrences have been replaced by '%s' in field %s",
                            $regexpPattern, $replace, $tableName.".".$field);
                    }

                }
            }
        }
}

if ($replacementsDone) {
    printf("\n"."To revert replacements, just execute \n".'./%s "%s" "%s"',
        basename(__FILE__), $replace, $regexpPattern);
}

mysql_close($link);

/**
 * Get table's fields names
 * @param string $tableName
 * @param array  $typesFilter
 *
 * @return array
 */
function textFields($tableName, $typesFilter = null)
{
    $strQuery = "desc ".$tableName;
    $resSet = mysql_query($strQuery);
    $res = array();
    while ($row = mysql_fetch_array($resSet)) {
        if (null == $typesFilter) {
            $res[] = $row['Field'];
        } else {
            foreach ($typesFilter as $typeName) {
                if (preg_match("/^".$typeName."/", $row['Type'])>0) {
                    $res[] = $row['Name'];
                }
            }
        }
    }

    return array_unique($res);
}