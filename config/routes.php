<?php
//Api Routes congiguration => defines all Api endpoints and maps them to controllers
//http_method /path => controllerName@methodName
//parameters in url will passed as method parameters exm: /{id}
//--------------------------------------------------------
//the router "APP.php" will use this file routes
//user will enter exm: GET http://localhost/Bugsy/api/v1/products/5
/*
Router logic 'in App.php'
1. Parse URL: "api/v1/products/5"
2. Extract version: "v1"
3. Extract route: "products/5"
4. Load routes config: $routes['v1']
5. Match pattern: "GET /products/{id}" matches "products/5"
6. Extract parameter: {id} = 5
7. Get handler: "V1\ProductController@show"
8. Call: ProductController->show(5)
*/


return [

    //level 1 routes vulnerable
    //Base: /api/v1/
    'v1' =>[

        //Authentication routes
        'POST /register'   => 'v1\Authcontroller@register',
        'POST /login'      => 'v1\Authcontroller@login',
        'POST /logout'     => 'V1\AuthController@logout',
        
        
        //product routes
        //public access
        'GET /products'    => 'v1\ProductController@index',
        'GET /products/search'       => 'V1\ProductController@search',
        'GET /products/{id}' => 'V1\ProductController@show',
        //admin routes 
        'POST /products'             => 'V1\ProductController@create',          
        'POST /products/{id}'         => 'V1\ProductController@update',  //use POST in update coz with method PUT it alwase return empty for multipart
        'DELETE /products/{id}'      => 'V1\ProductController@delete',  
        // Product images management
        'GET /products/{id}/images'          => 'V1\ProductController@getProductImages',
        'POST /products/{id}/images'         => 'V1\ProductController@uploadAdditionalImages',
        'DELETE /products/images/{id}'       => 'V1\ProductController@deleteProductImage',
        'POST /products/{id}/images/replace' => 'V1\ProductController@replaceProductImages',
        
        // Reviews
        'GET /products/{id}/reviews'     => 'V1\ReviewController@index',      // Product reviews
        'GET /products/{id}/rating'      => 'V1\ReviewController@rating',     // Average rating
        'GET /reviews/{id}'              => 'V1\ReviewController@show',       // Single review
        'POST /products/{id}/reviews'    => 'V1\ReviewController@create',     // Add review
        'PUT /reviews/{id}'              => 'V1\ReviewController@update',     // Edit review
        'DELETE /reviews/{id}'           => 'V1\ReviewController@delete',     // Delete review
        'POST /reviews/{id}/helpful'     => 'V1\ReviewController@markHelpful', // Mark helpful
        'GET /users/{id}/reviews'        => 'V1\ReviewController@userReviews', // User's reviews


        //category routes
        //public access
        'GET /categories'                => 'V1\CategoryController@index',
        'GET /categories/{id}/products'  => 'v1\ProductController@categoryProducts',
        //admin routes 
        'POST /categories'                   => 'V1\CategoryController@create',          // Create category (future)
        'POST /categories/{id}'               => 'V1\CategoryController@update',          // Update category (future)   //use POST in update coz with method PUT it alwase return empty for multipart
        'DELETE /categories/{id}'            => 'V1\CategoryController@delete',          // Delete category (future)
        

        //brand routes
        //public access
        'GET /brands'                => 'V1\BrandController@index',      // List all brands
        'GET /brands/{id}/products'  => 'V1\BrandController@products',   // Products by brand
        //admin routes 
        'POST /brands'               => 'V1\BrandController@create',     // Create brand (future)
        'POST /brands/{id}'           => 'V1\BrandController@update',     // Update brand (future)   //use POST in update coz with method PUT it alwase return empty for multipart
        'DELETE /brands/{id}'        => 'V1\BrandController@delete',     // Delete brand (future)
        

        //cart routes
        'GET /cart'        => 'v1\CartController@show',
        'GET /cart/count'            => 'V1\CartController@count',       // Cart items count (for badge)
        'GET /cart/total'            => 'V1\CartController@total',       // Cart total price
        //admin routes 
        'POST /cart/add'             => 'V1\CartController@add',         // Add item to cart
        'PUT /cart/items/{id}'       => 'V1\CartController@updateItem',  // Update item quantity (VULNERABLE: IDOR)
        'DELETE /cart/items/{id}'    => 'V1\CartController@removeItem',  // Remove single item (VULNERABLE: IDOR)
        'DELETE /cart/clear'         => 'V1\CartController@clear',       // Clear entire cart
        
        //order routes
        'GET /orders'     =>'v1\OrderController@index',
        'GET /orders/{id}'  =>'v1\OrderController@show',
        'GET /orders/{id}/items'     => 'V1\OrderController@items',      // Order items (VULNERABLE: IDOR)
        'GET /orders/{id}/status'    => 'V1\OrderController@status',     // Order status tracking
        'POST /checkout'             => 'V1\OrderController@checkout',   // Create order from cart (VULNERABLE: no auth)
        'PUT /orders/{id}/cancel'    => 'V1\OrderController@cancel',     // Cancel order (VULNERABLE: IDOR)
        //admin routes 
        'PUT /orders/{id}/status'    => 'V1\OrderController@updateStatus',  // Update order status (admin)
       
        

        //user profile routes
        'GET /user/{id}'  =>'v1\UserController@show',
        'GET /users/{id}/orders'     => 'V1\UserController@orders',      // User's order history
        'GET /users/{id}/addresses'  => 'V1\UserController@addresses',   // Saved addresses
        'PUT /user/{id}'  =>'v1\UserController@update',
        'PUT /users/{id}/password'   => 'V1\UserController@changePassword',  // Change password (VULNERABLE)
        'POST /users/{id}/addresses' => 'V1\UserController@addAddress',  // Add new address
        'DELETE /users/{id}'         => 'V1\UserController@delete',      // Delete account
        //admin routes 


        //reiews
        'GET /products/{id}/reviews'     => 'V1\ReviewController@index',      // Product reviews
        'GET /products/{id}/rating'      => 'V1\ReviewController@rating',     // Average rating
        'GET /reviews/{id}'              => 'V1\ReviewController@show',       // Single review
        'POST /products/{id}/reviews'    => 'V1\ReviewController@create',     // Add review (future)
        'PUT /reviews/{id}'              => 'V1\ReviewController@update',     // Edit review (future)
        'DELETE /reviews/{id}'           => 'V1\ReviewController@delete',     // Delete review (future)
        

        //global search
        'GET /search'                    => 'V1\SearchController@all',           // Search everything (future)
        'GET /search/suggestions'        => 'V1\SearchController@suggestions',   // Autocomplete (future)
        'GET /search/trending'           => 'V1\SearchController@trending',      // Trending searches (future)
   
        


        'GET /test/public'             => 'V1\TestController@publicTest',
        'GET /test/protected'          => 'V1\TestController@protectedTest',
        'GET /test/admin'              => 'V1\TestController@adminTest',
        'GET /test/session'            => 'V1\TestController@sessionInfo',
        'GET /test/ownership/{user_id}' => 'V1\TestController@ownershipTest',
    ],

    //level 2 routes secure
    // Base: /api/v2/
    'v2' =>[
        // Will include all V1 routes PLUS:
        // - JWT authentication middleware
        // - Role-based access control (customer, admin)
        // - PDO prepared statements (no SQL injection)
        // - IDOR protection (users can only access their own data)
        // - Rate limiting
        // - Input validation & sanitization
        // - Password hashing (bcrypt)
    ],

    //level 3 routes advanced
    //Base: api/v3/
    'v3' =>[
        // Will include all V2 routes PLUS:
        // - AI-powered threat detection
        // - 2FA authentication
        // - Biometric auth support
        // - Advanced audit logging
        // - Anomaly detection
        // - Real-time fraud detection
        // - Advanced session management
    ],
];


