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

/*
 * METHODS WITHOUT AUTHTENTICATION
 */
/**
 * delete a favorite carburant
 * url - /deleteCarburantFromFavori/:id
 * method - DELETE
 */
$app->delete('/deleteCarburantFromFavori/:id', 'authenticate', function($idCarburant){
    
    global $user_id;
    
    $response = array();
    $response['error'] = false;
    $db = new DbHandler();
    $result = $db->deleteCarburantFromFavori($idCarburant, $user_id);
    if ($result) {
                    $response["error"] = false;
                    $response["message"] = "A Carburant is deleted from his favori";
                    echoRespnse(201, $response);

                } else {
                    // unknown error occurred
                    $response['error'] = true;
                    $response['message'] = "An error occurred. Please try again";
                    echoRespnse(200, $response);
                }
});

/**
 * delete a favorite station
 * url - /deleteStationFromFavori/:id
 * method - DELETE
 */
$app->delete('/deleteStationFromFavori/:id', 'authenticate', function($idStation){

    
    global $user_id;
    
    $response = array();
    $response['error'] = false;
    $db = new DbHandler();
    $result = $db->deleteStationFromFavori($idStation, $user_id);
    if ($result) {
                    $response["error"] = false;
                    $response["message"] = "A Station is deleted from his favori";
                    echoRespnse(201, $response);

                } else {
                    // unknown error occurred
                    $response['error'] = true;
                    $response['message'] = "An error occurred. Please try again";
                    echoRespnse(200, $response);
                }
});

/**
 * delete a favorite marque
 * url - /deleteMarqueFromFavori/:id
 * method - DELETE
 */
$app->delete('/deleteMarqueFromFavori/:id', 'authenticate', function($idMarque){

    
    global $user_id;
    
    $response = array();
    $response['error'] = false;
    $db = new DbHandler();
    $result = $db->deleteMarqueFromFavori($idMarque, $user_id);
    if ($result) {
                    $response["error"] = false;
                    $response["message"] = "A Marque is deleted from his favori";
                    echoRespnse(201, $response);

                } else {
                    // unknown error occurred
                    $response['error'] = true;
                    $response['message'] = "An error occurred. Please try again";
                    echoRespnse(200, $response);
                }
});


$app->run();

