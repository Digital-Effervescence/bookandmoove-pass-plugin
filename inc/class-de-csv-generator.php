<?php

class DE_CSV_Generator
{
	private $queryString;
	private $queryParameters;
	
	private $batchSize = 1000;
	
	
	public function __construct($queryString = null, $queryParameters = array())
	{
		$this->queryString = $queryString;
		$this->queryParameters = $queryParameters;
	}
	
	
	public function generateCSV($filePath)
	{
		global $wpdb;
		
		if ($this->queryString !== null) {
			// Définition des titres du CSV
			$csvTitle = array(__("Code", "debampass"), __("Expiration date", "debampass"), __("Membership Plan", "debampass"), __("User Email", "debampass"), __("User display name", "debampass"), __("Activation date", "debampass"), __("Creation date", "debampass"));
			
			//Création du CSV sur le serveur
			$handle = fopen($filePath, 'w');
			fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // Pour un bon encodage
			fputcsv($handle, $csvTitle, ';'); // Titre
							
			$finished = false;
			$currentOffset = 0;
			
			// Tant que l'on a des résultats
			while (!$finished) {
				$parameters = $this->queryParameters;
				
				$query = $this->queryString;
				$query .= " LIMIT %d, %d";
				
				array_push($parameters, $currentOffset, $this->batchSize);
				
				$queryResults = $wpdb->get_results($wpdb->prepare($query, $parameters));
				
				if (count($queryResults) > 0) { // On a des résultats
					foreach ($queryResults as $aResult) {
						fputcsv($handle, array('"'. $aResult->code .'"', $aResult->date_end_code_active, $aResult->post_title, $aResult->user_email, $aResult->display_name, $aResult->updated_at, $aResult->created_at), ';');
					}
					
					$currentOffset += $this->batchSize;
				} else {
					$finished = true;
				}
			}
			
			fclose($handle);
			
			return true;
		} else {
			return false;
		}
	}
	
	
	public function setQueryString($queryString)
	{
		$this->queryString = $queryString;
	}
	public function getQueryString()
	{
		return $this->queryString;
	}
	
	public function setQueryParameters($queryParameters)
	{
		$this->queryParameters = $queryParameters;
	}
	public function getQueryParameters()
	{
		return $this->queryParameters;
	}
	
	public function setBatchSize($size)
	{
		$this->batchSize = $size;
	}
}
