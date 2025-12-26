<?php
// The only use for this is to generate hashes for passwords
// The plain text passwords TO hash
$password1 = '#Adm1n$Lock3d';
$password2 = 'Le3#Inv!nc1bleF0rc3';

// Generates hashed passwords using BCRYPT
$hashedPassword1 = password_hash($password1, PASSWORD_BCRYPT);
$hashedPassword2 = password_hash($password2, PASSWORD_BCRYPT);

// Display the hashed passwords
echo "Hashed Password 1: " . $hashedPassword1 . "<br>";
echo "Hashed Password 2: " . $hashedPassword2 . "<br>";