<?php

set_time_limit(0);
ignore_user_abort(true);


require __DIR__ . '/../src/MySQLImport.php';

$time = -microtime(true);

$import = new MySQLImport(new mysqli('localhost', 'root', 'password', 'database'));

$import->onProgress = function ($count, $percent) {
	if ($percent !== null) {
		echo (int) $percent . " %\r";
	} elseif ($count % 10 === 0) {
		echo '.';
	}
};

$import->load('dump.sql.gz');

$time += microtime(true);
echo "FINISHED (in $time s)";
