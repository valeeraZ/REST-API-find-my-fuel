<?php

/**
 * Encoding : UTF-8
 * Created on  : 2019-3-08 11:25:34 by Tristan GOBBI , tristan.gobbi@u-psud.fr
 */
class DbHandler {
 
    private $conn;
 
    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }
    /**
     * Fetching user api key
     * @param String $user_id user id primary key in user table
     */
    public function getApiKeyById($user_id) {
        $stmt = $this->conn->prepare("SELECT api_key FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $api_key = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $api_key;
        } else {
            return NULL;
        }
    }
 
    /**
     * Fetching user id by api key
     * @param String $api_key user api key
     */
    public function getUserId($api_key) {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        if ($stmt->execute()) {
            $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $user_id;
        } else {
            return NULL;
        }
    }
 
    /**
     * Validating user api key
     * If the api key is there in db, it is a valid key
     * @param String $api_key user api key
     * @return boolean
     */
    public function isValidApiKey($api_key) {
        $stmt = $this->conn->prepare("SELECT id from users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
    /**
     * Add a station to favorite
     * @param String $idStation
     */
    public function addFavoriteStation($idStation, $idUser){
        //$idUser = $this->getUserId($api_key);
          
        $stmt = $this->conn->prepare("INSERT INTO stationFavori(idUser, idStation) values(?, ?)");
        $stmt->bind_param("ii", $idUser, $idStation);
 
        $result = $stmt->execute();
        $stmt->close();
        return $result;                     
    }
    
    public function addFavoriteCarburant($idCarburant, $idUser){
        //$idUser = $this->getUserId($api_key);
          
        $stmt = $this->conn->prepare("INSERT INTO carburantFavori(idUser, idCarburant) values(?, ?)");
        $stmt->bind_param("ii", $idUser, $idCarburant);
 
        $result = $stmt->execute();
        $stmt->close();
        return $result;                   
    }
    
    public function addFavoriteMarque($idMarque, $idUser){
        //$idUser = $this->getUserId($api_key);
          
        $stmt = $this->conn->prepare("INSERT INTO marqueFavori(idUser, idMarque) values(?, ?)");
        $stmt->bind_param("ii", $idUser, $idMarque);
 
        $result = $stmt->execute();
        $stmt->close();
        return $result;                      
    }
    
}

