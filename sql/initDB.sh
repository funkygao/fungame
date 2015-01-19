#!/usr/bin/php -q
<?php

/**
 * this is a quick and dirty file that will 
 * drop all databases
 * create all databases
 * add tables to each database
 * add basic pho data used for unit tests
 */
$SHARDS_AND_AMOUNT = array(
    'ShardLookup' => array('name' => 'ShardLookup', 'num' => 1),
    'Global' => array('name' => 'Global', 'num' => 1),
    'UserShard' => array('name' => 'UserShard', 'num' => 2),
    'WorldShard' => array('name' => 'WorldShard', 'num' => 2), 
);


$MYSQL_CMD = "/usr/bin/mysql -uroot ";

/**
 * create the databases
 */
foreach ($SHARDS_AND_AMOUNT as $pool => $conf) {
    $num = $conf['num'];
    $name = $conf['name'];
    $DB = $name;
    for ($i = 1; $i <= $num; $i++) {
        if ($num > 1){
            $DB = $name . $i;
        }

        $cmd = $MYSQL_CMD . "-e'DROP DATABASE IF EXISTS $DB'";
        print "$cmd\n";
        system($cmd);

        $cmd = $MYSQL_CMD . "-e'CREATE DATABASE $DB'";
        print "$cmd\n";
        system($cmd);

        $path = dirname(dirname(__FILE__)) . '/sql/' . $pool;
        if (is_dir($path)) {
            if ($dh = opendir($path)) {
                while (($file = readdir($dh)) !== false) {
                    if (strpos($file, ".") === 0) { 
                        // it's dir instead of file
                        continue;
                    }
                    
                    $cmd = "$MYSQL_CMD $DB < $path/$file";
                    print $cmd . "\n";
                    system($cmd);
                }
                
                closedir($dh);
            }
        }
    }
}   
