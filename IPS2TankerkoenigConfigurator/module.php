<?
    // Klassendefinition
    class IPS2TankerkoenigConfigurator extends IPSModule 
    {
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->ConnectParent("{66FD608F-6C67-6011-25E3-B9ED4C3E1590}");
		$this->RegisterPropertyFloat("Lat", 0.0);
		$this->RegisterPropertyFloat("Long", 0.0);
		$this->RegisterPropertyFloat("Radius", 5.0);
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "Kommunikationfehler!");
				
		$arrayElements = array(); 
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Lat", "caption" => "Latitude", "digits" => 4);
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Long", "caption" => "Longitude", "digits" => 4);
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Radius", "caption" => "Radius", "digits" => 1);
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Button", "caption" => "Tankerkönig-API", "onClick" => "echo 'https://creativecommons.tankerkoenig.de/';");
		$arrayActions = array();
		$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}       
	   
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
		If (IPS_GetKernelRunlevel() == 10103) {	
			If ($this->HasActiveParent() == true) {
				$this->SetStatus(102);
			}
			else {
				$this->SetStatus(104);
			}
		}
	}
	    
	// Beginn der Funktionen
	public function GetDataUpdate()
	{
		$Lat = $this->ReadPropertyFloat("Lat");
		$Long = $this->ReadPropertyFloat("Long");
		$Radius = $this->ReadPropertyFloat("Radius");
		If (($Lat <> 0) AND ($Long <> 0) AND ($Radius > 0)) {
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{6ADD0473-D761-A2BF-63BE-CFE279089F5A}", 
				"Function" => "GetAreaInformation", "InstanceID" => $this->InstanceID, "Lat" => $Lat, "Long" => $Long, "Radius" => $Radius )));
			If ($Result <> false) {
				$this->SetStatus(102);
				$this->SendDebug("GetDataUpdate", $Result, 0);
				$this->ShowResult($Result);
			}
			else {
				$this->SetStatus(202);
				$this->SendDebug("GetDataUpdate", "Fehler bei der Datenermittlung!", 0);
			}
		}
		else {
			$this->SendDebug("GetDataUpdate", "Keine Koordinaten verfügbar!", 0);
		}
	}
	
	private function ShowResult(string $Text)
	{
		$ResultArray = array();
		$ResultArray = json_decode($Text);
		$ColorCode = "#00FF00";
		// Fehlerbehandlung
		If (boolval($ResultArray->ok) == false) {
			$this->SendDebug("ShowResult", "Fehler bei der Datenermittlung: ".utf8_encode($ResultArray->message), 0);
			return;
		}
		
		
		
	}
	

	protected function HasActiveParent()
    	{
		$Instance = @IPS_GetInstance($this->InstanceID);
		if ($Instance['ConnectionID'] > 0)
		{
			$Parent = IPS_GetInstance($Instance['ConnectionID']);
			if ($Parent['InstanceStatus'] == 102)
			return true;
		}
        return false;
    	}  
}
?>
