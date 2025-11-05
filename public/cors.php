<?php
// CORS Handler for InfinityFree
// This file MUST be included BEFORE any output

// Allowed origins
$allowedOrigins = [
    'https://gp-mobile-ecommerce.vercel.app',
    'http://localhost:3000',
    'http://localhost:5173',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: {$origin}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 3600');
}

// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}