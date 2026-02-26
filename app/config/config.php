

<?php

// Fichier de configuration de la connexion à la base de données (PDO)
// Contient les paramètres nécessaires : host, port, nom de la BDD et identifiants

return [
  'host' => 'localhost',       // serveur MySQL
  'port' => 3307,             // port utilisé par MySQL
  'dbname' => 'filrouge',    // nom de la base de données
  'user' => 'root',          // utilisateur MySQL
  'pass' => 'S@leh',         // mot de passe
  'charset' => 'utf8mb4'      // encodage pour supporter tous les caractères
];