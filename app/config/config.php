<?php

return [

  'host' => getenv('MYSQLHOST') ?: 'localhost',

  'port' => getenv('MYSQLPORT') ?: 3307,

  'dbname' => getenv('MYSQLDATABASE') ?: 'filrouge',

  'user' => getenv('MYSQLUSER') ?: 'root',

  'pass' => getenv('MYSQLPASSWORD') ?: 'S@leh',

  'charset' => 'utf8mb4'

];
