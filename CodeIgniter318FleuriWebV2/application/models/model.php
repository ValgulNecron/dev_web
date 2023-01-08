<?php if(!defined('BASEPATH'))exit('No direct scrip access allowed');
class Model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}
	public function getBulbes()
	{
		$search = "SELECT * FROM produit WHERE produit.catCode='Bul'";
		$result = $this->db->conn_id->prepare($search);
		$result->execute();
		return $query_result = $result->fetchAll(PDO::FETCH_ASSOC);
	}
	public function getMassifs()
	{
		$search = "SELECT * FROM produit WHERE produit.catCode='PaM'";
		$result = $this->db->conn_id->prepare($search);
		$result->execute();
		return $query_result = $result->fetchAll(PDO::FETCH_ASSOC);
	}
	public function getRosiers()
	{
		$search = "SELECT * FROM produit WHERE produit.catCode='Ros'";
		$result = $this->db->conn_id->prepare($search);
		$result->execute();
		return $query_result = $result->fetchAll(PDO::FETCH_ASSOC);
	}
}
?>