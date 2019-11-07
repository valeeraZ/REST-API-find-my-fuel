<?php
require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
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
        $response['errorType'] = 1;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}
 
/**
 * Validating email address
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['user'] = array();
        $tmp["error"] = true;
        $tmp['errorType'] = 2;
        $tmp["message"] = 'Email address is not valid';
        array_push($response['user'],$tmp);
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
 * User Registration
 * url - /register
 * method - POST
 * params - name, email, password
 */
$app->post('/register', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('name', 'email', 'password'));
 
            $response = array();
 
            // reading post params
            $name = $app->request->post('name');
            $email = $app->request->post('email');
            $password = $app->request->post('password');
 
            // validating email address
            validateEmail($email);
 
            $db = new DbHandler();
            $res = $db->createUser($name, $email, $password);
            $response['user'] = array();
 
            if ($res['message'] == USER_CREATED_SUCCESSFULLY) {
                $tmp["error"] = false;
                $tmp['errorType'] = 0;
                $tmp["message"] = "You are successfully registered";
                $tmp['name'] = $res['name'];
                $tmp['email'] = $res['email'];
                $tmp['api_key'] = $res['api_key'];
                array_push($response['user'],$tmp);
                echoRespnse(201, $response);
            } else if ($res['message'] == USER_CREATE_FAILED) {
                $tmp["error"] = true;
                $tmp['errorType'] = 4;
                $tmp["message"] = "Oops! An error occurred while registereing";
                array_push($response['user'],$tmp);
                echoRespnse(200, $response);
            } else if ($res['message'] == USER_ALREADY_EXISTED) {
                $tmp["error"] = true;
                $tmp['errorType'] = 3;
                $tmp["message"] = "Sorry, this email already existed";
                array_push($response['user'],$tmp);
                echoRespnse(200, $response);
            }
        });
        
/**
 * Demo connect server
 * url - /coucou
 * method - GET
 * 
 */
$app->get('/',function() use ($app){
    $response = array();
    $response['error'] = false;
    $response['message'] = 'Welcome to REST API';
    echoRespnse(200, $response);
    });

/**
 * User Login
 * url - /login
 * method - POST
 * params - email, password
 */
$app->post('/login', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('email', 'password'));
 
            // reading post params
            $email = $app->request()->post('email');
            $password = $app->request()->post('password');
            $response = array();
            
            $response['user'] = array();
 
            $db = new DbHandler();
            // check for correct email and password
            if ($db->checkLogin($email, $password)) {
                // get the user by email
                $user = $db->getUserByEmail($email);
 
                if ($user != NULL) {
                    $tmp = array();
                    $tmp["error"] = false;
                    $tmp["id"] = $user['id'];
                    $tmp['name'] = $user['name'];
                    $tmp['email'] = $user['email'];
                    $tmp['apiKey'] = $user['api_key'];
                    $tmp['createdAt'] = $user['created_at'];
                    array_push($response['user'],$tmp);
                } else {
                    // unknown error occurred
                    $tmp['error'] = true;
                    $tmp['message'] = "An error occurred. Please try again";
                    array_push($response['user'],$tmp);
                }
            } else {
                // user credentials are wrong
                $tmp['error'] = true;
                $tmp['message'] = 'Login failed. Incorrect credentials';
                array_push($response['user'],$tmp);
            }
 
            echoRespnse(200, $response);
        });
/**
 * modify information(name,email and password of user)
 * url - /modifyInfo
 * method - put
 * param - old_email, new_password, new_name, new_email
 */       
$app->put('/modifyInfo', 'authenticate',function() use ($app){
    global $user_id;
    verifyRequiredParams(array('new_name','new_email','old_email'));
    $old_email = $app->request()->post('old_email');
    $new_password = $app->request()->post('new_password');
    $new_name = $app->request()->post('new_name');
    $new_email = $app->request()->post('new_email');
    // validating email address
    validateEmail($new_email);//error type 2
    $response = array(); 
    $response['user'] = array();
    $db = new DbHandler();
    $result = $db->modifyInfo($user_id,$new_name,$old_email,$new_email,$new_password);//boolean
    if($result == 0){
        $tmp['error'] = false;
        $tmp['errorType'] = 0;
        $tmp['message'] = "You have successfully modified your personnal information";
        array_push($response['user'], $tmp);
        echoRespnse(201, $response);
    }
    if($result == 1){
        $tmp['error'] = true;
        $tmp['errorType'] = 1;
        $tmp['message'] = "Sorry, an error occured.";
        array_push($response['user'], $tmp);
        echoRespnse(400, $response);
    }
    if($result == 3){
        $tmp['error'] = true;
        $tmp['errorType'] = 3;
        $tmp['message'] = "Sorry, this email exists already.";
        array_push($response['user'], $tmp);
        echoRespnse(200, $response);
    }
});




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
$app->run();

