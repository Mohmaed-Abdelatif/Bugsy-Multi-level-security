<?php
namespace Controllers\V1;

use Controllers\BaseController;

class TestController extends BaseController
{
    // Test 1: Basic response
    public function index()
    {
        return $this->json([
            'message' => 'BaseController is working!',
            'version' => $this->getVersion(),
            'method' => $this->getMethod()
        ]);
    }
    
    // Test 2: Query parameters
    public function testQuery()
    {
        // URL: /test/query?name=Ahmed&age=25
        $name = $this->getQuery('name', 'Guest');
        $age = $this->getQuery('age', 0);
        
        return $this->json([
            'name' => $name,
            'age' => $age,
            'all_query' => $this->getQuery()
        ]);
    }
    
    // Test 3: URL parameters
    public function testParam($id)
    {
        // URL: /test/param/123
        $idFromParam = $this->getParam('id');
        
        return $this->json([
            'id_from_argument_from_router_App' => $id,
            'id_from_method' => $idFromParam,
            'message' => 'Both should be the same!'
        ]);
    }
    
    // Test 4: POST body
    public function testBody()
    {
        // Send POST: {"name": "mohamed", "nikname": "medo"}
        $name = $this->getInput('name');
        $price = $this->getInput('nikname');
        $allData = $this->getAllInput();
        
        return $this->json([
            'name' => $name,
            'price' => $price,
            'all_data' => $allData
        ]);
    }
    
    // Test 5: Validation
    public function testValidation()
    {
        // Send POST: {"email": "test@test.com", "password": "123456"}
        $this->validate([
            'email' => 'required',
            'password' => 'required'
        ]);
        
        // If validation passes, return success
        return $this->json([
            'message' => 'Validation passed!',
            'data' => $this->getAllInput()
        ]);
    }
    
    // Test 6: Error response
    public function testError()
    {
        return $this->error('This is a test error', 400);
    }
    
    // Test 7: Pagination
    public function testPagination()
    {
        // URL: /test/pagination?page=2&per_page=10
        $pagination = $this->getPagination();
        
        return $this->json([
            'pagination' => $pagination,
            'message' => 'Use these values in database query'
        ]);
    }
}