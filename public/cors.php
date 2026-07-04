<?php
header('Content-Type: application/json; charset=utf-8');

$requestUri = $_SERVER['REQUEST_URI'] ?? '';

if (preg_match('#^/api/v1(/|$)#', $requestUri)) {
    require_once __DIR__ . '/cors_v1.php';
    return;
}

if (preg_match('#^/api/v2(/|$)#', $requestUri) || preg_match('#^/api/v3(/|$)#', $requestUri)) {
    require_once __DIR__ . '/cors_v2.php';
    return;
}