<?php

/* 
 * Encoding : UTF-8
 * Created on  : Mar 8, 2019 11:28:36 AM by Wenzhuo ZHAO , wenzhuo.zhao@u-psud.fr
 */
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

$app->get('/getStationFavori','authenticate',function() use ($app) {
    verifyRequiredParams(array("longitude","latitude"));
    $longitude = $app->request()->get("longitude");
    $latitude = $app->request()->get("latitude");
    global $user_id;
    $numberStation = 0;
    $response = array();
    $response['error'] = false;
    $response['stations'] = array();
    
    $db = new DbHandler();
    $result = $db->getStationFavori($user_id);
    
    while($station = $result->fetch_assoc()){
        $numberStation++;
        $tmp['distance'] = $db->getDistance($longitude, $latitude, $station['longitude'], $station['latitude']);
        $tmp['idStation'] = $station['idStation'];
        $tmp['stocks'] = $db->getStockByidStationAndidCarburant($station['idStation'], null);
        $tmp['commune'] = $station['commune'];
        $tmp['codePostal'] = $station['codePostal'];
        $tmp['latitude'] = $station['latitude'];
        $tmp['longitude'] = $station['longitude'];
        $tmp['adresse'] = $station['adresse'];
        $tmp['heureDebut'] = $station['heureDebut'];
        $tmp['heureFin'] = $station['heureFin'];
        $tmp['statut'] = $station['statut'];
        array_push($response['stations'],$tmp);
    }
    sort($response['stations']);
    
    if($numberStation == 0){
        $response['error'] = true;
        $response['message'] = "The requested ressource doesn't exist.";
        echoRespnse(404, $response);
    }else{
        $response['numberStation'] = $numberStation;
        echoRespnse(200, $response);
    }
});

$app->get('/getCarburantFavori','authenticate',function(){
   global $user_id;
    $numberCarburant = 0;
    $response = array();
    $response['error'] = false;
    $response['carburants'] = array();
    
    $db = new DbHandler();
    $result = $db->getCarburantFavori($user_id);
    
    while($carburant = $result->fetch_assoc()){
        $numberCarburant++;
        $tmp['nomCarburant'] = $carburant['nomCarburant'];
        array_push($response['carburants'],$tmp);
    }
    
    if($numberCarburant == 0){
        $response['error'] = true;
        $response['message'] = "The requested ressource doesn't exist.";
        echoRespnse(404, $response);
    }else{
        $response['numberCarburant'] = $numberCarburant;
        echoRespnse(200, $response);
    }
});
$app->get('/getMarqueFavori','authenticate',function(){
    global $user_id;
    $numberMarque = 0;
    $response = array();
    $response['error'] = false;
    $response['marques'] = array();
    
    $db = new DbHandler();
    $result = $db->getMarqueFavori($user_id);
    
    while($marque = $result->fetch_assoc()){
        $numberMarque++;
        $tmp['nomMarque'] = $marque['nomMarque'];
        array_push($response['marques'],$tmp);
    }
    
    if($numberMarque == 0){
        $response['error'] = true;
        $response['message'] = "The requested ressource doesn't exist.";
        echoRespnse(404, $response);
    }else{
        $response['numberMarque'] = $numberMarque;
        echoRespnse(200, $response);
    }
});

$app->get('/determineStationFavori/:id','authenticate',function($idStation){
    global $user_id;
    $db = new DbHandler();
    $result = $db->determineStationFavori($user_id, $idStation);
    if($result){
        $response['message'] = "The station is in the favori";
        echoRespnse(200, $response);
    }else{
        $response['message'] = "The station is not in the favori";
        echoRespnse(404, $response);
    }
});

$app->run();