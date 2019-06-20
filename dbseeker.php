#!/usr/bin/env php
<?php
$host = "127.0.0.1";
$password = "";
$options = getopt("h:u:p:d:s:r:");

if (! isset($_SERVER['argv'][1]) || "--help" == $_SERVER['argv'][1]) {
        echo <<<EOF
This tool searches or searches and replaces pattern in database
usage: dbseeker.php [-h host] -u user [-p password] -d databasename -s pattern [-r replacement_pattern]

EOF;
;
        exit (0);
}

if(isset($options["h"])) {
  if($options["h"] != "") {
    $host = $options["h"];
  }
}
if(isset($options["p"])) {
    $host = $options["p"];
}
if(isset($options["r"])) {
    $replace = $options["r"];
}
if(! isset($options["u"])) {
  die("You must give a user name : -u myusername");
} else {
  $user = $options["u"];
}
if(! isset($options["d"])) {
  die("You must give a database name : -d databasename");
} else {
  $database = $options["d"];
}
if(! isset($options["s"])) {
  die("You must give a pattern to search : -s pattern");
} else {
  $regexpPattern = $options["s"];
}


if (isset($replace)) {
    echo "Replace string given, all occurences of ".$regexpPattern." will be replaced by ".$replace."\n";
    echo "Replacements are case sensitive.\n";
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
                if (mysqli_num_rows($resSet2) > 0) {
                    $found = false;
                    while ($entryFound = mysqli_fetch_array($resSet2, MYSQLI_BOTH)) {
                        $found = true;
                        printf("\n%s   +++     %s   +++     %s",
                            $tableName, $entryFound[0], $field." (".getPreview($entryFound[$field]).")");

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

mysqli_close($link);

if ($replacementsDone) {
    printf("\n"."To revert replacements, just execute \n".'./%s -s %s -r %s',
        basename(__FILE__), $replace, $regexpPattern);
}

printf("%s", "\n");

function getPreview($text) {
  $preview = substr($text, 0, 40);
  if(strlen($text) > 40) {
    $preview .= "...";
  }

  return $preview;
}
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
