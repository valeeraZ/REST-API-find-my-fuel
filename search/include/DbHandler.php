<?php

/* 
 * Encoding : UTF-8
 * Created on  : 2019-2-22 15:04:54 by Wenzhuo ZHAO , wenzhuo.zhao@u-psud.fr
 */
class DbHandler {
 
    private $conn;
 
    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }
 
    /* ------------- `Carburant` table method ------------------ */
 
    /**
     * get all carburants
     * @return array
     * ZHAO Wenzhuo
     */
    public function getCarburant() {
        
        $response = array();
        $stmt = $this->conn->prepare("SELECT * FROM carburant");
        $stmt->execute();
        $response = $stmt->get_result();
        $stmt->close();
        return $response;
    }
 
    /**
     * get a station by the parameters
     * @param type $idCarburant
     * @return array
     */
    public function getCarburantById($idCarburant){
        $response = array();
        $stmt = $this->conn->prepare("SELECT * FROM carburant WHERE idCarburant = ?");
        $stmt->bind_param("i",$idCarburant);
        $stmt->execute();
        $stmt->bind_result($idCarburant,$nomCarburant);
        $stmt->fetch();
        if($nomCarburant != NULL){
            $response['idCarburant'] = $idCarburant;
            $response['nomCarburant'] = $nomCarburant;
            $stmt->close();
            return $response;
        }
        else{
            return null;
        }
    }
    
    
    
    /**
     * 
     * @param type $longitude : longitude actuel d'utilisateur
     * @param type $latitude : latitude actuel d'utilisateur
     * @param type $rayon : un chiffre de kilometres, valeur = 10 par default
     * @param type $idCarburant :  idCarburant
     */
    public function getStationByDistance($longitude,$latitude,$rayon){
        $response = array();
        $stmt = $this->conn->prepare("select * from station
                    where sqrt( ( ((?-longitude)*PI()*6328*cos(((?+latitude)/2)*PI()/180)/180) * ((?-longitude)*PI()*6328*cos (((?+latitude)/2)*PI()/180)/180) ) + ( ((?-latitude)*PI()*6328/180) * ((?-latitude)*PI()*6328/180) ) )   <? ");
        $stmt->bind_param("ddddddi",$longitude,$latitude,$longitude,$latitude,$latitude,$latitude,$rayon);
        $stmt->execute();
        $response = $stmt->get_result();
        //$stmt->debugDumpParams();
        
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
        $stmt->bind_result($idStation,$codePostal,$commune,$region,$adresse,$statut,$longitude2,$latitude2,$heureDebut,$heureFin,$commentaire,$photo,$typeRoute,$service,$saufJour,$idMarque);
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
        $response['typeRoute'] = $typeRoute;
        $response['service'] = $service;
        $response['saufJour'] = $saufJour;
        $response['idMarque'] = $idMarque;
        $stmt->close();
        return $response;
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
     * get all marques
     * @return array
     */
    public function getMarque(){
        $response = array();
        $stmt = $this->conn->prepare("select * from marque");
        $stmt->execute();
        $response = $stmt->get_result();
        $stmt->close();
        return $response;
    }
    
    /**
     * get marque by id
     * @return array
     */
    public function getMarqueByid($idMarque){
        $response = array();
        $stmt = $this->conn->prepare("select * from marque where idMarque = ?");
        $stmt->bind_param("i",$idMarque);
        $stmt->execute();
        $stmt->bind_result($idMarque,$nomMarque,$logoMarque);
        $stmt->fetch();
        if($nomMarque != NULL){
            $response['idMarque'] = $idMarque;
            $response['nomMarque'] = $nomMarque;
            $response['logoMarque'] = $logoMarque;
            $stmt->close();
            return $response;
        }
        else{
            return null;
        }
    }
}


