<?php
require_once '../include/DbHandler.php';
require '../../libs/Slim/Slim.php';
 
\Slim\Slim::registerAutoloader();
 
$app = new \Slim\Slim();
 
// User id from db - Global Variable
$user_id = NULL;
 
/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }
 
    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}
 

/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
function authenticate(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();
 
    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        $db = new DbHandler();
 
        // get the api key
        $api_key = $headers['Authorization'];
        // validating api key
        if (!$db->isValidApiKey($api_key)) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        } else {
            global $user_id;
            // get user primary key id
            $user = $db->getUserId($api_key);
            if ($user != NULL)
                $user_id = $user["id"];
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoRespnse(400, $response);
        $app->stop();
    }
}
 
/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);
 
    // setting response content type to json
    $app->contentType('application/json');
 
    echo json_encode($response);
}
 

/**
 * Add a station to favorite
 */
$app->post('/addFavoriteStation/:id', 'authenticate',function($idStation) {
    global $user_id;
    
    $response = array();
    $response['error'] = false;
    
    $db = new DbHandler();
    $result = $db->addFavoriteStation($idStation, $user_id);
    if($result){
        $response['error'] = false;
        $response['message']="A Station is added into his favori";
        echoRespnse(201, $response);
    }else{
        $response['error'] = true;
        $response['message']="Failed to add this station into favori,check the data base";
        echoRespnse(200, $response);
    }
    
    
});

/**
 * Add a fuel to favorite
 */
$app->post('/addFavoriteCarburant/:id', 'authenticate',function($idCarburant) {
    global $user_id;
    
    $response = array();
    $response['error'] = false;
    
    $db = new DbHandler();
    $result = $db->addFavoriteCarburant($idCarburant, $user_id);
    if($result){
        $response['error'] = false;
        $response['message']="A fuel is added into his favori";
        echoRespnse(201, $response);
    }else{
        $response['error'] = true;
        $response['message']="Failed to add this fuel into favori,check the data base";
        echoRespnse(200, $response);
    }
    
    
});

/**
 * Add a label to favorite
 */
$app->post('/addFavoriteMarque/:id', 'authenticate',function($idMarque) {
    global $user_id;
    
    $response = array();
    $response['error'] = false;
    
    $db = new DbHandler();
    $result = $db->addFavoriteMarque($idMarque, $user_id);
    if($result){
        $response['error'] = false;
        $response['message']="A label is added into his favori";
        echoRespnse(201, $response);
    }else{
        $response['error'] = true;
        $response['message']="Failed to add this label into favori,check the data base";
        echoRespnse(200, $response);
    }
    
    
});


$app->run();