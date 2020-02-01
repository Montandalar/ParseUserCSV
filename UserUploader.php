<?php
use Garden\Cli\Cli;
use Garden\Cli\Args;

require_once 'vendor/autoload.php';


// TODO: DRY RUNS
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
    /** @var Cli */
    private $opts;
    /** @var Args */
    private $args;

    private function printUsage() {
        $this->opts->writeUsage($this->args);
    }

    private function createTable() {
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

    private function tableExists() {
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
    private function preprocessName(string $n) {
        $n = trim($n);
        $n = strtolower($n);
        $n[0] = strtoupper($n[0]);
        return $n;
    }

    // Uses the 
    private function loadCSV(string $filename) {
        $line = fgetcsv($this->input);
        assert($line != NULL);
        if ($line === FALSE) return;

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
                return; // Done
            }
            if ($line == [NULL]) {
                continue; // Empty line
            }
            // TODO:
            // * Check result of execution. Email must be unique.
            // * Pre-process / validate each field

            $email = $line[$columnIndexes["email"]];
            $email = trim($email);
            $email = strtolower($email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                printf("%s: Warning: Invalid email %s on line %d\n",
                         $filename, $email, $lineNo);
                continue;
            }

            $name = $this->preprocessName($line[$columnIndexes["name"]]);
            $surname = $this->preprocessName($line[$columnIndexes["surname"]]);
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
    public static function handleWarning($errno, $errstr) {
        if (strstr($errstr,"Permission denied")) {
            echo "$errstr\n";
            return true;
        }
        return false;
    }

    public function __construct(Cli $opts, Args $args) {
        $this->opts = $opts;
        $this->args = $args;
    }

    public function main(): int {
        set_error_handler("UserUploader::handleWarning", E_WARNING);

        // Open the database connection
        try {
            $this->dbh = new PDO(
                sprintf('mysql:host=%s;dbname=%s',
                    $this->args['host'], $this->args['dataase']),
                $this->args['user'],
                $this->args['password']);
        } catch (PDOException $e) {
            echo "Error connecting to database: ", $e->getMessage(), "\n";
            if ($e->getCode() == 1049) {
                echo "You should create the database first before using it with user_upload.php\n";
            } else if ($e->getCode() == 1044) {
                echo "Make sure your database user has permissions to use the database\n";
            }
            return UserUploader::EXIT_DATABASE_ERROR;
        }

        if (isset($this->args['create_table'])) {
            $this->createTable();
            return UserUploader::EXIT_SUCCESS;
        }

        if (!isset($this->args['file'])) {
            echo "Error: no input file specified\n";
            $this->printUsage();
            return UserUploader::EXIT_NO_TABLE;
        }

        if (!file_exists($this->args['file'])) {
            echo "Error: no such file: ", $this->args['file'];
        }

        if (!$this->tableExists()) {
            echo "The 'users' table does not exist. Run this program with --create_table\n";
            return UserUploader::EXIT_NO_TABLE;
        }

        // Load the CSV
        // Binary mode won't be necessary on this text file
        $this->input = fopen($this->args['file'], 'r');
        if (!$this->input) {
            printf("Could not open file: %s\n", $this->args['file']);
            return UserUploader::EXIT_NO_FILE;
        }

        $this->loadCSV($this->args['file']);
        fclose($this->input);

    }
}
?>
