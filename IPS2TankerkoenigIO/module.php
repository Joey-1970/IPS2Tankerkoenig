<?
    // Klassendefinition
    class IPS2TankerkoenigIO extends IPSModule 
    {
	
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->RegisterPropertyBoolean("Open", false);
 	    	$this->RegisterPropertyString("ApiKey", "");
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
				
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "ApiKey", "caption" => "Tankerkönig API-Key");	
		$arrayActions = array();
		$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}       
	   
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
		// Profil anlegen
		
		
		
		If (IPS_GetKernelRunlevel() == 10103) {	
		
			If ($this->ReadPropertyBoolean("Open") == true) {
				$this->SetStatus(102);
			}
			else {
				$this->SetStatus(104);
			}
		}
	}
	
	public function ForwardData($JSONString) 
	 {
	 	// Empfangene Daten von der Device Instanz
	    	$data = json_decode($JSONString);
	    	$Result = -999;
	 	switch ($data->Function) {
			case "GetAreaInformation":
				$ApiKey = $this->ReadPropertyString("ApiKey");
				$Lat = floatval($data->Lat);
				$Long = floatval($data->Long);
				$Radius = floatval($data->Radius);
				$this->SendDebug("GetAreaInformation", $Lat.", ".$Long.", ".$Radius, 0);
	 			$Result = file_get_contents ("https://creativecommons.tankerkoenig.de/json/list.php?lat=".$Lat."&lng=".$Long."&rad=".$Radius."&sort=dist&type=all&apikey=".$ApiKey);
				break;
			case "GetDetailInformation":
				$ApiKey = $this->ReadPropertyString("ApiKey");
				$StationID = $data->StationID;
				$this->SendDebug("GetDetailInformation", $StationID, 0);
				$Result = file_get_contents ("https://creativecommons.tankerkoenig.de/json/detail.php?id=".$StationID."&apikey=".$ApiKey);
				$this->SendDebug("GetDetailInformation", $Result, 0);
				break;
		}
	return $Result;
	}
	    
	// Beginn der Funktionen
	
	
	
}
?>
