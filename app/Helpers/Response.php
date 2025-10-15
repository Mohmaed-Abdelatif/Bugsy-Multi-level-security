<?php
/*
This class provides standardized JSON responses for the API.
I want methods like:
Response::success() - for successful responses
Response::error() - for error responses
Response::json() - for custom JSON"
This will simplify json response and reusable!
*/

namespace Helpers;
class Response
{
    //will make all func static coz it performs a general task 'no object data' so creating an object is just wasted memory ,and to call it without creating an object => Response::func-name

    //send successul json response (array data, string message def=null ,int http status code def=200 success)
    public static function success($data=[], $message=null, $statusCode=200){
        http_response_code($statusCode);

        //start building response in an associative array for json conversion
        $response=[
            'success' => true
        ];

        if($message !== null){
            $response['message'] = $message;
        }

        if(is_array($data)){
            //merge to to make response flat,not make aray for data in array of ersponse
            $response = array_merge($response, $data);
        }else{
            $response['data'] = $data;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }


    //send error json response (string message, int http status cod def=400, array errors def= null)
    public static function error($message, $statusCode=400, $errors=null){
        http_response_code($statusCode);
        
        //build error response
        $response =[
            'success' => false,
            'message' => $message,
        ];

        if($errors !== null){
            $response['errors'] = $errors;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }


    //send custom json response (array data , int http statucs cose def=200)
    public static function json($data, $statusCode=200){
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }


    //send not found response (string message def)
    public static function notFound($message='Resource not found'){
        //call error static function in the same class
        self::error($message, 404);
    }


    //send unauthorized response (string message)
    public static function unauthorized($message='Unauthorized'){
        self::error($message, 401);
    }

    //send forbidden response (string message)
    public static function forbidden($message='Forbidden'){
        self::error($message, 403);
    }

    public static function serverError($message='Internal Server Error'){
        self::error($message,500);
    }
}
