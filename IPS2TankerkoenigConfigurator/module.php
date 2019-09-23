<?
    // Klassendefinition
    class IPS2TankerkoenigConfigurator extends IPSModule 
    {
	public function Destroy() 
	{
		//Never delete this line!
		parent::Destroy();
		$this->SetTimerInterval("Timer_1", 0);
	}
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->ConnectParent("{66FD608F-6C67-6011-25E3-B9ED4C3E1590}");
		$this->RegisterPropertyFloat("Lat", 0.0);
		$this->RegisterPropertyFloat("Long", 0.0);
		$this->RegisterPropertyFloat("Radius", 5.0);
		$this->RegisterPropertyInteger("Timer_1", 10);
		$this->RegisterTimer("Timer_1", 0, 'I2TListe_GetDataUpdate($_IPS["TARGET"]);');
		$this->RegisterPropertyBoolean("Diesel", true);
		$this->RegisterPropertyBoolean("E5", true);
		$this->RegisterPropertyBoolean("E10", true);
		$this->RegisterPropertyBoolean("ShowOnlyOpen", true);
		
		// Webhook einrichten
		$this->RegisterHook("/hook/IPS2TankerkoenigListe_".$this->InstanceID);
		
		// Profil anlegen
		$this->RegisterProfileFloat("IPS2Tankerkoenig.Euro", "Euro", "", " €", 0, 1000, 0.001, 3);
		// Status-Variablen anlegen
		$this->RegisterVariableString("PetrolStationList", "Tankstellen", "~HTMLBox", 10);
		
		$this->RegisterVariableInteger("LastUpdate", "Letztes Update", "~UnixTimestamp", 20);
		
		$this->RegisterVariableString("PetrolStationDetail", "Details", "~HTMLBox", 30);
				
		$this->RegisterVariableFloat("Diesel", "Diesel", "IPS2Tankerkoenig.Euro", 40);
		$this->RegisterVariableFloat("E5", "Super E5", "IPS2Tankerkoenig.Euro", 50);
		$this->RegisterVariableFloat("E10", "Super E10", "IPS2Tankerkoenig.Euro", 60);
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
		$arrayElements[] = array("type" => "Label", "label" => "Aktualisierung (gemäß Tankerkönig.de Minimum 10 Minuten)");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Timer_1", "caption" => "min");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Anzuzeigende Sorten");
		$arrayElements[] = array("type" => "CheckBox", "name" => "Diesel", "caption" => "Diesel"); 
		$arrayElements[] = array("type" => "CheckBox", "name" => "E5", "caption" => "Super E5");
		$arrayElements[] = array("type" => "CheckBox", "name" => "E10", "caption" => "Super E10");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "CheckBox", "name" => "ShowOnlyOpen", "caption" => "Nur geöffnete Tankstellen anzeigen"); 
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
		
		SetValueString($this->GetIDForIdent("PetrolStationDetail"), "");
		
		If (IPS_GetKernelRunlevel() == 10103) {	
			If ($this->HasActiveParent() == true) {
				$this->SetStatus(102);
				$this->SetTimerInterval("Timer_1", $this->ReadPropertyInteger("Timer_1") * 1000 * 60);
				If (($this->ReadPropertyFloat("Lat") <> 0) AND ($this->ReadPropertyFloat("Long") <> 0) AND ($this->ReadPropertyFloat("Radius") > 0)) {
					$this->GetDataUpdate();
				}
				else {
					$this->SendDebug("GetDataUpdate", "Keine Koordinaten verfügbar!", 0);
				}
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
		// Preise untersuchen
		$Diesel = 100;
		$E5 = 100;
		$E10 = 100;
		// Variablen für Mittelpreise
		$DieselArray = array();
		$E5Array = array();
		$E10Array = array();
		foreach($ResultArray->stations as $Stations) {
			If ($this->ReadPropertyBoolean("ShowOnlyOpen") == false) {
				If (($Diesel > floatval($Stations->diesel)) AND (floatval($Stations->diesel) > 0)) {
					$Diesel = floatval($Stations->diesel);
					$DieselArray[] = floatval($Stations->diesel);
				}
				If (($E5 > floatval($Stations->e5)) AND (floatval($Stations->e5) > 0)) {
					$E5 = floatval($Stations->e5);
					$E5Array[] = floatval($Stations->e5);
				}
				If (($E10 > floatval($Stations->e10)) AND (floatval($Stations->e10) > 0)) {
					$E10 = floatval($Stations->e10);
					$E10Array[] = floatval($Stations->e10);
				}
			}
			else {
				If ($Stations->isOpen == true) {
					If (($Diesel > floatval($Stations->diesel)) AND (floatval($Stations->diesel) > 0)) {
						$Diesel = floatval($Stations->diesel);
						$DieselArray[] = floatval($Stations->diesel);
					}
					If (($E5 > floatval($Stations->e5)) AND (floatval($Stations->e5) > 0)) {
						$E5 = floatval($Stations->e5);
						$E5Array[] = floatval($Stations->e5);
					}
					If (($E10 > floatval($Stations->e10)) AND (floatval($Stations->e10) > 0)) {
						$E10 = floatval($Stations->e10);
						$E10Array[] = floatval($Stations->e10);
					}
				}
			}
		}
		$this->SendDebug("ShowResult", "Diesel: ".$Diesel." E5: ".$E5." E10: ".$E10, 0);
		// Mittelpreis ermitteln
		$DieselAVG = array_sum($DieselArray) / count($DieselArray);
		$E5AVG = array_sum($E5Array) / count($E5Array);
		$E10AVG = array_sum($E10Array) / count($E10Array);
		If ($DieselAVG <> GetValueFloat($this->GetIDForIdent("Diesel"))) {
			SetValueFloat($this->GetIDForIdent("Diesel"), $DieselAVG);
		}
		If ($E5AVG <> GetValueFloat($this->GetIDForIdent("E5"))) {
			SetValueFloat($this->GetIDForIdent("E5"), $E5AVG);
		}
		If ($E10AVG <> GetValueFloat($this->GetIDForIdent("E10"))) {
			SetValueFloat($this->GetIDForIdent("E10"), $E10AVG);
		}
		// Tabelle aufbauen
		$table = '<style type="text/css">';
		$table .= '<link rel="stylesheet" href="./.../webfront.css">';
		$table .= "</style>";
		$table .= '<table class="tg">';
		$table .= "<tr>";
		$table .= '<th class="tg-kv4b">Marke</th>';
		$table .= '<th class="tg-kv4b">Name</th>';
		$table .= '<th class="tg-kv4b">Ort<br></th>';
		If ($this->ReadPropertyBoolean("Diesel") == true) { 
        		$table .= '<th class="tg-kv4b">Diesel<br></th>';
		}
		If ($this->ReadPropertyBoolean("E5") == true) { 
        		$table .= '<th class="tg-kv4b">Super E5<br></th>';
		}
		If ($this->ReadPropertyBoolean("E10") == true) { 
        		$table .= '<th class="tg-kv4b">Super E10<br></th>';
		}
        	If ($this->ReadPropertyBoolean("ShowOnlyOpen") == false) { 
			$table .= '<th class="tg-kv4b">Offen<br></th>';
		}
		$table .= '<th class="tg-kv4b">Details<br></th>';
		//$table .= '<th class="tg-kv4b">Ort<br></th>';
		$table .= '</tr>';
		foreach($ResultArray->stations as $Stations) {
			If ($this->ReadPropertyBoolean("ShowOnlyOpen") == false) {
				$table .= '<tr>';
				$table .= '<td class="tg-611x">'.ucwords(strtolower($Stations->brand)).'</td>';
				$table .= '<td class="tg-611x">'.ucwords(strtolower($Stations->name)).'</td>';
				$table .= '<td class="tg-611x">'.ucwords(strtolower($Stations->place)).'</td>';
				If ($this->ReadPropertyBoolean("Diesel") == true) {
					If (floatval($Stations->diesel) == $Diesel) {
						$table .= '<td class="tg-611x"> <font color='.$ColorCode.'>'.$Stations->diesel." €".'</font> </td>';
					}
					else {
						$table .= '<td class="tg-611x">'.$Stations->diesel." €".'</font> </td>';
					}
				}
				If ($this->ReadPropertyBoolean("E5") == true) {
					If (floatval($Stations->e5) == $E5) {
						$table .= '<td class="tg-611x"> <font color='.$ColorCode.'>'.$Stations->e5." €".'</font> </td>';
					}
					else {
						$table .= '<td class="tg-611x">'.$Stations->e5." €".'</font> </td>';
					}
				}
				If ($this->ReadPropertyBoolean("E10") == true) {
					If (floatval($Stations->e10) == $E10) {
						$table .= '<td class="tg-611x"> <font color='.$ColorCode.'>'.$Stations->e10." €".'</font> </td>';
					}
					else {
						$table .= '<td class="tg-611x">'.$Stations->e10." €".'</font> </td>';
					}
				}
				If ($Stations->isOpen == true) {
					$table .= '<td class="tg-611x">'."Ja".'</td>';
				} else {
					$table .= '<td class="tg-611x">'."Nein".'</td>';
				}
				$StationID = $Stations->id;
				$table .= '<td class="tg-611x"> <button type="button" alt="Details" onclick="window.xhrGet=function xhrGet(o) {var HTTP = new XMLHttpRequest();HTTP.open(\'GET\',o.url,true);HTTP.send();};window.xhrGet({ url: \'hook/IPS2TankerkoenigListe_'.$this->InstanceID.'?StationID='.$StationID.'\' })"id="ID">...</button> </td>';
				$table .= '</tr>';
			}
			else {
				If ($Stations->isOpen == true) {
					$table .= '<tr>';
					$table .= '<td class="tg-611x">'.ucwords(strtolower($Stations->brand)).'</td>';
					$table .= '<td class="tg-611x">'.ucwords(strtolower($Stations->name)).'</td>';
					$table .= '<td class="tg-611x">'.ucwords(strtolower($Stations->place)).'</td>';
					If ($this->ReadPropertyBoolean("Diesel") == true) {
						If (floatval($Stations->diesel) == $Diesel) {
							$table .= '<td class="tg-611x"> <font color='.$ColorCode.'>'.$Stations->diesel." €".'</font> </td>';
						}
						else {
							$table .= '<td class="tg-611x">'.$Stations->diesel." €".'</font> </td>';
						}
					}
					If ($this->ReadPropertyBoolean("E5") == true) {
						If (floatval($Stations->e5) == $E5) {
							$table .= '<td class="tg-611x"> <font color='.$ColorCode.'>'.$Stations->e5." €".'</font> </td>';
						}
						else {
							$table .= '<td class="tg-611x">'.$Stations->e5." €".'</font> </td>';
						}
					}
					If ($this->ReadPropertyBoolean("E10") == true) {
						If (floatval($Stations->e10) == $E10) {
							$table .= '<td class="tg-611x"> <font color='.$ColorCode.'>'.$Stations->e10." €".'</font> </td>';
						}
						else {
							$table .= '<td class="tg-611x">'.$Stations->e10." €".'</font> </td>';
						}
					}
					$StationID = $Stations->id;
					$table .= '<td class="tg-611x"> <button type="button" alt="Details" onclick="window.xhrGet=function xhrGet(o) {var HTTP = new XMLHttpRequest();HTTP.open(\'GET\',o.url,true);HTTP.send();};window.xhrGet({ url: \'hook/IPS2TankerkoenigListe_'.$this->InstanceID.'?StationID='.$StationID.'\' })"id="ID">...</button> </td>';
					$table .= '</tr>';
				}
			}
				
		}
		
		$table .= '</table>';
		If ($table <> GetValueString($this->GetIDForIdent("PetrolStationList"))) {
			SetValueString($this->GetIDForIdent("PetrolStationList"), $table);
		}
		SetValueInteger($this->GetIDForIdent("LastUpdate"), time() );
	}
	
	protected function ProcessHookData() 
	{		
		if (isset($_GET["StationID"])) {
			$StationID = $_GET["StationID"];
			$this->SendDebug("ProcessHookData", "StationID: ".$StationID, 0);
			$this->GetStationDetails($StationID);
		}
	}    
	
	private function GetStationDetails(string $StationID)
	{
		If (strlen($StationID) > 0) {
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{6ADD0473-D761-A2BF-63BE-CFE279089F5A}", 
				"Function" => "GetDetailInformation", "InstanceID" => $this->InstanceID, "StationID" => $StationID)));
			If ($Result <> false) {
				$this->SetStatus(102);
				$this->SendDebug("GetStationDetails", $Result, 0);
				$this->ShowDetails($Result);
			}
			else {
				$this->SetStatus(202);
				$this->SendDebug("GetStationDetails", "Fehler bei der Datenermittlung!", 0);
			}
		}
		else {
			$this->SendDebug("GetStationDetails", "Stations ID fehlerhaft!", 0);
		}
	}    
	
	private function ShowDetails(string $Text)
	{
		$ResultArray = array();
		$ResultArray = json_decode($Text);
		$ColorCode = "#00FF00";
		// Fehlerbehandlung
		If (boolval($ResultArray->ok) == false) {
			$this->SendDebug("ShowResult", "Fehler bei der Datenermittlung: ".utf8_encode($ResultArray->message), 0);
			return;
		} 
		// Tabelle aufbauen	
		$table = '<style type="text/css">';
		$table .= '<link rel="stylesheet" href="./.../webfront.css">';
		$table .= "</style>";
		$table .= '<table class="tg">';
		$table .= '<tr>';
		$table .= '<td class="tg-611x">'.ucwords(strtolower($ResultArray->station->brand)).'</td>';
		$table .= '</tr>';
		$table .= '<tr>';
		$table .= '<td class="tg-611x">'.ucwords(strtolower($ResultArray->station->name)).'</td>';
		$table .= '</tr>';
		$table .= '<tr>';
		$table .= '<td class="tg-611x">'.ucwords(strtolower($ResultArray->station->street))." ".$ResultArray->station->houseNumber.'</td>';
		$table .= '</tr>';
		$table .= '<tr>';
		$table .= '<td class="tg-611x">'.$ResultArray->station->postCode." ".ucwords(strtolower($ResultArray->station->place)).'</td>';
		$table .= '</tr>';
		If (boolval($ResultArray->station->wholeDay) == true) {
			$table .= '<tr>';
			$table .= '<td class="tg-611x">'."Ganztägig geöffnet".'</td>';
			$table .= '</tr>';
		}
		else {
			$table .= '<tr>';
			$table .= '<td class="tg-611x">'."Öffnungszeiten:".'</td>';
			$table .= '</tr>';
			foreach($ResultArray->station->openingTimes as $Open) {
				$table .= '<tr>';
				$table .= '<td class="tg-611x">'.$Open->text.'</td>';
				$table .= '<td class="tg-611x">'.$Open->start." Uhr bis ".'</td>';
				$table .= '<td class="tg-611x">'.$Open->end." Uhr".'</td>';
				$table .= '</tr>';
			}
			If (boolval($ResultArray->station->isOpen) == true) {
				$table .= '<tr>';
				$table .= '<td class="tg-611x">'."Aktuell geöffnet".'</td>';
				$table .= '</tr>';
			}
			else {
				$table .= '<tr>';
				$table .= '<td class="tg-611x">'."Aktuell geschlossen".'</td>';
				$table .= '</tr>';
			}
			foreach($ResultArray->station->overrides as $Closed) {
				$table .= '<tr>';
				$table .= '<td class="tg-611x">'.$Closed.'</td>';
				$table .= '</tr>';
			}
		}
		$table .= '</table>';
		$Lat = floatval($ResultArray->station->lat);
		$Long = floatval($ResultArray->station->lng);
		
		If ($table <> GetValueString($this->GetIDForIdent("PetrolStationDetail"))) {
			SetValueString($this->GetIDForIdent("PetrolStationDetail"), $table);
		}
	}
	   
	private function RegisterProfileFloat($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
	{
	        if (!IPS_VariableProfileExists($Name))
	        {
	            IPS_CreateVariableProfile($Name, 2);
	        }
	        else
	        {
	            $profile = IPS_GetVariableProfile($Name);
	            if ($profile['ProfileType'] != 2)
	                throw new Exception("Variable profile type does not match for profile " . $Name);
	        }
	        IPS_SetVariableProfileIcon($Name, $Icon);
	        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
	        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
	        IPS_SetVariableProfileDigits($Name, $Digits);
	}
	
	private function RegisterHook($WebHook) 
	{ 
		$ids = IPS_GetInstanceListByModuleID("{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}"); 
		if(sizeof($ids) > 0) { 
			$hooks = json_decode(IPS_GetProperty($ids[0], "Hooks"), true); 
			$found = false; 
			foreach($hooks as $index => $hook) { 
				if($hook['Hook'] == $WebHook) { 
					if($hook['TargetID'] == $this->InstanceID) 
						return; 
					$hooks[$index]['TargetID'] = $this->InstanceID; 
					$found = true; 
				} 
			} 
			if(!$found) { 
				$hooks[] = Array("Hook" => $WebHook, "TargetID" => $this->InstanceID); 
			} 
			IPS_SetProperty($ids[0], "Hooks", json_encode($hooks)); 
			IPS_ApplyChanges($ids[0]); 
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
