return [
  'host' => getenv('DB_HOST') ?: getenv('MYSQLHOST'),
  'port' => getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: 3306,
  'dbname' => getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: 'filrouge',
  'user' => getenv('DB_USER') ?: getenv('MYSQLUSER'),
  'pass' => getenv('DB_PASS') ?: getenv('MYSQLPASSWORD'),
  'charset' => 'utf8mb4'
];
