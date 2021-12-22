<?php

set_time_limit(0);
ignore_user_abort(true);


require __DIR__ . '/../src/MySQLDump.php';

$time = -microtime(true);

$dump = new MySQLDump(new mysqli('localhost', 'root', 'password', 'database'));
$dump->save('dump ' . date('Y-m-d H-i') . '.sql.gz');

$time += microtime(true);
echo "FINISHED (in $time s)";
