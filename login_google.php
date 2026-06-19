<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require_once 'vendor/autoload.php';

$clientID = '413048648794-vr0kcperf75a3c4qk644b7e1lb80c57n.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri = 'http://localhost/Nexus%20Network%20-%20Grupo%204/NexusNetwork/google-callback.php';

$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope("email");
$client->addScope("profile");

// Redireciona para a página de autorização do Google
header('Location: ' . $client->createAuthUrl());
exit;
?>