<?php

define("EXIT_SUCCESS", 0);
define("EXIT_BAD_INVOCATION", 1);
define("EXIT_DATABASE_ERROR", 2);

class UserUploader {
    private function printUsage() {
?>
    user_upload.php - Parse a CSV of users into the database

        --file [csv file name] - path of the CSV to be parsed
        --create_table - this will cause the MySQL users table to be built
                         (and no further action will be taken)
        --dry_run - don't alter the database
        -u - MySQL username
        -p - MySQL password
        -h - MySQL host
        -d - MySQL database
        --help - show this help
<?php
    }

    private function createTable() {
        try {
            $stmt = $this->dbh->prepare(
<<<'EOD'
CREATE OR REPLACE TABLE users (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	email VARCHAR(200),
	name VARCHAR(200),
	surname VARCHAR(200)
);
EOD
            );
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error creating table in database: ", $e->getMessage(), "\n";
            return false;
        }
    }

    public function main() {
        global $argv;
        global $argc;

        $opts = getopt("u:p:h:d:", ["file::", "create_table", "dry_run::", "help::"],
            $optind);

        //var_dump($opts);
        if (isset($opts["help"])) {
            $this->printUsage();
            exit(EXIT_BAD_INVOCATION);
        }

        // Not the approach to use if the application would be localised, but it
        // is quicker to write if the only language will be english
        foreach (["u" => "username", "p" => "password",
                  "h" => "host", "d" => "database"]
                    as $opt => $msg)
        {
            if (!isset($opts[$opt])) {
                printf("Please specify the %s for MySQL\n", $msg);
                $this->printUsage();
                exit(EXIT_BAD_INVOCATION);
            }
        }

        // Open the database connection
        try {
            $this->dbh = new PDO(
                sprintf('mysql:host=%s;dbname=%s', $opts['h'], $opts['d']),
                $opts['u'],
                $opts['p']);
        } catch (PDOException $e) {
            echo "Error connecting to database: ", $e->getMessage(), "\n";
            if ($e->getCode() == 1049) {
                echo "You should create the database first before using it with user_upload.php\n";
            } else if ($e->getCode() == 1044) {
                echo "Make sure your database user has permissions to use the database\n";
            }
            exit(EXIT_DATABASE_ERROR);
        }

        // If the option --create_table was given do that now then quit
        if (isset($opts['create_table'])) {
            $this->createTable();
            exit(EXIT_SUCCESS);
        }

        // If the table does not exist, quit now
        
        // Load the CSV

        // Do the database insertion

    }
}

$uploader = new UserUploader();
$uploader->main();
?>
