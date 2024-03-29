<?
    // Klassendefinition
    class IPS2TankerkoenigConfigurator extends IPSModule 
    {
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->RegisterMessage(0, IPS_KERNELSTARTED);
		$this->ConnectParent("{66FD608F-6C67-6011-25E3-B9ED4C3E1590}");
		$this->RegisterPropertyString("Location", '{"latitude":0,"longitude":0}');  
		$this->RegisterPropertyFloat("Radius", 5.0);
		$this->RegisterPropertyInteger("Category", 0);  
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "Kommunikationfehler!");
				
		$arrayElements = array(); 
		$arrayElements[] = array("type" => "SelectLocation", "name" => "Location", "caption" => "Region");
		$arrayElements[] = array("type" => "Label", "label" => "Radius (gemäß Tankerkönig.de Maximum 25 km)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Radius", "caption" => "Radius (km)", "digits" => 1);
		$arrayElements[] = array("type" => "SelectCategory", "name" => "Category", "caption" => "Zielkategorie");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arraySort = array();
		$arraySort = array("column" => "Brand", "direction" => "ascending");
		
		$arrayColumns = array();
		$arrayColumns[] = array("caption" => "Marke", "name" => "Brand", "width" => "100px", "visible" => true);
		$arrayColumns[] = array("caption" => "Name", "name" => "Name", "width" => "250px", "visible" => true);
		$arrayColumns[] = array("caption" => "Strasse", "name" => "Street", "width" => "200px", "visible" => true);
		$arrayColumns[] = array("caption" => "Ort", "name" => "Place", "width" => "auto", "visible" => true);
		
		$Category = $this->ReadPropertyInteger("Category");
		$RootNames = [];
		$RootId = $Category;
		while ($RootId != 0) {
		    	if ($RootId != 0) {
				$RootNames[] = IPS_GetName($RootId);
		    	}
		    	$RootId = IPS_GetParent($RootId);
			}
		$RootNames = array_reverse($RootNames);
		
		$StationArray = array();
		If ($this->HasActiveParent() == true) {
			$StationArray = unserialize($this->GetData());
		}
		$arrayValues = array();
		for ($i = 0; $i < Count($StationArray); $i++) {
			$arrayCreate = array();
			$arrayCreate[] = array("moduleID" => "{47286CAD-187A-6D88-89F0-BDA50CBF712F}", "location" => $RootNames, 
					       "configuration" => array("StationID" => $StationArray[$i]["StationsID"], "Timer_1" => 10));
			$arrayValues[] = array("Brand" => $StationArray[$i]["Brand"], "Name" => $StationArray[$i]["Name"], "Street" => $StationArray[$i]["Street"],
					       "Place" => $StationArray[$i]["Place"], "name" => $StationArray[$i]["Name"], "instanceID" => $StationArray[$i]["InstanceID"], 
					       "create" => $arrayCreate);
		}
		
		$arrayElements[] = array("type" => "Configurator", "name" => "PetrolStations", "caption" => "Tankstellen", "rowCount" => 10, "delete" => false, "sort" => $arraySort, "columns" => $arrayColumns, "values" => $arrayValues);

		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Button", "caption" => "Tankerkönig-API", "onClick" => "echo 'https://creativecommons.tankerkoenig.de/';");
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements)); 		 
 	}       
	   
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
		If (IPS_GetKernelRunlevel() == KR_READY) {	
			If ($this->HasActiveParent() == true) {
				If ($this->GetStatus() <> 102) {
					$this->SetStatus(102);
				}
			}
			else {
				If ($this->GetStatus() <> 104) {
					$this->SetStatus(104);
				}
			}
		}
	}
	
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    	{
		switch ($Message) {
			case IPS_KERNELSTARTED:
				// IPS_KERNELSTARTED
				If ($this->HasActiveParent() == true) {
					If ($this->GetStatus() <> 102) {
						$this->SetStatus(102);
					}
				}
				else {
					If ($this->GetStatus() <> 104) {
						$this->SetStatus(104);
					}
				}
				break;
		}
    	}         
	    
	// Beginn der Funktionen
	private function GetData()
	{
		$locationObject = json_decode($this->ReadPropertyString('Location'), true);
		$Lat = $locationObject['latitude'];
		$Long = $locationObject['longitude']; 
		$Radius = $this->ReadPropertyFloat("Radius");
		$Radius = min(25, max(0, $Radius));
		$StationArray = array();
		If (($Lat <> 0) AND ($Long <> 0) AND ($Radius > 0)) {
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{6ADD0473-D761-A2BF-63BE-CFE279089F5A}", 
				"Function" => "GetAreaInformation", "InstanceID" => $this->InstanceID, "Lat" => $Lat, "Long" => $Long, "Radius" => $Radius )));
			If ($Result <> false) {
				If ($this->GetStatus() <> 102) {
					$this->SetStatus(102);
				}
				$this->SendDebug("GetData", $Result, 0);
				//$this->ShowResult($Result);
				$ResultArray = array();
				$ResultArray = json_decode($Result);
				// Fehlerbehandlung
				If (boolval($ResultArray->ok) == false) {
					$this->SendDebug("ShowResult", "Fehler bei der Datenermittlung: ".utf8_encode($ResultArray->message), 0);
					return;
				}
				
				$i = 0;
				foreach($ResultArray->stations as $Stations) {
					/*
					$StationArray[$i]["Brand"] = ucwords(strtolower($Stations->brand));
					$StationArray[$i]["Name"] = ucwords(strtolower($Stations->name));
					$StationArray[$i]["Street"] = ucwords(strtolower($Stations->street));
					$StationArray[$i]["Place"] = ucwords(strtolower($Stations->place));
					*/
					
					$StationArray[$i]["Brand"] = $Stations->brand;
					$StationArray[$i]["Name"] = $Stations->name;
					$StationArray[$i]["Street"] = $Stations->street;
					$StationArray[$i]["Place"] = $Stations->place;
					$StationArray[$i]["StationsID"] = $Stations->id;
					$StationArray[$i]["InstanceID"] = $this->GetStationInstanceID($Stations->id);

					$i = $i + 1;
				}
				$this->SendDebug("GetData", "TankstellenArray: ".serialize($StationArray), 0);
				
			}
			else {
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
				$this->SendDebug("GetData", "Fehler bei der Datenermittlung!", 0);
			}
		}
		else {
			$this->SendDebug("GetDataUpdate", "Keine Koordinaten verfügbar!", 0);
		}
	return serialize($StationArray);
	}
	
	function GetStationInstanceID(string $StationID)
	{
		$guid = "{47286CAD-187A-6D88-89F0-BDA50CBF712F}";
	    	$Result = 0;
	    	// Modulinstanzen suchen
	    	$InstanceArray = array();
	    	$InstanceArray = @(IPS_GetInstanceListByModuleID($guid));
	    	If (is_array($InstanceArray)) {
			foreach($InstanceArray as $Module) {
				If (strtolower(IPS_GetProperty($Module, "StationID")) == strtolower($StationID)) {
					$this->SendDebug("GetStationInstanceID", "Gefundene Instanz: ".$Module, 0);
					$Result = $Module;
					break;
				}
				else {
					$Result = 0;
				}
			}
		}
	return $Result;
	}
}
?>
