# sql_php_sandbox

# Simple boilerplate HTML / PHP/ Javascript codes for working with SQL data bases
## Tested with:
- mysql  Ver 8.0.23
- PHP 7.4.3
- Mozilla Firefox 85.0.1
- Javascript 1.5

## File sql_tables.php 
Displays a simple HTML form that gives the choice of tables within some predefined
SQL database, and allows one to send query via post method.  The script renders the resulting table in HTML format, assuming
that the table contains numeric and string values. Uses minimal HTML, Javascript, and CSS styling. 
One should change these constants that correspond to the desired SQL host, account, and the data base. 
```PHP
// SQL login credentials.
// Confidential info which is different for different users.
const __lgin_creds__ =
["hostname" => "secret",       // name of the host on which SQL is running, best to run it on localhost
"username" => "top_secret",    // user name for the data base access
"password" => "top_secret"];   // password for the data base access
const __db_name__ = "secret";  // default data base name
const __nrowsmax__ = 5;        // default maximum number of queried rows
const __NROWSMAX__ = 100000;   // top limit on the number of rows a user can request
```
