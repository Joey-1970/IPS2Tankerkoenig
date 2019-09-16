<?
    // Klassendefinition
    class IPS2TankerkoenigListe extends IPSModule 
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
		$this->RegisterPropertyInteger("Timer_1", 60);
		$this->RegisterTimer("Timer_1", 0, 'I2TListe_GetDataUpdate($_IPS["TARGET"]);');
		$this->RegisterPropertyBoolean("Diesel", true);
		$this->RegisterPropertyBoolean("E5", true);
		$this->RegisterPropertyBoolean("E10", true);
		$this->RegisterPropertyBoolean("ShowOnlyOpen", true);
		
		// Status-Variablen anlegen
		$this->RegisterVariableString("PetrolStationList", "Tankstellen", "~HTMLBox", 10);
		
		$this->RegisterVariableInteger("LastUpdate", "Letztes Update", "~UnixTimestamp", 20);
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
				
		$arrayElements = array(); 
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Lat", "caption" => "Latitude", "digits" => 4);
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Long", "caption" => "Longitude", "digits" => 4);
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Radius", "caption" => "Radius", "digits" => 1);
		$arrayElements[] = array("type" => "Label", "label" => "Aktualisierung");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Timer_1", "caption" => "s");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Anzuzeigende Sorten");
		$arrayElements[] = array("type" => "CheckBox", "name" => "Diesel", "caption" => "Diesel"); 
		$arrayElements[] = array("type" => "CheckBox", "name" => "E5", "caption" => "Super E5");
		$arrayElements[] = array("type" => "CheckBox", "name" => "E10", "caption" => "Super E10");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "CheckBox", "name" => "ShowOnlyOpen", "caption" => "Nur geöffnete Tankstellen anzeigen"); 
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
				$this->SetTimerInterval("Timer_1", $this->ReadPropertyInteger("Timer_1") * 1000);
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
				$this->SendDebug("GetDataUpdate", $Result, 0);
				$this->ShowResult($Result);
			}
			else {
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
		foreach($ResultArray->stations as $Stations) {
			If (($Diesel > floatval($Stations->diesel)) AND (floatval($Stations->diesel) > 0)) {
				$Diesel = floatval($Stations->diesel);
			}
			If (($E5 > floatval($Stations->e5)) AND (floatval($Stations->e5) > 0)) {
				$E5 = floatval($Stations->e5);
			}
			If (($E10 > floatval($Stations->e10)) AND (floatval($Stations->e10) > 0)) {
				$E10 = floatval($Stations->e10);
			}
		}
		$this->SendDebug("ShowResult", "Diesel: ".$Diesel." E5: ".$E5." E10: ".$E10, 0);
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
		$table .= '<th class="tg-kv4b">ID kopieren<br></th>';
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
				$table .= '<button type="button" id="ID">...</button>';
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
					$table .= '<button type="button" id="ID">...</button>';
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
	    
	private function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize)
	{
	        if (!IPS_VariableProfileExists($Name))
	        {
	            IPS_CreateVariableProfile($Name, 1);
	        }
	        else
	        {
	            $profile = IPS_GetVariableProfile($Name);
	            if ($profile['ProfileType'] != 1)
	                throw new Exception("Variable profile type does not match for profile " . $Name);
	        }
	        IPS_SetVariableProfileIcon($Name, $Icon);
	        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
	        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);    
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
