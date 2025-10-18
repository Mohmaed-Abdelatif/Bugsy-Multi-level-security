<?php
namespace Controllers\V1;

class TestController {
    public function index() {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Router is working correctly!',
            'version' => 'v1'
        ]);
        exit;
    }

    public function setRequestData(){}
}