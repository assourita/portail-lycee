<?php

require 'vendor/autoload.php';

$passwords = [
    'admin123',
    'enseignant123',
    'comptable123',
    'etudiant123'
];

foreach ($passwords as $password) {
    echo "Password: " . $password . "\n";
    echo "Hashed: " . password_hash($password, PASSWORD_DEFAULT) . "\n\n";
} 