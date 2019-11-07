<?php

/* 
 * Encoding : UTF-8
 * Created on  : 2019-4-09 17:04:54 by Wenzhuo ZHAO , wenzhuo.zhao@u-psud.fr
 */
class DbHandler {
 
    private $conn;
 
    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }
 
    public function modifyPrix($idStation,$idCarburant, $prix){
        $stmt = $this->conn->prepare("UPDATE stock SET prix = ? WHERE idStation = ? AND idCarburant = ? ");
        $stmt->bind_param("dii",$prix,$idStation,$idCarburant);
        $result = $stmt->execute();
        $stmt->close();
        if($result){
            return 0;
        }else{
            return 1;
        }
    }
    
    /*-----Parite abandonnée-----*/
    /**
     * 
     * @param type $idStation
     * @param type $commentaire
     * @param type $photo
     * @return int
     */
    public function update($idStation,$commentaire,$photo) {
        $stmt = $this->conn->prepare("UPDATE station SET commentaire = ? ,photo = ? WHERE idStation = ?");
        $stmt->bind_param("ssi", $commentaire, $photo, $idStation);
        if ($stmt->execute()) {
            return 1;
        } else {
            return 0;
        }
    }
    
    public function updateCommentaire($idStation,$commentaire) {
        $stmt = $this->conn->prepare("UPDATE station SET commentaire = ?  WHERE idStation = ?");
        $stmt->bind_param("si", $commentaire, $idStation);
        if ($stmt->execute()) {
            return 1;
        } else {
            return 0;
        }
    }
    
    public function updatePhoto($idStation,$photo) {
        $stmt = $this->conn->prepare("UPDATE station SET photo = ? WHERE idStation = ?");
        $stmt->bind_param("si",  $photo, $idStation);
        if ($stmt->execute()) {
            return 1;
        } else {
            return 0;
        }
    }
    
    
 
}


