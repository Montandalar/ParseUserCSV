# User CSV Parser

This PHP script will import users from a CSV file into a database of your
choice. The CSV file should contain three fields: name, surname and email. They
can appear in any order in the header row. Whitespace before or after each field
will be ignored.

## Requirements

* PHP 7.2
* MySQL 5.7 OR MariaDB 10.x
* The extensions for PHP Data Objects and the PDO driver for MySQL
* Composer to install the dependenices

## Usage

You will need to specify a username, password, database and host to connect to.
The user will need to have privileges to CREATE, DROP, SELECT and INSERT on the
'users' table. See 'Installation' for help on creating a database.

The program will also print a shorter version of this help.

Usage: php user\_upload.php [options]
                <--file <file> [--dry\_run] | --create\_table>

Options:
    --file <file>   Specifies the path of the CSV to parse. You must specify
                    either this option or --create_table.
    --create_table Create the MySQL users table, then quit. If the table
                    already exists, any data already in it will be lost.
    --dry_run      Run the program, but do not update the database. Invalid
                    email addresses will be printed to the output.

    -u <user>       Connect with this MySQL user
    -p <password>   Connect with this MySQL password
    -d <database>   Connect to this MySQL database
    -h <host>       Connect to the MySQL database on this host.

## Installation

You can install the required packages on an Ubuntu/Debian system with the
following one-liner:

    $ sudo apt install php mariadb php-mysql composer

In order to set up the database, you will need to log in with a user that has
privileges to create other users and grant them privileges. If you don't already
have a user in the database, you should log in as root

    $ sudo mysql

Inside the mysql shell, the following commands will create a user 'phpapp', with
password 'phpapp' and who can perform the necessary operations on the database
'appdb'.

	CREATE USER phpapp identified by 'phpapp';
	GRANT CREATE ON appdb.users to 'phpapp';
	GRANT DROP on appdb.users to 'phpapp';
	GRANT SELECT on appdb.users to 'phpapp';
	GRANT INSERT on appdb.users to 'phpapp';

You will also need to create the database. You can do this with the new user
you created:

	$ mysql -u phpapp -p
	(Enter the password when prompted)

	CREATE DATABASE appdb;

Finally, you will need to install the dependencies with composer (an external
library is used to handle command line arguments). Run the following in the same
directory as the script:

    $ composer install

After creating the database and installing the dependencies, you are ready to
run the script. You should provide the details of the user and database you
just created to the script.

### Tests

WARNING: Running the tests will erase any data in the 'users' table.
Notice: The tests only work with the default database setup shown in
'Installation'. If you want to use your own settings, edit the variables at
the top of test.php.

`test.php` contains a series of tests for the program. You can run it with
    
    $ php test.php

In order to run the tests, you need to set the following in your php.ini
(Ubuntu/Debian default: /etc/php/7.2./cli/php.ini):

    zend.assertions = 1

If there are any assertion exceptions thrown, the program is not working
properly. Other awrnings or errors that appear are just the output of the
program.
