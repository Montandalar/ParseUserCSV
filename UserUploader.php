<?php
require_once 'vendor/autoload.php';

class UserUploader {
	public const EXIT_SUCCESS = 0;
	public const EXIT_BAD_INVOCATION = 1;
	public const EXIT_DATABASE_ERROR = 2;
	public const EXIT_NO_TABLE = 3;
	public const EXIT_NO_FILE = 4;

    /** @var PDO */
    private $dbh;
    /** @var resource */
    private $input;

    private function createTable() : bool {
        try {
            $stmt = $this->dbh->prepare(
<<<'EOD'
CREATE OR REPLACE TABLE users (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	email VARCHAR(100) UNIQUE,
	name VARCHAR(100),
	surname VARCHAR(100)
);
EOD
            );
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error creating table in database: ", $e->getMessage(), "\n";
            return false;
        }
    }

    private function tableExists() : bool {
        try {
            $stmt = $this->dbh->prepare("SHOW TABLES LIKE 'users';");
            $res = $stmt->execute();
            return $res && ($stmt->rowCount() > 0);
        } catch (PDOException $e) {
            echo "Error checking table: ", $e->getMessage(), "\n";
            return false;
        }
    }

    // This function has the drawback that the names like O'Reilly and McDonald
    // have their capitalisation ruined (assuming it was good to begin with).
    // The trade off is names in ALL CAPS or MeSSy CAse are fixed.
    // The scope of this project is a bit small to worry about adding surname
    // capitalisation rules.
    private function preprocessName(string $n) : string {
        // Names don't have to be purely alphabetical. This isn't a rule applied
        // to names in many places.
        $n = trim($n);
        $n = strtolower($n);
        $n[0] = strtoupper($n[0]);
        return $n;
    }

    // Uses the 
    private function loadCSV(string $filename, bool $dryRun) : int {
        $line = fgetcsv($this->input);
        if ($line === NULL) { // empty file
            echo "$filename: Error: Invalid file\n";
            return UserUploader::EXIT_NO_FILE;
        }
        if ($line === FALSE) {
            echo "$filename: Warning: Empty file\n";
            return UserUploader::EXIT_SUCCESS;
        }

        $validColumns = ['name', 'surname', 'email'];
        $columnIndexes = [];
        foreach ($line as $idx => $column) {
            $colName = trim($column);
            $columnValid = false;
            foreach ($validColumns as $vc) {
                if (strcasecmp($vc, $colName) == 0) {
                    $columnValid = true;
                }
            }
            if ($columnValid === false) {
                printf("Invalid column present in CSV file: %s",
                        $columnIndexes[$column]);
                return false;
            }
            $columnIndexes[$colName] = $idx;
        }

        $queryStr =
<<<'EOD'
INSERT INTO users(email, name, surname)
VALUES (:email, :name, :surname)
EOD;
        $stmt = $this->dbh->prepare($queryStr);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':surname', $surname);
        $lineNo = 2;
        do {
            $line = fgetcsv($this->input);
            ++$lineNo;
            if ($line === FALSE) {
                return UserUploader::EXIT_SUCCESS; // Done
            }
            if ($line == [NULL]) {
                continue; // Empty line
            }

            $email = $line[$columnIndexes["email"]];
            $email = trim($email);
            $email = strtolower($email);
            // Yes, exclamation marks are valid in email addresses
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                printf("%s: Warning: Invalid email %s on line %d\n",
                         $filename, $email, $lineNo);
                continue;
            }

            $name = $this->preprocessName($line[$columnIndexes["name"]]);
            $surname = $this->preprocessName($line[$columnIndexes["surname"]]);

            if ($dryRun) continue;
            $result = $stmt->execute();
            if ($result === FALSE) {
                $einfo = $stmt->errorInfo();
                // einfo[2] is message, [1] is the db-specific code
                printf("%s: Insertion failed for line %d: %s (code: %d)\n",
                         $filename, $lineNo, $einfo[2], $einfo[1]);
            }
        } while(1);
    }

    // This function must remain public so it can be accessed from outside
    public static function handleWarning($errno, $errstr) : bool {
        if (strstr($errstr,"Permission denied")) {
            echo "$errstr\n";
            return true;
        }
		if (strstr($errstr, "No such file")) {
			echo "$errstr\n";
			return true;
		}
        return false;
    }

    public function main($argv) : int {
        set_error_handler("UserUploader::handleWarning", E_WARNING);

		$paramDefs = [
			\GetOpt\Option::create(null, 'file', \GetOpt\GetOpt::REQUIRED_ARGUMENT)
				->setDescription("Path of the CSV to be parsed"),
			\GetOpt\Option::create(null, 'create_table', \GetOpt\GetOpt::NO_ARGUMENT)
				->setDescription("Create the MySQL users table, then quit"),
			\GetOpt\Option::create(null, 'dry_run', \GetOpt\GetOpt::NO_ARGUMENT)
				->setDescription("Don't alter the database"),

			\GetOpt\Option::create('u', null, \GetOpt\GetOpt::REQUIRED_ARGUMENT)
				->setDescription('MySQL user'),
			\GetOpt\Option::create('p', null, \GetOpt\GetOpt::REQUIRED_ARGUMENT)
				->setDescription('MySQL password'),
			\GetOpt\Option::create('h', null, \GetOpt\GetOpt::REQUIRED_ARGUMENT)
				->setDescription('MySQL host'),
			\GetOpt\Option::create('d', null, \GetOpt\GetOpt::REQUIRED_ARGUMENT)
				->setDescription('MySQL database'),


			\GetOpt\Option::create('?', 'help', \GetOpt\GetOpt::NO_ARGUMENT)
				->setDescription("Print this help")
		];

		$opts = new \GetOpt\GetOpt($paramDefs);
		try {
			$opts->process($argv);
		} catch (GetOpt\ArgumentException $e) {
			echo $e->getMessage(), "\n";
			return UserUploader::EXIT_BAD_INVOCATION;
		}
		foreach ($opts as $key => $value) { echo sprintf('%s: %s', $key, $value) . PHP_EOL; }

        if ($opts["help"]) {
			echo $opts->getHelpText();
            return UserUploader::EXIT_SUCCESS;
        }

        // Not the approach to use if the application would be localised, but it
        // is quicker to write if the only language will be english
        foreach (["u" => "username", "p" => "password",
                  "h" => "host", "d" => "database"]
                    as $opt => $msg)
        {
            if (!isset($opts[$opt])) {
                printf("Please specify the %s for MySQL\n", $msg);
                echo $opts->getHelpText();
                return UserUploader::EXIT_BAD_INVOCATION;
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
            return UserUploader::EXIT_DATABASE_ERROR;
        }

        if (isset($opts['create_table'])) {
            $this->createTable();
            return UserUploader::EXIT_SUCCESS;
        }

        if (!isset($opts['file'])) {
            echo "Error: no input file specified\n";
            echo $opts->getHelpText();
            return UserUploader::EXIT_NO_FILE;
        }

        if (!file_exists($opts['file'])) {
            echo "Error: no such file: ", $opts['file'], "\n";
			return UserUploader::EXIT_NO_FILE;
        }

        if (!$this->tableExists()) {
            echo "The 'users' table does not exist. Run this program with --create_table\n";
            return UserUploader::EXIT_NO_TABLE;
        }

        // Load the CSV
        // Binary mode won't be necessary on this text file
        $this->input = fopen($opts['file'], 'r');
        if (!$this->input) {
            printf("Could not open file: %s\n", $opts['file']);
            return UserUploader::EXIT_NO_FILE;
        }

        $ret = $this->loadCSV($opts['file'], isset($opts['dry_run']));
        fclose($this->input);
		return $ret;

    }
}


