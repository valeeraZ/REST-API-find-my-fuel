<?php

/* 
 * Encoding : UTF-8
 * Created on  : 2019-3-6 11:20:26 by Cédric LEGRAND , cedric.legrand@u-psud.fr
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


    /* ------------- `` table method ------------------ */
 
    /**
     * delete favori
     * LEGRAND Cédric
     */


    public function deleteStationFromFavori ($idStation, $idUser) {
        $smtp = $this->conn->prepare("DELETE FROM stationFavori WHERE idStation= ? AND idUser= ?");
        $smtp->bind_param("ii",$idStation, $idUser);
        $result = $smtp->execute();
        $smtp->close();
        return $result;
        
    }
    
    public function deleteMarqueFromFavori ($idMarque, $idUser) {
        $smtp = $this->conn->prepare("DELETE FROM marqueFavori WHERE idMarque= ? AND idUser= ?");
        $smtp->bind_param("ii",$idMarque, $idUser);
        $result = $smtp->execute();
        $smtp->close();
        return $result;
        
    }

    
    public function deleteCarburantFromFavori ($idCarburant, $idUser) {
        $smtp = $this->conn->prepare("DELETE FROM carburantFavori WHERE idCarburant= ? AND idUser= ?");
        $smtp->bind_param("ii",$idCarburant, $idUser);
        $result = $smtp->execute();
        $smtp->close();
        return $result;
        
    }
    
}