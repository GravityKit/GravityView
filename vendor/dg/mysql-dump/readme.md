MySQL Dump Utility
==================

This is a backup utility used to dump a database for backup or transfer to another MySQL server.
The dump typically contains SQL statements to create the table, populate it, or both.

It requires PHP 5.6 or later.

Usage
-----

Create [MySQLi](http://www.php.net/manual/en/mysqli.construct.php) object and pass it to the MySQLDump:

```php
$db = new mysqli('localhost', 'root', 'password', 'database');
$dump = new MySQLDump($db);
```

You can optionally specify how each table or view should be exported:

```php
$dump->tables['search_cache'] = MySQLDump::DROP | MySQLDump::CREATE;
$dump->tables['log'] = MySQLDump::NONE;
```

Then simply call `save()` or `write()`:

```php
$dump->save('export.sql.gz');
```

Import dump from file to database this way:

```php
$import = new MySQLImport($db);
$import->load('dump.sql.gz');
```

If you like it, **[please make a donation now](https://nette.org/make-donation?to=mysql-dump)**. Thank you!
