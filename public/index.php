<?php
//Entry point for all API requests: All requests flow throughh here thanks to .htaccess rewriting
//One job: start the app
//flow: User Request → .htaccess → index.php → App.php → Controller → Model → Database


//---------------------------------
//error handling :for any error in app or deeper
//---------------------------------
set_exception_handler(function ($exception) {
    // Log the error (always, even in production)
    error_log("═══════════════════════════════════════");
    error_log("FATAL ERROR: " . $exception->getMessage());
    error_log("File: " . $exception->getFile() . " Line: " . $exception->getLine());
    error_log("Trace: " . $exception->getTraceAsString());
    error_log("═══════════════════════════════════════");
    
    // Send clean JSON response
    http_response_code(500);
    header('Content-Type: application/json');
    
    if (defined('APP_ENV') && APP_ENV === 'development') {
        // Development: Show detailed error
        echo json_encode([
            'success' => false,
            'error' => 'Application Error',
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => explode("\n", $exception->getTraceAsString())
        ], JSON_PRETTY_PRINT);
    } else {
        // Production: Generic message (hide sensitive info)
        echo json_encode([
            'success' => false,
            'error' => 'Internal Server Error',
            'message' => 'Something went wrong. Please try again later.',
            'support' => 'Contact support if this issue persists.'
        ], JSON_PRETTY_PRINT);
    }
    exit;
});



//Load configuration
require_once dirname(__DIR__) . '/config/config.php';

try{
    //instantiate the App (router)
    $app = new Core\App();

    //run the application (handles routing,contrillers,erc.)
    $app->run();
}catch(Exception $e){
    //if exist wrong in App or deeper
    //the global exception handler above will catch this
    throw $e;
}

exit(0);
?>