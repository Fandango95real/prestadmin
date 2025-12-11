<?php
session_start();

// Invalide la connexion validée
$_SESSION['connection_validated'] = false;

// Retourne un JSON pour confirmer
header('Content-Type: application/json');
echo json_encode(['success' => true]);
