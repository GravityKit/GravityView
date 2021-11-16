<?php

set_time_limit(0);
ignore_user_abort(true);


require __DIR__ . '/../src/MySQLDump.php';

$dump = new MySQLDump(new mysqli('localhost', 'root', 'password', 'database'));

ini_set('zlib.output_compression', true);
header('Content-Type: application/x-gzip');
header('Content-Disposition: attachment; filename="dump ' . date('Y-m-d H-i') . '.sql.gz"');
header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-cache');
header('Connection: close');

$dump->write();
