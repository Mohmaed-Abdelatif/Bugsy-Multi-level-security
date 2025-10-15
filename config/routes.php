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
        //No Authentication required "level 1"
        'POST /register'   => 'v1\Authcontroller@register',
        'POST /login'      => 'v1\Authcontroller@login',

        //product routes
        //public access
        'GET /products'    => 'v1\ProductController@index',
        'GET /search'      => 'v1\ProductController@search',
        'GET /{id}'        => 'v1\ProductController@show',

        //category routes
        //public access
        'GET /categories'  => 'v1\ProductController@categories',
        'GET /categories/{id}/products'  => 'v1\ProductController@categoryProducts',

        //cart routes
        //No authentication 'vulnerable'
        //user id any one can passed in request body or query
        'POST /cart/add'   => 'v1\CartController@add',
        'GET /cart'        => 'v1\CartController@show',
        'PUT /cart/update/{id}'  => 'v1\CartController@update',
        'DELETE /cart/remove/{id}'  => 'v1\CartController@remove',
        'DELETE /cart/clear' => 'v1\CartController@clear',

        //order routes
        //No authentication 'vulnerable'
        //anyone can access any user 's orders
        'Post /checkout'  =>'v1\OrderController@checkout',
        'GET /orders'     =>'v1\OrderController@index',
        'GET /orders/{id}'  =>'v1\OrderController@show',

        //user profile routes
        //No authentication 'culnerable'
        //anyone can access any user 's data
        'GET /user/{id}'  =>'v1\UserController@show',
        'PUT /user/{id}'  =>'v1\UserController@update',
        
    ],

    //level 2 routes secure
    // Base: /api/v2/
    'v2' =>[],

    //level 3 routes advanced
    //Base: api/v3/
    'v3' =>[],
];


