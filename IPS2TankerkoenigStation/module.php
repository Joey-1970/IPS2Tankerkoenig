<?
    // Klassendefinition
    class IPS2TankerkoenigStation extends IPSModule 
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
		$this->RegisterPropertyString("StationID", "");
		$this->RegisterPropertyInteger("Timer_1", 10);
		$this->RegisterTimer("Timer_1", 0, 'I2TStation_GetDataUpdate($_IPS["TARGET"]);');
		$this->RegisterPropertyBoolean("Diesel", true);
		$this->RegisterPropertyBoolean("E5", true);
		$this->RegisterPropertyBoolean("E10", true);
		
		// Webhook einrichten
		//$this->RegisterHook("/hook/IPS2TankerkoenigListe_".$this->InstanceID);
		
		// Status-Variablen anlegen
		$this->RegisterVariableString("PetrolStation", "Tankstelle", "~HTMLBox", 10);
		
		$this->RegisterVariableInteger("LastUpdate", "Letztes Update", "~UnixTimestamp", 20);
			
		$this->RegisterVariableFloat("Diesel", "Diesel", "~Euro", 30);
		$this->RegisterVariableFloat("E5", "Super E5", "~Euro", 40);
		$this->RegisterVariableFloat("E10", "Super E10", "~Euro", 50);
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "Kommunikationfehler!");
				
		$arrayElements = array(); 
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "StationID", "caption" => "Tankstellen ID");
		$arrayElements[] = array("type" => "Label", "label" => "Aktualisierung (gemäß Tankerkönig.de Minumum 10 Minuten)");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Timer_1", "caption" => "min");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Anzuzeigende Sorten");
		$arrayElements[] = array("type" => "CheckBox", "name" => "Diesel", "caption" => "Diesel"); 
		$arrayElements[] = array("type" => "CheckBox", "name" => "E5", "caption" => "Super E5");
		$arrayElements[] = array("type" => "CheckBox", "name" => "E10", "caption" => "Super E10");
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
				If (strlen($this->ReadPropertyString("StationID")) > 0) {
					$this->GetDataUpdate();
				}
				else {
					$this->SendDebug("GetDataUpdate", "Keine Tnkstellen ID verfügbar!", 0);
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
		If (strlen($StationID) > 0) {
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{6ADD0473-D761-A2BF-63BE-CFE279089F5A}", 
				"Function" => "GetDetailInformation", "InstanceID" => $this->InstanceID, "StationID" => $StationID)));
			If ($Result <> false) {
				$this->SetStatus(102);
				$this->SendDebug("GetDataUpdate", $Result, 0);
				$this->ShowDetails($Result);
			}
			else {
				$this->SetStatus(202);
				$this->SendDebug("GetDataUpdate", "Fehler bei der Datenermittlung!", 0);
			}
		}
		else {
			$this->SendDebug("GetStationDetails", "Stations ID fehlerhaft!", 0);
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
		
		If ($table <> GetValueString($this->GetIDForIdent("PetrolStation"))) {
			SetValueString($this->GetIDForIdent("PetrolStation"), $table);
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
