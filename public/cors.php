<?php
// CORS Handler for InfinityFree
// This file MUST be included BEFORE any output

header('Content-Type: application/json');

// Allowed origins
$allowedOrigins = [
    'https://gp-mobile-ecommerce.vercel.app',
    'http://localhost:3000',
    'http://localhost:5173',
    'http://localhost:5500',
    'http://127.0.0.1:3000',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:5500',
];

// Get origin from request
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Allow localhost with any port (for dev flexibility)
$isLocalhost = str_starts_with($origin, 'http://localhost') || str_starts_with($origin, 'http://127.0.0.1');

// Apply CORS headers if origin is allowed
if (in_array($origin, $allowedOrigins) || $isLocalhost) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 3600');
}

// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}