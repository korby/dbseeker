#!/usr/bin/env php
<?php
// DB CONFIGS
$host = "127.0.0.1";
$database = "website";
$user = "root";
$password = "";

if (! isset($_SERVER['argv'][1])) {
    echo "Please, give a pattern to search enclosed by double quotes, ex. : \"http:\"\n";
    exit (0);
} else {
    if ("-h" == $_SERVER['argv'][1] || "--help" == $_SERVER['argv'][1]) {
        echo <<<EOF
This tool searches or searches and replaces pattern in database
usage: dbseeker.php pattern [replacement_pattern]

EOF;
;
        exit (0);
    }
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
    } else {
        printf("Backuping database here: %s", getcwd()."/".$database.".sql");
        $cmd = sprintf("mysqldump -h %s -u %s %s %s > %s", $host, $user, ("" != $password)? "-p".$password:"", $database, getcwd()."/".$database.".sql");
        shell_exec($cmd);
    }
}

// CONFIGS
// $tabFieldsTypeText = array("varchar", "tinytext", "text", "mediumtext", "longtext");
$tabFieldsTypeText = null;
$excludedTables = array();

printf("return entries containing pattern '%s'", $regexpPattern);

echo "\nTable   +++     First Field value   +++     field with pattern";
echo "\n________________________________________________________________";

$link = mysqli_connect($host, $user, $password)
or die("Impossible de se connecter : " . mysql_error());

mysqli_select_db($link, $database)
or die('Could not select database.');

$strQuery = "Show tables ";
$resSet = mysqli_query($link, $strQuery);
$replacementsDone = false;
while ($table = mysqli_fetch_array($resSet)) {
    $tableName = $table[0];
        if (! in_array($tableName, $excludedTables)) {
            $aFields = textFields($link, $tableName, $tabFieldsTypeText);
            foreach ($aFields as $field) {
                $strQuery= "select * from ".$tableName. " where ".$field." regexp '".$regexpPattern."' ";
                $resSet2 = mysqli_query($link, $strQuery);
                if (is_resource($resSet2)) {
                    $found = false;
                    while ($entryFound = mysqli_fetch_array($resSet2)) {
                        $found = true;
                        printf("\n%s   +++     %s   +++     %s",
                            $tableName, $entryFound[0], $field);

                    }
                    if (isset($replace) && $found) {
                        $strQuery= "update ".$tableName. " set ".$field."= replace(".$field.",'".$regexpPattern."','".$replace."');";
                        mysqli_query($link, $strQuery);
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

mysqli_close($link);

/**
 * Get table's fields names
 * @param string $tableName
 * @param array  $typesFilter
 *
 * @return array
 */
function textFields($link, $tableName, $typesFilter = null)
{
    $strQuery = "desc ".$tableName;
    $resSet = mysqli_query($link, $strQuery);
    $res = array();
    while ($row = mysqli_fetch_array($resSet)) {
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
