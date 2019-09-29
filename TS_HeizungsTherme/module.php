<?php
class TS_HeizungsTherme extends IPSModule {

    // Überschreibt die interne IPS_Create($id) Funktion
    public function Create() {
        // Diese Zeile nicht löschen.
        parent::Create();
		
		$this->RegisterPropertyFloat("Aussentemperatur_ID", 0);
		$this->RegisterPropertyFloat("Raumtemperatur_ID", 0);
		$this->RegisterPropertyFloat("Kesseltemperatur_ID", 0);
		$this->RegisterPropertyFloat("Vorlauftemperatur_ID", 0);
		$this->RegisterPropertyFloat("Rücklauftemperatur_ID", 0);

		$this->RegisterPropertyInteger("dallastimer_ID", 0);
		$this->RegisterPropertyInteger("reglertimer_ID", 0);
		$this->RegisterPropertyInteger("graphtimer_ID", 0);

		// Erstellt einen Timer mit dem Namen "Update" und einem Intervall von 5 Sekunden. 
		$this->RegisterTimer("Update_Dallas", 10000, 'TSHEIZT_read_dallas($_IPS["TARGET"]);');
		$this->RegisterTimer("Update_Regler", 10000, 'TSHEIZT_UpdateRegler($_IPS["TARGET"]);');
		$this->RegisterTimer("Update_Graph", 10000, 'TSHEIZT_Graph($_IPS["TARGET"]);');
	}
	
	public function Destroy(){
		//Never delete this line!
		parent::Destroy();
	}

    public function ApplyChanges() {

        // Diese Zeile nicht löschen
        parent::ApplyChanges();
		$this->CreateCategoryByName(0,"TS_Heizung");
		
		$this->RegisterProfileInteger("TS.HeatingStatus", "Information", "", "", 0, 2, 1);
		IPS_SetVariableProfileAssociation("TS.HeatingStatus", 0, "unbekannt", "Information", -1);
		IPS_SetVariableProfileAssociation("TS.HeatingStatus", 1, "Winterbetrieb", "Information", -1);
		IPS_SetVariableProfileAssociation("TS.HeatingStatus", 2, "Sommerbetrieb", "Information", -1);
		IPS_SetVariableProfileAssociation("TS.HeatingStatus", 3, "gestört", "Information", -1);
//     function RegisterProfileFloat($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize,$digits)
		$this->RegisterProfileFloat("TS.Steilheit", "Graph", "", "",0,4,0.1,1);
		
        $this->RegisterProfileInteger("TS.Parallelverschiebung", "Graph", "", " K", -20, 20, 1);
		$this->RegisterProfileInteger("TS.Boosthysterese", "Graph", "", " K", 0, 10, 1);
		$this->RegisterProfileInteger("TS.Boostanhebung", "Graph", "", " K", 0, 15, 1);
		
		//     RegisterVariableFloat( string $Ident, string $Name, string $Profil, integer $Position )
        $this->RegisterVariableFloat("Steuerspannung", "Steuerspannung", "~Volt",101);
		$this->RegisterVariableFloat("Kesselabschaltemperatur", "Kesselabschaltemperatur", "~Temperature",10);
        $this->RegisterVariableFloat("Aussentemperatur", "Aussentemperatur", "~Temperature",10);
        $this->RegisterVariableFloat("Raumtemperatur", "Raumtemperatur", "~Temperature.Room",11);
        $this->RegisterVariableFloat("Kesseltemperatur", "Kesseltemperatur", "~Temperature",12);
        $this->RegisterVariableFloat("Vorlauftemperatur", "Vorlauftemperatur", "~Temperature",13);
        $this->RegisterVariableFloat("Ruecklauftemperatur", "Rücklauftemperatur", "~Temperature",14);
		$this->RegisterVariableFloat("RaumSolltemperatur", "Raum Solltemperatur Tag", "~Temperature.Room",30);
			$this->EnableAction("RaumSolltemperatur");
		$this->RegisterVariableFloat("RaumSolltemperaturNacht", "Raum Solltemperatur Nacht", "~Temperature.Room",31);
			$this->EnableAction("RaumSolltemperaturNacht");
		$this->RegisterVariableFloat("aktuelleRaumSolltemperatur", "Raum Solltemp.aktuell", "~Temperature.Room",11);
		
		$this->RegisterVariableFloat("AussentemparaturMin", "Ausentemp.min", "~Temperature",10);
		
		$this->RegisterVariableFloat("Sommerumschaltung", "Sommerumschaltung", "~Temperature.Room",29);
			$this->EnableAction("Sommerumschaltung");
		$this->RegisterVariableFloat("Kesselmin", "Kessel min. Temperature", "~Temperature",41);
			$this->EnableAction("Kesselmin");
		$this->RegisterVariableFloat("Kesselmax", "Kessel max. Temperature", "~Temperature",40);
			$this->EnableAction("Kesselmax");
		$this->RegisterVariableFloat("duty_cycle", "duty cycle", "",100);
		$this->RegisterVariableInteger("Parallelverschiebung", "Parallelverschiebung", "TS.Parallelverschiebung",50);
			$this->EnableAction("Parallelverschiebung");
		$this->RegisterVariableFloat("Steilheit", "Steilheit", "TS.Steilheit",51);
			$this->EnableAction("Steilheit");
		$this->RegisterVariableInteger("Status", "Status", "TS.HeatingStatus",0);
		$this->RegisterVariableFloat("KesselSolltemperatur", "Kessel Solltemperatur", "Temperature.Room",35);
 		$this->RegisterVariableString("onewireId", "onewireId","",200);
          $this->DisableAction("onewireId");
		$this->RegisterVariableInteger("onewirezaehler", "onewirezaehler", "",210);
          $this->DisableAction("onewirezaehler");
		$this->RegisterVariableInteger("boostTemperatur", "boost Temperatur", "TS.Boostanhebung",60);
		$this->RegisterVariableInteger("boostHysterese", "boost Hysterese", "TS.Boosthysterese",61);
			$this->EnableAction("boostHysterese");
		$this->RegisterVariableInteger("ThermeHysterese", "Therme Hysterese", "TS.Boostanhebung",62);
			$this->EnableAction("ThermeHysterese");

		$this->RegisterVariableInteger("boostAnhebung", "boost Anhebung", "TS.Boosthysterese",61);
			$this->EnableAction("boostAnhebung");

		$this->RegisterVariableBoolean("Waermebedarf", "Wärmebedarf", "~Switch",100);

		$this->RegisterVariableBoolean("UhrHeizungKessel", "Uhr Heizung", "~Switch",-10);
          $this->EnableAction("UhrHeizungKessel");
		
//		
		$this->CreateWochenplan("Uhr Heizung", 2,"UhrHeizungKessel");
		 // CreateWochenplan($name, $Typ, $Var)
		$this->CreateImageHeizkennline(); 
// 
        $this->RegisterMessage($this->ReadPropertyFloat("Aussentemperatur_ID"), VM_UPDATE);
		$this->RegisterMessage($this->ReadPropertyFloat("Raumtemperatur_ID"), VM_UPDATE);
		$this->RegisterMessage($this->ReadPropertyFloat("Kesseltemperatur_ID"), VM_UPDATE);
        $this->RegisterMessage($this->ReadPropertyFloat("Vorlauftemperatur_ID"), VM_UPDATE);		
		
		$this->SetTimerInterval("Update_Dallas", ($this->ReadPropertyInteger("dallastimer_ID") * 1000));
		$this->SetTimerInterval("Update_Regler", ($this->ReadPropertyInteger("reglertimer_ID") * 1000));
		$this->SetTimerInterval("Update_Graph", ($this->ReadPropertyInteger("graphtimer_ID") * 1000));


        // private function CreateCategoryByName($id, $name)

		$this->CreateCategoryByName(IPS_GetCategoryIDByName("TS_Heizung", 0),"Statusanzeigen");	
		$this->CreateCategoryByName(IPS_GetCategoryIDByName("TS_Heizung", 0),"Einstellungen");
		$this->CreateCategoryByName(IPS_GetCategoryIDByName("TS_Heizung", 0),"Graph");

		$id=$this->CreateLinkByName(IPS_GetCategoryIDByName("Graph",IPS_GetCategoryIDByName("TS_Heizung", 0)),"Heizkennline",@IPS_GetMediaIDByName("Heizkennline", IPS_GetInstanceIDByName("Heizkennline", 0) ));
			IPS_SetPosition($id, 0);
		//private function CreateLinkByName($id, $name, $varID)		
		$id=$this->CreateLinkByName(IPS_GetCategoryIDByName("Einstellungen",IPS_GetCategoryIDByName("TS_Heizung", 0)),"Raum Solltemperatur Tag",($this->GetIDForIdent("RaumSolltemperatur")));	
			IPS_SetPosition($id, 10);
		$id=$this->CreateLinkByName(IPS_GetCategoryIDByName("Einstellungen",IPS_GetCategoryIDByName("TS_Heizung", 0)),"Uhr Heizung Kessel",($this->GetIDForIdent("UhrHeizungKessel")));
			IPS_SetPosition($id, -200);
		$id=$this->CreateLinkByName(IPS_GetCategoryIDByName("Einstellungen",IPS_GetCategoryIDByName("TS_Heizung", 0)),"boost Hysterese",($this->GetIDForIdent("boostHysterese")));
			IPS_SetPosition($id, 40);
		$id=$this->CreateLinkByName(IPS_GetCategoryIDByName("Einstellungen",IPS_GetCategoryIDByName("TS_Heizung", 0)),"boost Anhebung",($this->GetIDForIdent("boostAnhebung")));
				IPS_SetPosition($id, 40);
		$id=$this->CreateLinkByName(IPS_GetCategoryIDByName("Einstellungen",IPS_GetCategoryIDByName("TS_Heizung", 0)),"Steilheit",($this->GetIDForIdent("Steilheit")));	
				IPS_SetPosition($id, 60);
		$id=$this->CreateLinkByName(IPS_GetCategoryIDByName("Einstellungen",IPS_GetCategoryIDByName("TS_Heizung", 0)),"Therme Hysterese",($this->GetIDForIdent("ThermeHysterese")));	
				IPS_SetPosition($id, 60);

		$id=$this->CreateLinkByName(IPS_GetCategoryIDByName("Einstellungen",IPS_GetCategoryIDByName("TS_Heizung", 0)),"Parallelverschiebung",($this->GetIDForIdent("Parallelverschiebung")));	
				IPS_SetPosition($id, 50);
		$id=$this->CreateLinkByName(IPS_GetCategoryIDByName("Einstellungen",IPS_GetCategoryIDByName("TS_Heizung", 0)),"Kessel max",($this->GetIDForIdent("Kesselmax")));	
				IPS_SetPosition($id, 70);
		$id=$this->CreateLinkByName(IPS_GetCategoryIDByName("Einstellungen",IPS_GetCategoryIDByName("TS_Heizung", 0)),"Kessel min",($this->GetIDForIdent("Kesselmin")));
				IPS_SetPosition($id, 80);
		$id=$this->CreateLinkByName(IPS_GetCategoryIDByName("Einstellungen",IPS_GetCategoryIDByName("TS_Heizung", 0)),"Sommerumschaltung",($this->GetIDForIdent("Sommerumschaltung")));	
			IPS_SetPosition($id, -90);
		$id=$this->CreateLinkByName(IPS_GetCategoryIDByName("Einstellungen",IPS_GetCategoryIDByName("TS_Heizung", 0)),"Raum Solltemperatur Nacht",($this->GetIDForIdent("RaumSolltemperaturNacht")));	
			IPS_SetPosition($id, 20);

		$id= $this->CreateLinkByName(IPS_GetCategoryIDByName("Einstellungen",IPS_GetCategoryIDByName("TS_Heizung", 0)),"Uhr Heizung",IPS_GetObjectIDByName("Uhr Heizung",($this->GetIDForIdent("UhrHeizungKessel"))));	
			IPS_SetPosition($id, -100);
		
		$id=$this->CreateLinkByName(IPS_GetCategoryIDByName("Statusanzeigen",IPS_GetCategoryIDByName("TS_Heizung", 0)),"Aussentemperatur",($this->GetIDForIdent("Aussentemperatur")));	
			IPS_SetPosition($id, 0);
		$id=$this->CreateLinkByName(IPS_GetCategoryIDByName("Statusanzeigen",IPS_GetCategoryIDByName("TS_Heizung", 0)),"Aussentemp.min",($this->GetIDForIdent("AussentemparaturMin")));	
			IPS_SetPosition($id, 1);
		$id=$this->CreateLinkByName(IPS_GetCategoryIDByName("Statusanzeigen",IPS_GetCategoryIDByName("TS_Heizung", 0)),"Steuerspannung",($this->GetIDForIdent("Steuerspannung")));	
			IPS_SetPosition($id, 100);
		$id=$this->CreateLinkByName(IPS_GetCategoryIDByName("Statusanzeigen",IPS_GetCategoryIDByName("TS_Heizung", 0)),"Kessel Solltemperatur",($this->GetIDForIdent("KesselSolltemperatur")));	
			IPS_SetPosition($id, 90);
		$id=$this->CreateLinkByName(IPS_GetCategoryIDByName("Statusanzeigen",IPS_GetCategoryIDByName("TS_Heizung", 0)),"Status",($this->GetIDForIdent("Status")));	
			IPS_SetPosition($id, -100);
		$id=$this->CreateLinkByName(IPS_GetCategoryIDByName("Statusanzeigen",IPS_GetCategoryIDByName("TS_Heizung", 0)),"duty_cycle",($this->GetIDForIdent("duty_cycle")));	
			IPS_SetPosition($id, 110);
		$id=$this->CreateLinkByName(IPS_GetCategoryIDByName("Statusanzeigen",IPS_GetCategoryIDByName("TS_Heizung", 0)),"Rücklauftemperatur",($this->GetIDForIdent("Ruecklauftemperatur")));	
			IPS_SetPosition($id, 52);
		$id=$this->CreateLinkByName(IPS_GetCategoryIDByName("Statusanzeigen",IPS_GetCategoryIDByName("TS_Heizung", 0)),"Vorlauftemperatur",($this->GetIDForIdent("Vorlauftemperatur")));	
			IPS_SetPosition($id, 51);
		$id=$this->CreateLinkByName(IPS_GetCategoryIDByName("Statusanzeigen",IPS_GetCategoryIDByName("TS_Heizung", 0)),"Kesseltemperatur",($this->GetIDForIdent("Kesseltemperatur")));	
			IPS_SetPosition($id, 50);
		$id=$this->CreateLinkByName(IPS_GetCategoryIDByName("Statusanzeigen",IPS_GetCategoryIDByName("TS_Heizung", 0)),"Raumtemperatur",($this->GetIDForIdent("Raumtemperatur")));
			IPS_SetPosition($id, 10);		
		$id=$this->CreateLinkByName(IPS_GetCategoryIDByName("Statusanzeigen",IPS_GetCategoryIDByName("TS_Heizung", 0)),"boost Temperatur",($this->GetIDForIdent("boostTemperatur")));	
			IPS_SetPosition($id, 20);
		$id=$this->CreateLinkByName(IPS_GetCategoryIDByName("Statusanzeigen",IPS_GetCategoryIDByName("TS_Heizung", 0)),"Raum Solltemp.aktuell",($this->GetIDForIdent("aktuelleRaumSolltemperatur")));	
			IPS_SetPosition($id, 12);


		IPS_SetParent(IPS_GetParent($this->GetIDForIdent("Aussentemperatur")),IPS_GetCategoryIDByName("TS_Heizung", 0)); //Verschiebe Modult nach TS_Heizung
	
    }

	public function RequestAction($Ident, $Value) {
  		switch($Ident) {
	        case "Sommerumschaltung":
	            $this->Set_Sommerumschaltung($Value);
	            break;

			case "boostHysterese":
	            $this->Set_boostHysterese($Value);
	            break;
	        case "boostAnhebung":
	            $this->Set_boostAnhebung($Value);
	            break;
			case "UhrHeizungKessel":
	            $this->Set_UhrHeizungKessel($Value);
	            break;
	        case "RaumSolltemperatur":
	            $this->Set_Raumsollwert($Value);
	            break;
	        case "RaumSolltemperaturNacht":
	            $this->Set_Raumsollwertnacht($Value);
	            break;
	        case "Kesselmin":
	            $this->Set_Kesselmin($Value);
	            break;
	        case "Kesselmax":
	            $this->Set_Kesselmax($Value);
	            break;
	        case "MinSpannung":
	            $this->Set_MinSpannung($Value);
	            break;
			case "Steilheit":
	            $this->Set_Steilheit($Value);
	            break;
	        case "Parallelverschiebung":
	            $this->Set_Parallelverschiebung($Value);
	            break;
	        case "ThermeHysterese":
	            $this->Set_ThermeHysterese($Value);
	            break;

				
				default:
	            throw new Exception("Invalid Ident");
		}
	}

	public function Set_Sommerumschaltung(float $value)
	{
		SetValue($this->GetIDForIdent("Sommerumschaltung"), $value);
	}
	
	public function Set_ThermeHysterese(int $value)
	{
		SetValue($this->GetIDForIdent("ThermeHysterese"), $value);
	}
    public function Set_boostHysterese(Int $value)
	{
		SetValue($this->GetIDForIdent("boostHysterese"), $value);
	}

	public function Set_boostAnhebung(Int $value)
	{
		SetValue($this->GetIDForIdent("boostAnhebung"), $value);
	}

	public function Set_UhrHeizungKessel(bool $value)
	{
		SetValue($this->GetIDForIdent("UhrHeizungKessel"), $value);
	}
	
	public function Set_MinSpannung(float $value)
	{
		SetValue($this->GetIDForIdent("MinSpannung"), $value);
	}
	
	public function Set_Kesselmax(float $value)
	{
		SetValue($this->GetIDForIdent("Kesselmax"), $value);
	}
	
	public function Set_Kesselmin(float $value)
	{
		SetValue($this->GetIDForIdent("Kesselmin"), $value);
	}
	
	public function Set_Raumsollwert(float $value)
	{
		SetValue($this->GetIDForIdent("RaumSolltemperatur"), $value);
	}
	
	public function Set_Raumsollwertnacht(float $value)
	{
		SetValue($this->GetIDForIdent("RaumSolltemperaturNacht"), $value);
	}

	public function Set_Steilheit(float $value)
	{
		SetValue($this->GetIDForIdent("Steilheit"), $value);
	}
	
	public function Set_Parallelverschiebung(Int $value)
	{
		SetValue($this->GetIDForIdent("Parallelverschiebung"), $value);
	}
///////////////////////////////////////////////////////////////////////////////
    public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
        $this->SendDebug("MessageSink", "Message from SenderID ".$SenderID." with Message ".$Message."\r\n Data: ".print_r($Data, true), 0);
        $this->UpdateValue();
    }
	
    public function UpdateValue() {
        $id = $this->ReadPropertyFloat("Aussentemperatur_ID");
        if($id > 0) {
            $value = GetValue($id);
            SetValue($this->GetIDForIdent("Aussentemperatur"), GetValue($id));
        }
        $id = $this->ReadPropertyFloat("Raumtemperatur_ID");
        if($id > 0) {
            $value = GetValue($id);
            SetValue($this->GetIDForIdent("Raumtemperatur"), GetValue($id));
        }
        $id = $this->ReadPropertyFloat("Kesseltemperatur_ID");
        if($id > 0) {
            $value = GetValue($id);
            SetValue($this->GetIDForIdent("Kesseltemperatur"), GetValue($id));
        }
        $id = $this->ReadPropertyFloat("Vorlauftemperatur_ID");
        if($id > 0) {
            $value = GetValue($id);
            SetValue($this->GetIDForIdent("Vorlauftemperatur"), GetValue($id));
        }
        $id = $this->ReadPropertyFloat("Rücklauftemperatur_ID");
        if($id > 0) {
            $value = GetValue($id);
            SetValue($this->GetIDForIdent("Ruecklauftemperatur"), GetValue($id));
        }
	}
    public function UpdateRegler() {
			$steuerspannung_id= $this->GetIDForIdent("Steuerspannung");
//			$Aussentemperatur = GetValueFloat($this->GetIDForIdent("Aussentemperatur"));
//          $this->RegisterVariableFloat("AussentemparaturMin", "Ausentemp.min", "~Temperature",10);
			$Aussentemperatur = GetValueFloat($this->GetIDForIdent("AussentemparaturMin"));
			$SommerTemp = GetValueFloat($this->GetIDForIdent("Sommerumschaltung"));
			$Steilheit = GetValueFloat($this->GetIDForIdent("Steilheit"));
			$MinTemp = GetValueFloat($this->GetIDForIdent("Kesselmin"));
			$MaxTemp = GetValueFloat($this->GetIDForIdent("Kesselmax"));
			$Parallelverschiebung = GetValueInteger($this->GetIDForIdent("Parallelverschiebung"));
			$Raumistwert = GetValueFloat($this->GetIDForIdent("Raumtemperatur"));
			$Vorlauftemperatur = GetValueFloat($this->GetIDForIdent("Vorlauftemperatur"));
			$Rücklauftemperatur = GetValueFloat($this->GetIDForIdent("Ruecklauftemperatur"));
			$Uhr= GetValueBoolean($this->GetIDForIdent("UhrHeizungKessel"));
			$boostanhebung =GetValueInteger($this->GetIDForIdent("boostAnhebung")); 
			$boostHysterese =GetValueInteger($this->GetIDForIdent("boostHysterese")); 
			$wärmebedarf=GetValueBoolean($this->GetIDForIdent("Waermebedarf"));
			//TempDiff = $Vorlauftemperatur - $Rücklauftemperatur;
			//$Parallelverschiebung = $Parallelverschiebung + ($TempDiff - $Parallelverschiebung);
			//$Raumsollwert = GetValueFloat($this->GetIDForIdent("RaumSolltemperatur"));
			$spannung = GetValueFloat($this->GetIDForIdent("Steuerspannung"));;

			if ($Uhr == 1)  {
				$Raumsollwert= GetValueFloat($this->GetIDForIdent("RaumSolltemperatur"));
				} else{
				$Raumsollwert= GetValueFloat($this->GetIDForIdent("RaumSolltemperaturNacht"));
			}
			SetValueFloat($this->GetIDForIdent("aktuelleRaumSolltemperatur"), $Raumsollwert);
			
            If ($boostHysterese > 0) {
				$e = ($Raumsollwert - $Raumistwert)/$boostHysterese;
				$boostwert_e= (round($boostanhebung*$e));
				if ($e > $boostHysterese){
						SetValueInteger($this->GetIDForIdent("boostTemperatur"),$boostanhebung); 
					}
					else
					{
						$boost=$boostwert_e; 
						If ($boost < 0) {			
							$boost=0; 
						}
						SetValueInteger($this->GetIDForIdent("boostTemperatur"),$boost);
					}
				}

			$boostTemperatur =GetValueInteger($this->GetIDForIdent("boostTemperatur")); 
			$hysterese= GetValueInteger($this->GetIDForIdent("ThermeHysterese"));
			$ko=12.7;
			If ($Aussentemperatur < $SommerTemp) {
				// Winterbetrieb
				If (GetValueInteger($this->GetIDForIdent("Status")) <> 1) {
					SetValueInteger($this->GetIDForIdent("Status"), 1);
				}
				
				$KesselSolltemperatur = min(max(round((0.55 * $Steilheit * (pow($Raumsollwert,($Aussentemperatur / (320 - $Aussentemperatur * 4))))*((-$Aussentemperatur + 20) * 2) + $Raumsollwert + $Parallelverschiebung) * 1) / 1, $MinTemp), $MaxTemp);
				SetValueFloat($this->GetIDForIdent("KesselSolltemperatur"), $KesselSolltemperatur);
				$kessel_abschaltwert = ($KesselSolltemperatur+$hysterese+$boostTemperatur);
				
				if ($kessel_abschaltwert >= 30){
					$ko=12.86;
				}
				if ($kessel_abschaltwert >= 40){
					$ko=12.76;
				}
				if ($kessel_abschaltwert >= 45){
					$ko=12.64;
				}
				if ($kessel_abschaltwert >= 50){
					$ko=12.61;
				}
				if ($kessel_abschaltwert >= 60){
					$ko=12.55;
				}
				$spannung = ((( ($kessel_abschaltwert) - 43) / 10) + $ko);
				//$spannung = ((( ($KesselSolltemperatur+$hysterese) - 40) / 10) + 11.9);
				/* 
				9,9=15
				10,4=20
				10,9=25
				11,56 = 30
				12,06 = 35
				12,49 = 40
				12,84 = 45
				13,34 = 50
				13,85 = 55
				14,28 = 60
				14,78 = 65
				15,28 = 70
				15,71 = 75
				*/

				If (($Raumistwert) > ($Raumsollwert + 0.2)) {			
					$halbe_hysterese = $hysterese/2;
					$kessel_abschaltwert = ($KesselSolltemperatur);
					$spannung = ((( ($KesselSolltemperatur)  - 43) / 10) + $ko);
				}	

				If( ($Raumistwert) < ($Raumsollwert - 0.3) and ($Raumistwert) < ($Raumsollwert - 0.5)){
//				If (($Raumistwert) < ($Raumsollwert - 0.2)) {			
					$spannung = ((( ($MaxTemp) - 43) / 10) + $ko);
					$kessel_abschaltwert = ($MaxTemp);

				}
				if($kessel_abschaltwert > ($MaxTemp)) {
					$spannung = ((( ($MaxTemp) - 43) / 10) + $ko);
					$kessel_abschaltwert = ($MaxTemp);
				}	
			}

			//kein Wärmebedarf, nach Raumtemperatur
            If (($Raumistwert) > ($Raumsollwert + 1)) {			
				$spannung = ((( ($MinTemp) - 43) / 10) +$ko);
				$kessel_abschaltwert = ($MinTemp);
			}
			// Ende Wärmebedarf, nach Raumtemperatur
			
			If ($Aussentemperatur >= $SommerTemp) {			     
				If (GetValueInteger($this->GetIDForIdent("Status")) <> 2) {
					SetValueInteger($this->GetIDForIdent("Status"), 2);
				}
				If (GetValueFloat($this->GetIDForIdent("KesselSolltemperatur")) <> $MinTemp) {
					SetValueFloat($this->GetIDForIdent("KesselSolltemperatur"), $MinTemp);
				}
				$spannung = ((( ($MinTemp) - 43) / 10) +$ko);
				$kessel_abschaltwert = ($MinTemp);
			}

//			If (GetValueFloat($this->GetIDForIdent("Steuerspannung")) <> $spannung) {
				SetValueFloat($this->GetIDForIdent("Steuerspannung"), $spannung);
//			}
			SetValueFloat($this->GetIDForIdent("Kesselabschaltemperatur"), $kessel_abschaltwert);

			// Wert invertieren
//			$Intensity = 255 - (intval($spannung / (15 - 2) * 100 * 2.55));
			
			$duty_cycle=((($spannung-10)*61000)+657000); //657000 PWM = 10V,  61000 = +0,1V, Startwert ist 10V für die Berechnung
			//864400 =50 - ist aber 55
			//
			$Intensity = $duty_cycle;
 			If (GetValue($this->GetIDForIdent("duty_cycle")) <> $duty_cycle) {
				SetValue($this->GetIDForIdent("duty_cycle"), $duty_cycle);
			}
			$this->SendDebug("Calculate", "Stellwert: ".$Intensity." Spannung: ".$spannung."V", 0);
			$wärmebedarf=GetValueBoolean($this->GetIDForIdent("Waermebedarf"));

			If (GetValueFloat($this->GetIDForIdent("Steuerspannung")) < 10.3) {
				SetValueBoolean($this->GetIDForIdent("Waermebedarf"), 0);
			}
			else 
			{
				SetValueBoolean($this->GetIDForIdent("Waermebedarf"), 1);
			}
	
			$this->Set_PWM($Intensity);
    }

	public function Graph()
	{
			// Define variables
			$max = GetValueFloat($this->GetIDForIdent("Kesselmax"));
			$min = GetValueFloat($this->GetIDForIdent("Kesselmin"));
			$Steilheit = GetValueFloat($this->GetIDForIdent("Steilheit"));
			$Korrektur_Kessel = GetValueInteger($this->GetIDForIdent("Parallelverschiebung"));
			$Soll_Tag = GetValueFloat($this->GetIDForIdent("RaumSolltemperatur"));
			$Soll_Nacht = GetValueFloat($this->GetIDForIdent("RaumSolltemperaturNacht"));
			$Raumsollwert= GetValueFloat($this->GetIDForIdent("RaumSolltemperatur"));
			$Aussentemp=GetValueFloat($this->GetIDForIdent("Aussentemperatur"));
			$Aussentemp_min=GetValueFloat($this->GetIDForIdent("Aussentemperatur"));
			$Aussentemp_gpio=GetValueFloat($this->GetIDForIdent("Aussentemperatur"));
			$Kesseltemp=GetValueFloat($this->GetIDForIdent("Vorlauftemperatur"));
			$Raumsollwert= $Soll_Nacht;//$Soll_Tag ;//12 Nacht
			$Korrektur_Kessel = GetValueInteger($this->GetIDForIdent("Parallelverschiebung"));
			//$Kessel_faktorID = IPS_GetObjectIDByName("Kessel Hysterese", IPS_GetParent($_IPS['SELF']));
			$Kessel_faktor = 0;
			$j=20;
			for ( $i = 1; $i <= 550; $i++ ) {
				$Values[$i] = (min(max( ((0.55*$Steilheit*(pow($Raumsollwert,($j/(320-$j*4))))*((-$j+20)*2)+$Raumsollwert+$Korrektur_Kessel)*1)/1,$min),$max)+$Kessel_faktor)*10;
				$Test[$i] = $Values[$i];
				$temp[$i] = $j;
				$Values[$i] = 800 - $Values[$i] ;
				$j=$j-0.1;
			}
			$Raumsollwert= $Soll_Tag ;//12 Nacht
			$Kessel_faktor = 0;
			$j=20;
			for ( $i = 1; $i <= 550; $i++ ) {
			   $Values2[$i] = (min(max( ((0.55*$Steilheit*(pow($Raumsollwert,($j/(320-$j*4))))*((-$j+20)*2)+$Raumsollwert+$Korrektur_Kessel)*1)/1,$min),$max)+$Kessel_faktor)*10;
			   $Values2[$i] = 800 - $Values2[$i] ;
			   $j=$j-0.1;
			}

			$xPoint=50;
			$yPoint=50;
			$Image = imagecreate( 650, 650 );
			#1st colour allocated is used for the image background
			$backColour = imagecolorallocate( $Image, 230, 230, 230 );
			#line colours
			$colRed = imagecolorallocate( $Image, 250, 0, 0 );
			$colGelb = imagecolorallocate( $Image, 250, 100, 0 );
			$colBlue = imagecolorallocate( $Image, 0, 0, 250 );
			$colGreen = imagecolorallocate( $Image, 0, 250, 0 );
			$colGrey=imagecolorallocate($Image, 192, 192, 192);
			$colGrey2=imagecolorallocate($Image, 150, 150, 150);
			$colBlack=imagecolorallocate($Image, 2, 0, 0);

			// Raster erzeugen
			for ($i=5; $i<61; $i++){
			imageline($Image, $i*10, 50, $i*10, 600, $colGrey);
			imageline($Image, 50, $i*10, 600, $i*10, $colGrey);
			}
			for ($i=1; $i<13; $i++){
			imageline($Image, $i*50, 50, $i*50, 600, $colGrey2);
			imageline($Image, 50, $i*50, 600, $i*50, $colGrey2);
			}

			#draw x & y axis
			imageline( $Image, $xPoint, 600, 600, 600, $colBlue );
			imageline( $Image, $xPoint, 600, $xPoint, $yPoint, $colBlue );
			imagestring( $Image, 3, 10, 50, "75'C", $colRed);
			imagestring( $Image, 3, 10, 100, "70'C", $colRed);
			imagestring( $Image, 3, 10, 150, "65'C", $colRed);
			imagestring( $Image, 3, 10, 200, "60'C", $colRed);
			imagestring( $Image, 3, 10, 250, "55'C", $colRed);
			imagestring( $Image, 3, 10, 300, "50'C", $colRed);
			imagestring( $Image, 3, 10, 350, "45'C", $colRed);
			imagestring( $Image, 3, 10, 400, "40'C", $colRed);
			imagestring( $Image, 3, 10, 450, "35'C", $colRed);
			imagestring( $Image, 3, 10, 500, "30'C", $colRed);
			imagestring( $Image, 3, 10, 550, "25'C", $colRed);
			imagestring( $Image, 3, 10, 600, "20'C", $colRed);

			imagestring( $Image, 3, 50, 625, "20'C", $colBlue);
			imagestring( $Image, 3, 100, 625, "15'C", $colBlue);
			imagestring( $Image, 3, 150, 625, "10'C", $colBlue);
			imagestring( $Image, 3, 200, 625, "5'C", $colBlue);
			imagestring( $Image, 3, 250, 625, "0'C", $colBlue);
			imagestring( $Image, 3, 300, 625, "-5'C", $colBlue);
			imagestring( $Image, 3, 350, 625, "-10'C", $colBlue);
			imagestring( $Image, 3, 400, 625, "-15'C", $colBlue);
			imagestring( $Image, 3, 450, 625, "-20'C", $colBlue);
			imagestring( $Image, 3, 500, 625, "-25'C", $colBlue);
			imagestring( $Image, 3, 550, 625, "-30'C", $colBlue);

			// ohne Verschiebung und Steilheit
			$xPoint=49;
			$yPoint=50;
			$xinterval=1;
			$y1Point=$Values[1];
			for ( $i = 1; $i <= 550; $i++ ) {
				$xPoint=$xPoint + $xinterval;
				#create dummy data
			//   $Values[$i]=rand(100,400);
			//echo($Test[$i]." ".$Values[$i]."  ".$y1Point." ".$temp[$i].chr(10));
				imageline( $Image, $xPoint, $y1Point, $xPoint + $xinterval, $Values[$i], $colBlue );
				$y1Point=$Values[$i];
			}
			imagestring( $Image, 2, 572, $y1Point+10, "Kessel Nacht", $colBlue);


			//Kessel
			$xPoint=49;
			$yPoint=50;
			$xinterval=1;
			$y1Point=$Values2[1];
			for ( $i = 1; $i <= 550; $i++ ) {
				$xPoint=$xPoint + $xinterval;
				#create dummy data
			//   $Values[$i]=rand(100,400);
			//echo($Test2[$i]." ".$Values2[$i]."  ".$xPoint.chr(10));
				 imageline( $Image, $xPoint, $y1Point, $xPoint + $xinterval, $Values2[$i], $colRed );
				 $y1Point=$Values2[$i];
			}
			imagestring( $Image, 3, 572, $y1Point-15, "Kessel Tag", $colRed);


			//Aussentemperatur einzeichnen
			//print_r ($Aussentemp);
			if($Aussentemp > 20) {
			$Aussentemp = 20.0;
			}
/*
			if($Aussentemp_min > 20) {
			$Aussentemp_min = 20.0;
			}
*/
			$Aussen= 600-(($Aussentemp+35)*10);
			imageline($Image, $Aussen, 50, $Aussen, 600, $colBlue);
			imagestring( $Image, 3, $Aussen-20, 10, "Aussen Regler: ".number_format($Aussentemp, 2)." C", $colBlue);
/*
			$Aussenmin= 600-(($Aussentemp_min+35)*10);
			imageline($Image, $Aussenmin, 50, $Aussenmin, 600, $colBlue);
			imagestring( $Image, 3, $Aussenmin-0, 20, "Aussen Min : ".$Aussentemp_min." C / 5 Min.", $colBlue);

			$Aussentempgpio= 600-(($Aussentemp_gpio+35)*10);
			imageline($Image, $Aussentempgpio, 50, $Aussentempgpio, 600, $colBlue);
			imagestring( $Image, 3, $Aussentempgpio-40, 30, "Aussen GPIO : ".$Aussentemp_gpio." C", $colBlue);
*/
			// Kesseltemp
			//20 = 600px
			//75 = 50px
			$Kessel=  (($Kesseltemp*-10)+800);
			imagefilledellipse($Image, $Aussen, $Kessel, 10, 10, $colRed);
			imagestring( $Image, 5, $Aussen+10, $Kessel-20, "Kessel: ".$Kesseltemp." C", $colRed);

			//header('Content-Disposition: attachment; filename="bilddatei.png"');

			imagepng( $Image, "/var/lib/symcon/modules/TS_HeizungsTherme/imgs/Heizkennline.png"  );
			imagedestroy( $Image );		
	}
	
///////////////////////////////////////////////////////////////////////////////

	// PWM auf dem gewaehlten Pin
	public function Set_PWM(Int $value)
	{
        exec ('echo inversed  > /sys/class/pwm/pwmchip0/pwm0/polarity');
        $period = 1000000;
        exec ('echo '."$period".' > /sys/class/pwm/pwmchip0/pwm0/period');
        exec ('echo '."$value".' > /sys/class/pwm/pwmchip0/pwm0/duty_cycle');
	}

	// Dallas Bus
	public function read_dallas()
	{
      $wire = GetValue($this->GetIDForIdent("onewireId"));
      $zaehler = GetValue($this->GetIDForIdent("onewirezaehler"));
      $ds_id = explode(" ", $wire);
    	for ($i = 1; ; $i++)
    	{
    	   if ($i > $zaehler) {
    	        break;
          }
        $ds_id[$i]= trim($ds_id[$i]);
    		$temp = exec('cat /sys/bus/w1/devices/'.$ds_id[$i].'/w1_slave |grep t=');
    		$crc = exec('cat /sys/bus/w1/devices/'.$ds_id[$i].'/w1_slave | grep crc | sed "s/^.*\(...\)$/\1/"');
    		$temp = explode('t=',$temp);
          //The power-on reset value of the temperature register is +85°C
    		if($crc =="YES" and $temp[1] !== "-62" and $temp[1]  !== "85000") //Fehler raus, -1.2 Â°C ,85Â°C und CRC
    		{ 
    			$temp = $temp[1] / 1000;
    			$temp = round($temp,2);
  
				$id = $this->CreateVariableByName(IPS_GetParent($this->GetIDForIdent("onewireId")), $ds_id[$i], 2);
				SetValue($id,$temp);
			}
		}
	}

	public function create_dallas()
	{
		$zaehler = intval(exec('cat /sys/bus/w1/devices/w1_bus_master1/w1_master_slave_count'));
		SetValue($this->GetIDForIdent("onewireId"), "");
		SetValue($this->GetIDForIdent("onewirezaehler"), $zaehler);
		$datei = file("/sys/bus/w1/devices/w1_bus_master1/w1_master_slaves");
		$i=1;
		foreach($datei AS $dallas_id)
		 {
		   $ds_id[$i]= $dallas_id;
			$wire = GetValue($this->GetIDForIdent("onewireId"));
			$wire = "".$wire." ".$ds_id[$i];
			 SetValue($this->GetIDForIdent("onewireId"), $wire);
			$i++;
		 }

	//	Zweiter Dallas Bus
		$zaehler = $zaehler + (intval(exec('cat /sys/bus/w1/devices/w1_bus_master2/w1_master_slave_count')));
		SetValue($this->GetIDForIdent("onewirezaehler"), $zaehler);
		$datei = file("/sys/bus/w1/devices/w1_bus_master2/w1_master_slaves");
		$i=1;
		foreach($datei AS $dallas_id)
		 {
		   $ds_id[$i]= $dallas_id;
			$wire = GetValue($this->GetIDForIdent("onewireId"));
			$wire = "".$wire." ".$ds_id[$i];
			 SetValue($this->GetIDForIdent("onewireId"), $wire);
			$i++;
		 }
		 
		$ds_id = explode(" ", $wire);
		for ($i = 1; ; $i++)
		{
			if ($i > $zaehler) {
				break;
			}
			$ds_id[$i]= trim($ds_id[$i]);
			$temp = exec('cat /sys/bus/w1/devices/'.$ds_id[$i].'/w1_slave |grep t=');
			$crc = exec('cat /sys/bus/w1/devices/'.$ds_id[$i].'/w1_slave | grep crc | sed "s/^.*\(...\)$/\1/"');
			$temp = explode('t=',$temp);
			//The power-on reset value of the temperature register is +85°C
			if($crc =="YES" and $temp[1] !== "-62" and $temp[1]  !== "85000") //Fehler raus, -1.2 °C ,85°C und CRC
			{ 
				$temp = $temp[1] / 1000;
				//$temp = round($temp,1);
				$id = $this->CreateVariableByName(IPS_GetParent($this->GetIDForIdent("onewireId")), $ds_id[$i], 2);
				SetValue($id,$temp);
			}
		}
	}
	
	public function SetConfig() 
	{
	// Setzen von ersten Parametern beim anlegen.
		If (GetValueFloat($this->GetIDForIdent("Kesselmin")) == 0){SetValue($this->GetIDForIdent("Kesselmin"), 20);}
        If (GetValueFloat($this->GetIDForIdent("Kesselmax")) == 0){SetValue($this->GetIDForIdent("Kesselmax"), 70);}
        If (GetValueFloat($this->GetIDForIdent("Sommerumschaltung")) == 0){SetValue($this->GetIDForIdent("Sommerumschaltung"), 20);}
        If (GetValueFloat($this->GetIDForIdent("RaumSolltemperaturNacht"))== 0){SetValue($this->GetIDForIdent("RaumSolltemperaturNacht"), 18.5);}
        If (GetValueFloat($this->GetIDForIdent("RaumSolltemperatur"))== 0){SetValue($this->GetIDForIdent("RaumSolltemperatur"), 21.5);}
        If (GetValueFloat($this->GetIDForIdent("Steilheit")) == 0){SetValue($this->GetIDForIdent("Steilheit"), 1);}
        If (GetValueBoolean($this->GetIDForIdent("UhrHeizungKessel"))== 0){SetValue($this->GetIDForIdent("UhrHeizungKessel"), true);}
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

	private function RegisterProfileFloat($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $digits)
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
			IPS_SetVariableProfileDigits($Name, $digits);			
	}

	private function CreateVariableByName($id, $name, $type)
	{
		$vid = @IPS_GetVariableIDByName($name, $id);
		if($vid===false) {
			$vid = IPS_CreateVariable($type);
			IPS_SetParent($vid, $id);
			IPS_SetName($vid, $name);
		}
		return $vid;
	}  

	private function CreateWochenplan($name, $Typ, $Var)
	{
		$ParentID = $this->GetIDForIdent($Var);
		$EreignisID = @IPS_GetEventIDByName($name, $ParentID);
		if (!IPS_EventExists($EreignisID)) {
			$eid = IPS_CreateEvent($Typ);                  		//Wochplan
			IPS_SetParent($eid,($this->GetIDForIdent($Var)));   //Ereignis zuordnen
			//Anlegen von Gruppen
			IPS_SetEventScheduleGroup($eid, 1,1);
			IPS_SetEventScheduleGroup($eid, 2, 2);
			IPS_SetEventScheduleGroup($eid, 3, 4);
			IPS_SetEventScheduleGroup($eid, 4, 8);
			IPS_SetEventScheduleGroup($eid, 5, 16);
			IPS_SetEventScheduleGroup($eid, 6, 32);
			IPS_SetEventScheduleGroup($eid, 7, 64);
			//Anlegen von Aktionen 
			IPS_SetEventScheduleAction($eid, 1, "Tag", 0x00FF00, "SetValue(\$_IPS['TARGET'], 1);");
			IPS_SetEventScheduleAction($eid, 2, "Nacht", 0xFF0000, "SetValue(\$_IPS['TARGET'], 0);");	
			IPS_SetName($eid, $name);
			IPS_SetEventActive($eid, true);             //Ereignis aktivieren		
		}
	}

	private function CreateCategoryByName($id, $name)
	{
		$vid = @IPS_GetCategoryIDByName( $name, $id);
		if($vid===false) {
			$vid = IPS_CreateCategory();     
			IPS_SetParent($vid, $id);
			IPS_SetName($vid, $name);
		}
		return $vid;
	}  
	
	private function CreateLinkByName($id, $name, $varID)
	{
     $vid = @IPS_GetLinkIDByName( $name, $id);
		if($vid===false) {
			$vid = IPS_CreateLink();     
			IPS_SetParent($vid, $id);
			IPS_SetName($vid, $name);
			IPS_SetLinkTargetID($vid, $varID);    // Link verknüpfen
		}
     return $vid;
	}  
	
	private function CreateImageHeizkennline()
	{
     $ImageFile = @IPS_GetMediaIDByName("Heizkennline", IPS_GetInstanceIDByName("Heizkennline", 0) );
     if($ImageFile===false) {
		$ImageFile = "/var/lib/symcon/modules/TS_HeizungsTherme/imgs/Heizkennline.png";  // Image-Datei
		$MediaID = IPS_CreateMedia(1);           // Image im MedienPool anlegen
		IPS_SetMediaFile($MediaID, $ImageFile, true);    // Image im MedienPool mit Image-Datei verbinden     }
		IPS_SetName($MediaID, "Heizkennline");
     //return $vid;
	 }
	}  
	
}
