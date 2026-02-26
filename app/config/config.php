<?php

return [
  'host' => getenv('MYSQLHOST'),
  'port' => getenv('MYSQLPORT'),
  'dbname' => 'filrouge',
  'user' => getenv('MYSQLUSER'),
  'pass' => getenv('MYSQLPASSWORD'),
  'charset' => 'utf8mb4'
];
