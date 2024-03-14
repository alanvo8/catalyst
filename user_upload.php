<?php

// Globals
global $ARGS;

// Constants
const DB_NAME = "catalyst";

// Init
$ARGS = [];

/**
 * Create a PHP script, that is executed from the command line, which accepts a CSV file as input (see command
line directives below) and processes the CSV file. The parsed file data is to be inserted into a MySQL database.
A CSV file is provided as part of this task that contains test data, the script must be able to process this file
appropriately.
 */

function displaySyntax() {
    echo "Command line options (directives):
--file [csv file name] – this is the name of the CSV to be parsed
--create_table – this will cause the MySQL users table to be built (and no further action will be taken)
--dry_run – this will be used with the --file directive in case we want to run the script but not insert into the DB. All other functions will be executed, but the database won't be altered
-u – MySQL username
-p – MySQL password
-h – MySQL host
--help – which will output the above list of directives with details.

Example running from the command line:
php user_upload.php –help";
    exit();
}

function createTable($username, $password, $host) {
    try {
        $conn = new mysqli($host, $username, $password, DB_NAME);
        if ($conn->connect_error) { 
            throw new Exception("Connection failed: " . $conn->connect_error);
        } else {
            echo "Connected successfully";
        }

        $sql = "CREATE TABLE IF NOT EXISTS `catalyst`.`users` (
            `id` INT NOT NULL AUTO_INCREMENT , 
            `name` VARCHAR(255) NOT NULL , 
            `surname` VARCHAR(255) NOT NULL , 
            `email` VARCHAR(255) NOT NULL , PRIMARY KEY (`id`), UNIQUE (`email`)
            ) ENGINE = InnoDB;";

        if ($conn->query($sql) === TRUE) {
            echo "Table created successfully";
        } else {
            throw new Exception("Error creating table: " . $conn->error);
        }
        
        $conn->close();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
    exit();
}



/* MAIN */

// check arguments - conditions to display syntax
if (!isset($argv) || count($argv) <= 1 || in_array("--help", $argv)) {
    displaySyntax();
}

for ($i=1; $i<count($argv); $i++) {
    $key = $argv[$i];

    if ($key[0]!="-") {
        displaySyntax();
    }

    if ($key == "--create_table" || $key == "--dry_run") {
        $value = 1;
    }
    else {
        $value = $argv[$i+ 1];
        $i++;
    }

    $ARGS[$key] = $value;
}

if (key_exists("--create_table", $ARGS)) {
    try {
        if (key_exists("-u", $ARGS) && key_exists("-p", $ARGS) && key_exists("-p", $ARGS)) {
            createTable($ARGS['-u'], $ARGS['-p'], $ARGS['-h']);
        } else {
            
            throw new Exception('Error creating table - Missing DB details');
            
        }
        

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        displaySyntax();
    }
    
}

if (key_exists("-file", $ARGS)) {

}



