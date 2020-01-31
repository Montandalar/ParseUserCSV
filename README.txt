Installation
============

Requirements
------------
PHP, mariadb or mysql, PDO and the PDO MySQL driver are required for this
application. The following one-liner will install them on Ubuntu/Debian systems:

    $ sudo apt install php mariadb php-mysql

Setup
-----
After installing the requirements, you may want to to create a new database to
point the application; you will need a database user with CREATE, DROP, SELECT
and INSERT privileges on the table 'users' as well.

The following commands at the mysql shell will create the user with appropriate
privileges:

    $ sudo mysql

        CREATE USER phpapp identified by 'phpapp';
        GRANT CREATE ON appdb.users to 'phpapp';
        GRANT DROP on appdb.users to 'phpapp';
        GRANT SELECT on appdb.users to 'phpapp';
        GRANT INSERT on appdb.users to 'phpapp';

After completing those as root, create the database with the new user. The
application does not do this on its own.

    $ mysql -u phpapp -p

        CREATE DATABASE appdb;

Once you have a database and a user set up, you are ready to run the application
and provide those details on the command line.

Security note
-------------
Since you have to provide the user's password on the command line, make sure it
is not recorded in your shell history.

In bash this is controlled by the HISTCONTROL variable, which must be set to
ignoreboth (this is usually the default). To make sure you invocation of this
application is not recorded, start the command line with a space character.
