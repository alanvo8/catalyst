<?php

/*
Breakdowns:
1. Get command line arguments
    a. Display syntax
    b. Parse arguments
2. SQL
    a. Connection
    b. Create table
    c. Insert
3. Read CSV file
    
*/


// Globals
global $ARGS;

// Constants
const DB_NAME = "catalyst";

// Init
$ARGS = [];

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

function createTable() {
    global $ARGS;
    try {
        $conn = new mysqli($ARGS['-h'], $ARGS['-u'], $ARGS['-p'], DB_NAME);
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

function readCSVFile($filename) {
    try {
        $csv = array_map('str_getcsv', file($filename));
        return $csv;
    } catch (Exception $e) {   
        echo "Error: " . $e->getMessage();
    }
}

function uploadUser($data) {
    global $ARGS;
    try {
        $conn = new mysqli($ARGS['-h'], $ARGS['-u'], $ARGS['-p'], DB_NAME);
        if ($conn->connect_error) { 
            throw new Exception("Connection failed: " . $conn->connect_error);
        } else {
            echo "Connected successfully \n";
        }
        $inserted_rows = 0;
        $found_rows = count($data) - 1;
        echo "Found $found_rows rows \n";
        $is_dryrun = key_exists("--dry_run", $ARGS);

        for ($i = 1; $i < count($data); $i++) {
            $d = $data[$i];
            $name = ucfirst(trim(htmlspecialchars($d[0])));
            $surname = ucfirst(trim(htmlspecialchars($d[1])));
            $email = strtolower(trim(htmlspecialchars($d[2])));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo "Invalid email format. $email \n";
            }
            $sql = "INSERT INTO `users` (`id`, `name`, `surname`, `email`) VALUES (NULL, '$name', '$surname', '$email');";
            try {
                if (!$is_dryrun) {
                    if ($conn->query($sql) === TRUE) {
                        $inserted_rows+=1;
                    } else {
                        throw new Exception("Error: " . $conn->error);
                    }
                }          
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
        
        if (!$is_dryrun) {
            echo "\nInserted successfully: $inserted_rows \n";
        } else { 
            echo "\nDry run. Inserted 0 rows\n";
        }

        $conn->close();
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}
/* MAIN */

function getArguments($argv) {
    global $ARGS;
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
}

getArguments($argv);

# Create table logic
if (key_exists("--create_table", $ARGS)) {
    try {
        if (key_exists("-u", $ARGS) && key_exists("-p", $ARGS) && key_exists("-p", $ARGS)) {
                createTable();
        } else {
            throw new Exception('Error creating table - Missing DB details');
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}

# File logic
if (key_exists("--file", $ARGS)) {
    try {
        $fileName = $ARGS["--file"];
        $data = readCSVFile($fileName);
        $rowCount = count($data);

        if ($rowCount > 0) uploadUser($data);
        else 
            echo "Dry Run: $rowCount found, 0 uploaded";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}



