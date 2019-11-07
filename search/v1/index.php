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
 * search for all carburant
 * url - /getCarburant
 * method - GET
 * 
 */
$app->get('/getCarburant', function() {
    $response = array();
    $response['error'] = false;
    $response['carburants'] = array();
    $db = new DbHandler();
    $result = $db->getCarburant();

    while ($carburant = $result->fetch_assoc()) {
        $tmp = array();
        $tmp['idCarburant'] = $carburant['idCarburant'];
        $tmp['nomCarburant'] = $carburant['nomCarburant'];
        array_push($response["carburants"], $tmp);
    }
    echoRespnse(200, $response);
});

/**
 * search for all marque
 * url - /getMarque
 * method - GET
 * 
 */
$app->get('/getMarque', function() {
    $response = array();
    $response['error'] = false;
    $response['marques'] = array();
    $db = new DbHandler();
    $result = $db->getMarque();

    while ($marque = $result->fetch_assoc()) {
        $tmp = array();
        $tmp['idMarque'] = $marque['idMarque'];
        $tmp['nomMarque'] = $marque['nomMarque'];
        $tmp['logo'] = $marque['logoMarque'];
        array_push($response["marques"], $tmp);
    }
    echoRespnse(200, $response);
});

/*
 * search for one carburant by his id
 * url - /getCarburant/:id
 * method - GET
 * path param : idCarburant
 */
$app->get('/getCarburant/:id',function($idCarburant) {
    $response = array();
    $db = new DbHandler();
    $result = $db->getCarburantById($idCarburant);
    
    if($result != NULL){
        $response['error'] = false;
        $response['idCarburant'] = $result['idCarburant'];
        $response['nomCarburant'] = $result['nomCarburant'];
        echoRespnse(200, $response);
    }else{
        $response['error'] = true;
        $response['message'] = "The requested resource doesn't exist.";
        echoRespnse(404, $response);
    }
    
});

/*
 * search for some stations by the distance and/or by the stock of carburant
 * url - /getStation
 * method - GET
 * query param - longitude, latitude, rayon(optionnel), idCarburant(optionnel)
 */
$app->get('/getStation',function() use ($app) {
    verifyRequiredParams(array("longitude","latitude"));
    $longitude = $app->request()->get("longitude");
    $latitude = $app->request()->get("latitude");
    $rayon = $app->request()->get("rayon");
    if($rayon == null){
        $rayon = 20;
    }
    $idCarburant = $app->request()->get("idCarburant");
    $db = new DbHandler();
    $result = $db->getStationByDistance($longitude, $latitude, $rayon);
    
    
    $response = array();
    $response['error'] = false;
    $response['numberStation'] = null;
    $response['stations'] = array();
    
    $numberStation = 0;
    while ($station = $result->fetch_assoc()) {
        
        $tmp = array();
        $tmp['distance'] = $db->getDistance($longitude, $latitude, $station['longitude'], $station['latitude']);
        $tmp['stocks'] = $db->getStockByidStationAndidCarburant($station['idStation'], $idCarburant);
        if($tmp['stocks'] == null){
            continue;
        }
        $tmp['idStation'] = $station['idStation'];
        //$tmp['nomStation'] = $station['nomStation'];
        $tmp['commune'] = $station['commune'];
        $tmp['codePostal'] = $station['codePostal'];
        $tmp['latitude'] = $station['latitude'];
        $tmp['longitude'] = $station['longitude'];
        $tmp['adresse'] = $station['adresse'];
        $tmp['heureDebut'] = $station['heureDebut'];
        $tmp['heureFin'] = $station['heureFin'];
        $tmp['statut'] = $station['statut'];
        $tmp['typeRoute'] = $station['typeRoute'];
        $tmp['service'] = $station['service'];
        $tmp['suafJour'] = $station['saufJour'];
        $tmp['marque'] = $db->getMarqueByid($station['idMarque']);
       
        $numberStation++;
        array_push($response["stations"], $tmp);
    }
    
    $response['numberStation'] = $numberStation;
    sort($response['stations']);
    
    if($response['numberStation']>0){
        echoRespnse(200, $response);
    }else{
        
        $response['error'] = true;
        $response['message'] = "The requested resource doesn't exist.";
        echoRespnse(404, $response);
    }
    
    
    
});

/*
 * search for one station by his id
 * url - /getStation/:id
 * method - GET
 * path params : idStation
 * query params : longitude, latitude
 */
$app->get("/getStation/:id",function($idStation) use ($app){
    verifyRequiredParams(array("longitude","latitude"));
    $longitude = $app->request()->get("longitude");
    $latitude = $app->request()->get("latitude");
    $response = array();
    $db = new DbHandler();
    $result = $db->getStationByidStation($idStation,$longitude,$latitude);
    if($result != null){
        $response['distance'] = $result['distance'];
        $response['idStation'] = $result['idStation'];
        $response['longitude'] = $result['longitude'];
        $response['latitude'] = $result['latitude'];
        $response['commune'] = $result['commune'];
        $response['codePostal'] = $result['codePostal'];
        //$response['nomStation'] = $result['nomStation'];
        $response['adresse'] = $result['adresse'];
        $response['heureDebut'] = $result['heureDebut'];
        $response['heureFin'] = $result['heureFin'];
        $response['statut'] = $result['statut'];
        $response['stock'] = array();
        $response['stock'] = $db->getStockByidStationAndidCarburant($idStation,null);
        $response['typeRoute'] = $result['typeRoute'];
        $response['service'] = $result['service'];
        $response['saufJour'] = $result['saufJour'];
        $response['marque'] = $db->getMarqueByid($result['idMarque']);
        
        echoRespnse(200, $response);
    }else{
        $response['error'] = true;
        $response['message'] = "The requested resources doesn't exist.";
        echoRespnse(404, $response);
    }
    
});

        
/**
 * Demo connect server
 * url - /test
 * method - GET
 * 
 */
$app->get('/test',function() use ($app) {
    $data = $app->request()->get('data');
    $response = array();
    $response['error'] = false;
    $response['message'] = $data;
    echoRespnse(200, $response);
    });


$app->run();

