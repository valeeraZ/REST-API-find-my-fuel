<?php

/* 
 * Encoding : UTF-8
 * Created on  : Mar 8, 2019 11:24:27 AM by Wenzhuo ZHAO , wenzhuo.zhao@u-psud.fr
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
     * afficher toutes les stations favorites d'un utilisateur
     * @param type $id_user
     * @return type array
     */
    public function getStationFavori($user_id){
        $response = array();
        $stmt = $stmt = $this->conn->prepare("SELECT * FROM stationFavori sf, station s WHERE sf.idUser = ? AND sf.idStation = s.idStation ");
        $stmt->bind_param("i",$user_id);
        $stmt->execute();
        $response = $stmt->get_result();
        
        $stmt->close();
        return $response;
    }
    
    /**
     * calculation for distance from A to B
     * @param type $longitude1 : for point A
     * @param type $latitude1 : for point A
     * @param type $longitude2 : for point B
     * @param type $latitude2 : for point B
     * @param type $unit : 1 for meter, 2 for km
     * @param type $decimal : numbers after the point
     * @return type round($distance,$decimal) : float
     */
    public function getDistance($longitude1, $latitude1, $longitude2, $latitude2, $unit=2, $decimal=2){
        $EARTH_RADIUS = 6370.996; 
        $PI = 3.1415926;

        $radLat1 = $latitude1 * $PI / 180.0;
        $radLat2 = $latitude2 * $PI / 180.0;

        $radLng1 = $longitude1 * $PI / 180.0;
        $radLng2 = $longitude2 * $PI /180.0;

        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;

        $distance = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1) * cos($radLat2) * pow(sin($b/2),2)));
        $distance = $distance * $EARTH_RADIUS * 1000;

        if($unit==2){
            $distance = $distance / 1000;
        }

        return round($distance, $decimal);

    }
    /**
     * get every stock(if no specified $idCarburant is given) of one station
     * or get one stock(if $idCarburant is given) of one station
     * @param type $idStation
     * @return array
     */
    public function getStockByidStationAndidCarburant($idStation,$idCarburant){
        $response = array();
        
        if($idCarburant == null){
            //idCarburant = 3,5,6 si le client n'pas demande d'un carburant precis
            $idCarburants = array("3","5","6");
            foreach($idCarburants as $idCarburant){
                $stmt = $this->conn->prepare("select s.idCarburant, nomCarburant, prix from stock s, carburant c
                where idStation = ? and s.idCarburant = ? and s.idCarburant = c.idCarburant");
                $stmt->bind_param("ii",$idStation,$idCarburant);
                $stmt->execute();
                $stocks = $stmt->get_result();
                while($stock = $stocks->fetch_assoc() ){
                    $tmp['idCarburant'] = $stock['idCarburant'];
                    $tmp['nomCarburant'] = $stock['nomCarburant'];
                    $tmp['prix'] = $stock['prix'];
                    array_push($response,$tmp);
                }
            }
            
        }else{
            $stmt = $this->conn->prepare("select s.idCarburant, nomCarburant, prix from stock s,carburant c where idStation = ? and s.idCarburant = ? and s.idCarburant = c.idCarburant");
           
            $stmt->bind_param("ii",$idStation,$idCarburant);
            $stmt->execute();
            $stocks = $stmt->get_result();
            while($stock = $stocks->fetch_assoc() ){
                $tmp = array();
                $tmp['idCarburant'] = $stock['idCarburant'];
                $tmp['nomCarburant'] = $stock['nomCarburant'];
                $tmp['prix'] = $stock['prix'];
                array_push($response,$tmp);
            } 
        }
        
        return $response;
    }
    
    /**
     * search for a station by his id and calcul the distance
     * @param type $idStation
     * @param type $longitude : d'utilisateur
     * @param type $latitude : d'utilisateur
     * @return $response : array
     */
    public function getStationByidStation($idStation,$longitude,$latitude){
        $response = array();
        $stmt = $this->conn->prepare("SELECT * FROM station WHERE idStation = ?");
        $stmt->bind_param("i",$idStation);
        $stmt->execute();
        $stmt->bind_result($idStation,$codePostal,$commune,$region,$adresse,$statut,$latitude2,$longitude2,$heureDebut,$heureFin,$commentaire,$photo,$typeRoute,$service,$saufJour,$idMarque);
        $stmt->fetch();
        $response['distance'] = $this->getDistance($longitude, $latitude, $longitude2, $latitude2);
        $response['idStation'] = $idStation;
        $response['commune'] =$commune;
        $response['codePostal']=$codePostal;
        $response['adresse'] = $adresse;
        $response['heureDebut'] = $heureDebut;
        $response['heureFin'] = $heureFin;
        $response['statut'] = $statut;
        $response['longitude'] = $longitude2;
        $response['latitude'] = $latitude2;
        $stmt->close();
        return $response;
    }
    
    /**
     * get the favorite carburant
     * @param type $user_id
     * @return type
     */
    public function getCarburantFavori($user_id){
    $response = array();
        $stmt = $stmt = $this->conn->prepare("SELECT * FROM carburantFavori cf, carburant c WHERE cf.idUser = ? AND cf.idCarburant = c.idCarburant");
        $stmt->bind_param("i",$user_id);
        $stmt->execute();
        $response = $stmt->get_result();
        
        $stmt->close();
        return $response;
    }
    /**
     * get the favorite marques
     * @param type $user_id
     * @return type
     */
    public function getMarqueFavori($user_id){
        $response=array();
        $stmt=$stmt=$this->conn->prepare("SELECT * FROM marqueFavori mf, marque m WHERE mf.idUser = ? AND mf.idMarque = m.idMarque");
        $stmt->bind_param("i",$user_id);
        $stmt->execute();
        $response = $stmt->get_result();

        $stmt->close();
        return $response;
    }
    
    public function determineStationFavori($user_id,$idStation){
        $stmt = $this->conn->prepare("SELECT * FROM stationFavori WHERE idStation = ? AND idUser = ?");
        $stmt->bind_param("ii",$idStation,$user_id);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    
    }
}

