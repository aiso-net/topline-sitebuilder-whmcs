<?php
//-------------------------------------------------
// Global WHMCS Add-On Module Variables
//-------------------------------------------------
global $mstrModuleName,$mstrModuleLink;

$mstrModuleName = "sitebuilder";
$mstrModuleLink = "addonmodules.php?module=".$mstrModuleName;

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

Topline_load_language();

//-------------------------------------------------
// Main Functions For Module
//-------------------------------------------------
function Topline_DisplayProductDetailsLoginLink($intClientRID,$intServiceRID,$blnReturnIfLoginURLCanBeCreated = false)
{
	//-----------------------------
	// Check if service belongs to this customer
	//-----------------------------
	$table = "tblhosting";
	$fields = "packageid,server";
	$where = array("id"=>$intServiceRID,"userid"=>$intClientRID);
	$result = select_query($table,$fields,$where);
	$servicedata = mysql_fetch_array($result);
	$intPackageRID = $servicedata["packageid"];
	$intServerRID = $servicedata["server"];
	if(is_numeric($intPackageRID))
		$intPackageRID = (int)$intPackageRID;
	else
		$intPackageRID = 0;
	if($intPackageRID > 0)
	{
		//-----------------------------
		// See If WHMCS Product Is Tied To A Yola Bundle
		//-----------------------------
		$table = "mod_sitebuilder_bundlexproducts";
		$fields = "intRecordID,txtYolaBundleID";
		$where = array("tblproducts_id"=>$intPackageRID);
		$result = select_query($table,$fields,$where);
		$data = mysql_fetch_array($result);
		$intYolaProductBundleRID = $data['intRecordID'];
		$strYolaBundleIDSelected = $data['txtYolaBundleID'];
		if(is_numeric($intYolaProductBundleRID))
			$intYolaProductBundleRID = (int)$intYolaProductBundleRID;
		else
			$intYolaProductBundleRID = 0;
		if($intYolaProductBundleRID > 0)
		{
			//-----------------------------
			// Get Yola User ID From WHMCS
			//-----------------------------
			$strYolaUserID = Topline_GetYolaUserIDFromWHMCSService($intServiceRID);
			if(strlen($strYolaUserID) > 0)
			{
				//-----------------------------
				// Run Login Against The API
				//-----------------------------
				$aryModuleSettings = Topline_GetModuleSettings();
				if(!is_array($aryModuleSettings))
				{
					logModuleCall("sitebuilder","after_module_create","ERROR: Could Not Get Module Settings",$aryModuleSettings);
					if($blnReturnIfLoginURLCanBeCreated == true)
						return false;
					return Topline_DisplayErrorMessage("<script>alert('There was an issue getting the module settings, please contact us for help.');window.close();</script>");
				}
				if($blnReturnIfLoginURLCanBeCreated == true)
					return true;
				$Topline = new ToplineAPI;
				$Topline->SetPartnerAuthKey($aryModuleSettings[0]);
				$Topline->SetPartnerBrand($aryModuleSettings[1]);
				$Topline->TurnOnStatingMode($aryModuleSettings[3]);
				$strLoginURL = $Topline->GetTokenLoginURL($strYolaUserID);
				if(strlen($strLoginURL) > 0)
				{
					header("HTTP/1.1 302 Moved Temporarily");
					header("Location: $strLoginURL");
					exit;
				}else{
					$strErrorMessage = "<script>alert('There was a login issue trying to log you in to the site builder, please contact us for help.');window.close();</script>";
				}
			}else{
				logModuleCall("sitebuilder","Topline_DisplayProductDetailsLoginLink","ERROR: Could not get your site builder account information for SID: $intServiceRID, Yola ID Empty: $strYolaUserID");
				$strErrorMessage = "ERROR: Could not get your site builder account information, please contact us.";
			}
			if($blnReturnIfLoginURLCanBeCreated == true)
				return false;
			if($_GET['t'] == "2")
			{
				print $strErrorMessage;
				exit;
			}
			return Topline_DisplayErrorMessage($strErrorMessage);
		}else
			if($blnReturnIfLoginURLCanBeCreated == true)
				return false;
			else
				return Topline_DisplayErrorMessage("<script>alert('There was a issues trying to find your sitebuilder plan, please contact us for help.');window.close();</script>");
	}else
		if($blnReturnIfLoginURLCanBeCreated == true)
			return false;
		else
			return Topline_DisplayErrorMessage("<script>alert('There was a issues trying to find your hosting plan, please contact us for help.');window.close();</script>");
}
//-----------------------------------------------
// Get Yola User ID From WHMCS Service RID
//-----------------------------------------------
function Topline_GetYolaUserIDFromWHMCSService($intServiceRID)
{
	if(!is_numeric($intServiceRID))
		return "";
	$strYolaUserID = "";
	//-----------------------------
	// Get Serivce From WHMCS
	//-----------------------------
	$table = "tblhosting";
	$fields = "packageid,server";
	$where = array("id"=>$intServiceRID);
	$result = select_query($table,$fields,$where);
	$servicedata = mysql_fetch_array($result);
	$intPackageRID = $servicedata["packageid"];
	$intServerRID = $servicedata["server"];
	if(is_numeric($intPackageRID))
		$intPackageRID = (int)$intPackageRID;
	else
		$intPackageRID = 0;
	if($intPackageRID > 0)
	{
		//-----------------------------
		// See If WHMCS Product Is Tied To A Yola Bundle
		//-----------------------------
		$table = "mod_sitebuilder_bundlexproducts";
		$fields = "intRecordID,txtYolaBundleID";
		$where = array("tblproducts_id"=>$intPackageRID);
		$result = select_query($table,$fields,$where);
		$data = mysql_fetch_array($result);
		$intYolaProductBundleRID = $data['intRecordID'];
		$strYolaBundleIDSelected = $data['txtYolaBundleID'];
		if(is_numeric($intYolaProductBundleRID))
			$intYolaProductBundleRID = (int)$intYolaProductBundleRID;
		else
			$intYolaProductBundleRID = 0;
		if($intYolaProductBundleRID > 0)
		{
			//-----------------------------
			// Get Server Module Name
			//-----------------------------
			$table = "tblservers";
			$fields = "type";
			$where = array("id"=>$intServerRID);
			$result = select_query($table,$fields,$where);
			$data = mysql_fetch_array($result);
			$strServerModuleName = $data['type'];
			//-----------------------------	
			// Get Global Product Custom Field Names Used For Yola User ID
			//-----------------------------
			$table = "mod_sitebuilder_settings";
			$fields = "txtValue";
			$where = array("txtSetting"=>"txtGlobalYolaUserIDProductCustomFieldName");
			$result = select_query($table,$fields,$where);
			$globalcustomfieldnamesdata = mysql_fetch_array($result);
			//logModuleCall("sitebuilder","Topline_GetYolaUserIDFromWHMCSService","Global Product Custom Field Names Used For Yola User ID:",$globalcustomfieldnamesdata);
			//-----------------------------
			// Get Custom Module Product Custom Field Names Used For Yola User ID
			//-----------------------------
			$table = "mod_sitebuilder_modulexcustomfields";
			$fields = "intRecordID,txtYolaUserIDProductCustomFieldName";
			$where = array("txtModuleName"=>$strServerModuleName);
			$result = select_query($table,$fields,$where);
			$modulecustomfieldnamesdata = mysql_fetch_array($result);
			//logModuleCall("sitebuilder","Topline_GetYolaUserIDFromWHMCSService","Custom Module Product Custom Field Names Used For Yola User ID (Module Name: $strServerModuleName):",$modulecustomfieldnamesdata);
			if(is_numeric($modulecustomfieldnamesdata["intRecordID"]))
				$intModuleCustomFieldDataRID = (int)$modulecustomfieldnamesdata["intRecordID"];
			else
				$intModuleCustomFieldDataRID = 0;
			if($intModuleCustomFieldDataRID > 0)
			{
				// Use Module Custom Field Names
				$strYolaUserIDProductCustomFieldName = $modulecustomfieldnamesdata["txtYolaUserIDProductCustomFieldName"];
			}else{
				// Use Global Custom Field Names
				$strYolaUserIDProductCustomFieldName = $globalcustomfieldnamesdata["txtValue"];
			}
			//logModuleCall("sitebuilder","Topline_GetYolaUserIDFromWHMCSService","Custom Field Name To Get: $strYolaUserIDProductCustomFieldName");
			//-----------------------------
			// Do Work On The Custom Field Names To Determine If We Are Getting Values From WHMCS, Or Creating Our Own And Saving Them To WHMCS
			//-----------------------------
			if(strpos($strYolaUserIDProductCustomFieldName,"{") !== false && strpos($strYolaUserIDProductCustomFieldName,"}") !== false)
			{
				// Get The Custom Field Value From WHMCS
				preg_match("/\{([^\]]+)\}/", $strYolaUserIDProductCustomFieldName , $aryYolaUserIDProductCustomFieldNameMatches);
				$strYolaUserIDProductCustomFieldNameToLookup = $aryYolaUserIDProductCustomFieldNameMatches[1];
				$strYolaUserIDProductCustomFieldNameLookupResult = Topline_GetWHMCSCustomFieldValue($strYolaUserIDProductCustomFieldNameToLookup,$intPackageRID,$intServiceRID);
				$strYolaUserIDProductCustomFieldName = str_replace($aryYolaUserIDProductCustomFieldNameMatches[0],$strYolaUserIDProductCustomFieldNameLookupResult,$strYolaUserIDProductCustomFieldName);
			}else{
				// Get The Custom Field Value From WHMCS
				$strYolaUserIDProductCustomFieldName = Topline_GetWHMCSCustomFieldValue($strYolaUserIDProductCustomFieldName,$intPackageRID,$intServiceRID);
			}
			//-----------------------------
			// Assign The Yola User ID From The Custom Field Value From WHMCS To The Variable
			//-----------------------------
			$strYolaUserID = $strYolaUserIDProductCustomFieldName;
		}
	}
	return $strYolaUserID;
}
//-----------------------------------------------
// Get FTP Username And Password Via Topline Custom Field Variables As Well As Optional Yola User ID
//-----------------------------------------------
function Topline_GetCustomFTPLoginInfo($intPackageRID,$intServiceRID,$blnIncludeYolaUserID = false)
{
	//---------------
	// Get Global Product Custom Field Names Used For Yola User ID
	//---------------
	$globalcustomfieldnamesdata = Topline_GetGlobalModuleSetting("txtGlobalYolaUserIDProductCustomFieldName,txtGlobalYolaFTPUsernameProductCustomFieldName,txtGlobalYolaFTPPasswordProductCustomFieldName");
	//-----------------------------
	// Get Custom Module Product Custom Field Names Used For Username & FTP Info Storage From DB
	//-----------------------------
	$table = "mod_sitebuilder_modulexcustomfields";
	$fields = "intRecordID,txtYolaUserIDProductCustomFieldName,txtYolaFTPUsernameProductCustomFieldName,txtYolaFTPPasswordProductCustomFieldName";
	$where = array("txtModuleName"=>$strServerModuleName);
	$result = select_query($table,$fields,$where);
	$modulecustomfieldnamesdata = mysql_fetch_array($result);
	if(is_numeric($modulecustomfieldnamesdata["intRecordID"]))
		$intModuleCustomFieldDataRID = (int)$modulecustomfieldnamesdata["intRecordID"];
	else
		$intModuleCustomFieldDataRID = 0;
	if($intModuleCustomFieldDataRID > 0)
	{
		// Use Module Custom Field Names
		$strYolaUserIDProductCustomFieldName = $modulecustomfieldnamesdata["txtYolaUserIDProductCustomFieldName"];
		$strYolaFTPUsernameProductCustomFieldName = $modulecustomfieldnamesdata["txtYolaFTPUsernameProductCustomFieldName"];
		$strYolaFTPPasswordProductCustomFieldName = $modulecustomfieldnamesdata["txtYolaFTPPasswordProductCustomFieldName"];
	}else{
		// Use Global Custom Field Names
		$strYolaUserIDProductCustomFieldName = $globalcustomfieldnamesdata["txtGlobalYolaUserIDProductCustomFieldName"];
		$strYolaFTPUsernameProductCustomFieldName = $globalcustomfieldnamesdata["txtGlobalYolaFTPUsernameProductCustomFieldName"];
		$strYolaFTPPasswordProductCustomFieldName = $globalcustomfieldnamesdata["txtGlobalYolaFTPPasswordProductCustomFieldName"];
	}
	logModuleCall("sitebuilder","Topline_GetCustomFTPLoginInfo","Function Settings Before Variable Change: User ID PCFN: " . $strYolaUserIDProductCustomFieldName . ", Topline FTP Username PCFN: " . $strYolaFTPUsernameProductCustomFieldName . ", Topline FTP Password PCFN: " . $strYolaFTPPasswordProductCustomFieldName);
	//-----------------------------
	// Do Work On The Custom Field Names To Determine If We Are Getting Values From WHMCS, Or Creating Our Own And Saving Them To WHMCS
	//-----------------------------
	if(strpos($strYolaUserIDProductCustomFieldName,"{") !== false && strpos($strYolaUserIDProductCustomFieldName,"}") !== false)
	{
		// Get The Custom Field Value From WHMCS
		preg_match("/\{([^\]]+)\}/", $strYolaUserIDProductCustomFieldName , $aryYolaUserIDProductCustomFieldNameMatches);
		$strYolaUserIDProductCustomFieldNameToLookup = $aryYolaUserIDProductCustomFieldNameMatches[1];
		$strYolaUserIDProductCustomFieldNameLookupResult = Topline_GetWHMCSCustomFieldValue($strYolaUserIDProductCustomFieldNameToLookup,$intPackageRID,$intServiceRID);
		$strYolaUserIDProductCustomFieldName = str_replace($aryYolaUserIDProductCustomFieldNameMatches[0],$strYolaUserIDProductCustomFieldNameLookupResult,$strYolaUserIDProductCustomFieldName);
	}else{
		// Get The Custom Field Value From WHMCS
		$strYolaUserIDProductCustomFieldName = Topline_GetWHMCSCustomFieldValue($strYolaUserIDProductCustomFieldName,$intPackageRID,$intServiceRID);
	}
	if(strpos($strYolaFTPUsernameProductCustomFieldName,"{") !== false && strpos($strYolaFTPUsernameProductCustomFieldName,"}") !== false)
	{
		// Get The Custom Field Value From WHMCS
		preg_match("/\{([^\]]+)\}/", $strYolaFTPUsernameProductCustomFieldName , $aryYolaFTPUsernameProductCustomFieldNameMatches);
		$strYolaFTPUsernameProductCustomFieldNameToLookup = $aryYolaFTPUsernameProductCustomFieldNameMatches[1];
		$strYolaFTPUsernameProductCustomFieldNameLookupResult = Topline_GetWHMCSCustomFieldValue($strYolaFTPUsernameProductCustomFieldNameToLookup,$intPackageRID,$intServiceRID);
		$strYolaFTPUsernameProductCustomFieldName = str_replace($aryYolaFTPUsernameProductCustomFieldNameMatches[0],$strYolaFTPUsernameProductCustomFieldNameLookupResult,$strYolaFTPUsernameProductCustomFieldName);
	}else{
		// Get The Custom Field Value From WHMCS
		$strYolaFTPUsernameProductCustomFieldName = Topline_GetWHMCSCustomFieldValue($strYolaFTPUsernameProductCustomFieldName,$intPackageRID,$intServiceRID);
	}
	if(strpos($strYolaFTPPasswordProductCustomFieldName,"{") !== false && strpos($strYolaFTPPasswordProductCustomFieldName,"}") !== false)
	{
		// Get The Custom Field Value From WHMCS
		preg_match("/\{([^\]]+)\}/", $strYolaFTPPasswordProductCustomFieldName , $aryYolaFTPPasswordProductCustomFieldNameMatches);
		$strYolaFTPPasswordProductCustomFieldNameToLookup = $aryYolaFTPPasswordProductCustomFieldNameMatches[1];
		$strYolaFTPPasswordProductCustomFieldNameLookupResult = Topline_GetWHMCSCustomFieldValue($strYolaFTPPasswordProductCustomFieldNameToLookup,$intPackageRID,$intServiceRID);
		$strYolaFTPPasswordProductCustomFieldName = str_replace($aryYolaFTPPasswordProductCustomFieldNameMatches[0],$strYolaFTPPasswordProductCustomFieldNameLookupResult,$strYolaFTPPasswordProductCustomFieldName);
	}else{
		// Get The Custom Field Value From WHMCS
		$strYolaFTPPasswordProductCustomFieldName = Topline_GetWHMCSCustomFieldValue($strYolaFTPPasswordProductCustomFieldName,$intPackageRID,$intServiceRID);
	}
	if(strlen($strYolaFTPPasswordProductCustomFieldName) < 1) {
		$servicedata = mysql_fetch_array(select_query("tblhosting","password",array("id"=>$intServiceRID)));
		$strYolaFTPPasswordProductCustomFieldName = decrypt($servicedata[0]);
	}
	logModuleCall("sitebuilder","Topline_GetCustomFTPLoginInfo","Function Settings After Variable Change: User ID PCFN: " . $strYolaUserIDProductCustomFieldName . ", Topline FTP Username PCFN: " . $strYolaFTPUsernameProductCustomFieldName . ", Topline FTP Password PCFN: " . $strYolaFTPPasswordProductCustomFieldName);
	//-----------------------------
	// Assign The Yola User ID From The Custom Field Value From WHMCS To The Variable
	//-----------------------------
	$strYolaUserID = $strYolaUserIDProductCustomFieldName;
	$strNewFTPUsername = $strYolaFTPUsernameProductCustomFieldName;
	$strNewFTPPassword = $strYolaFTPPasswordProductCustomFieldName;
	if($blnIncludeYolaUserID == true)
	{
		logModuleCall("sitebuilder","Topline_GetCustomFTPLoginInfo","Function return results: $strNewFTPUsername - $strNewFTPPassword - $strYolaUserID","");
		return Array($strNewFTPUsername,$strNewFTPPassword,$strYolaUserID);
	}else{
		logModuleCall("sitebuilder","Topline_GetCustomFTPLoginInfo","Function return results: $strNewFTPUsername - $strNewFTPPassword","");
		return Array($strNewFTPUsername,$strNewFTPPassword);
	}
}
//-----------------------------------------------
// Get Custom Server FTP Settings
//-----------------------------------------------
function Topline_GetCustomFTPServerSettings($intServerRID,$intServiceRID)
{
	if(!is_numeric($intServerRID))
		return array();
	if(!is_numeric($intServiceRID))
		return array();
	logModuleCall("sitebuilder","Topline_GetCustomFTPServerSettings","Function call with Server ID: $intServerRID, Service ID: $intServiceRID","");
	//-----------------------------	
	// Get Global Product Custom Field Names Used For Username & FTP Info Storage From DB & Global Server Settings
	//-----------------------------
	$globalcustomfieldnamesdata = Topline_GetGlobalModuleSetting("txtGlobalFTPHostname,txtGlobalFTPHomeDirectory,txtGlobalFTPPort,txtGlobalFTPMode");
	//-----------------------------
	$table = "mod_sitebuilder_servers";
	$fields = "intRecordID,txtFTPHostname,txtFTPHomeDirectory,intFTPPort,intFTPMode";
	$where = array("tblservers_id"=>$intServerRID);
	$result = select_query($table,$fields,$where);
	$servercustomsettingsdata = mysql_fetch_array($result);
	if(is_numeric($servercustomsettingsdata["intRecordID"]))
		$intServerCustomSettingsRID = (int)$servercustomsettingsdata["intRecordID"];
	else
		$intServerCustomSettingsRID = 0;
	if($intServerCustomSettingsRID > 0)
	{
		// User Server Settings
		$strServerFTPIPAddress = $servercustomsettingsdata["txtFTPHostname"];
		$strFTPDirectory = $servercustomsettingsdata["txtFTPHomeDirectory"];
		$strFTPPort = $servercustomsettingsdata["intFTPPort"];
		$intFTPMode = $servercustomsettingsdata["intFTPMode"];
	}else{
		// Use Global Settings
		$strServerFTPIPAddress = $globalcustomfieldnamesdata["txtGlobalFTPHostname"];
		$strFTPDirectory = $globalcustomfieldnamesdata["txtGlobalFTPHomeDirectory"];
		$strFTPPort = $globalcustomfieldnamesdata["txtGlobalFTPPort"];
		$intFTPMode = $globalcustomfieldnamesdata["txtGlobalFTPMode"];
	}
	//--------------
	// Get Service Info From WHMCS
	//--------------
	$data = mysql_fetch_array(select_query("tblhosting","domain,username",array("id"=>$intServiceRID)));
	$strDomainName = $data["domain"];
	$strServiceUsername = $data["username"];
	//--------------
	// Get Server Info From WHMCS
	//--------------
	$data = mysql_fetch_array(select_query("tblservers","ipaddress",array("id"=>$intServerRID)));
	$strServerIPAddress = $data["ipaddress"];
	//-----------------------------
	// Make the changes to the settings if there is a variable in it
	//-----------------------------
	$strServerFTPIPAddress = str_replace('$serverip',$strServerIPAddress,$strServerFTPIPAddress);
	$strServerFTPIPAddress = str_replace('$domainname',$strDomainName,$strServerFTPIPAddress);
	$strServerFTPIPAddress = str_replace('$username',$strServiceUsername,$strServerFTPIPAddress);
	$strFTPDirectory = str_replace('$domainname',$strDomainName,$strFTPDirectory);
	$strFTPDirectory = str_replace('$username',$strServiceUsername,$strFTPDirectory);
	logModuleCall("sitebuilder","Topline_GetCustomFTPServerSettings","Function return results: $strServerFTPIPAddress - $strFTPDirectory - $strFTPPort - $intFTPMode","");
	return Array($strServerFTPIPAddress,$strFTPDirectory,$strFTPPort,$intFTPMode);
}



function Topline_GetGlobalModuleSetting($strSettingName,$blnJustReturnValue = false)
{
	if(strpos($strSettingName,",") === false)
	{
		$table = "mod_sitebuilder_settings";
		$fields = "txtValue";
		$where = array("txtSetting"=>$strSettingName);
		$result_rows = select_query($table,$fields,$where);
		$result = mysql_fetch_array($result_rows);
		if($blnJustReturnValue == false)
			if(isset($result["txtValue"]))
				return Array("$strSettingName"=>$result["txtValue"]);
			else
				return Array("$strSettingName"=>"");
		else
			if(isset($result["txtValue"]))
				return $result["txtValue"];
			else
				return "";
	}else{
		$arySettingNamesToGet = explode(",",$strSettingName);
		$arySettingsNameValuesToReturn = Array();
		foreach($arySettingNamesToGet as $strSettingNameToGet)
		{
			$table = "mod_sitebuilder_settings";
			$fields = "txtValue";
			$where = array("txtSetting"=>$strSettingNameToGet);
			$result_rows = select_query($table,$fields,$where);
			$result = mysql_fetch_array($result_rows);
			if(isset($result["txtValue"]))
				$arySettingsNameValuesToReturn[$strSettingNameToGet] = $result["txtValue"];
			else
				$arySettingsNameValuesToReturn[$strSettingNameToGet] = "";
		}
		return $arySettingsNameValuesToReturn;
	}
}

function Topline_DisplayErrorMessage($strErrorMessage)
{
	return array(
		'pagetitle' => 'Sitebuilder',
		'breadcrumb' => array('index.php?m=sitebuilder'=>'Sietbuilder'),
		'templatefile' => 'templates/message',
		'requirelogin' => true,
		'vars' => array(
			'strOnlyMessage' => $strErrorMessage
		),
	);
}

function Topline_GetClientAreaProductLoginLinkHTML()
{
	$line = "";
	if (file_exists(dirname(__FILE__) . "/templates/editsitelinkhtml.tpl")) {
		$file_handle = fopen(dirname(__FILE__) . "/templates/editsitelinkhtml.tpl", "r");
		while (!feof($file_handle)) {
			$line = $line . fgets($file_handle);
		}
		fclose($file_handle);
	}
	return $line;
}

function Topline_ConvertCPanelXMLToArray($xmldata)
{
	$xmldata = Topline_CleanUpFrontOfXML($xmldata);
	$xmldata = str_replace('<?xml version="1.0" ?>','',$xmldata);
	return Topline_xml2array($xmldata);
}

function Topline_CleanUpFrontOfXML($xmldata)
{
	do {
		if(Ord(substr($xmldata,0,1))==10) {
			$xmldata = substr($xmldata,1);
			continue;
		}
		elseif(Ord(substr($xmldata,0,1))==13) {
			$xmldata = substr($xmldata,1);
			continue;
		}
		break;
	} while (true);
	return $xmldata;
}

function Topline_db_escape($input)
{
	if(is_int($input))
	{
		return (int)$input;
	}
	elseif(is_float($input))
	{
		return (float)$input;
	}
	else
	{
		return mysql_real_escape_string($input);
	}
}

function Topline_find_in_array($string, $array = array ())
{
	if(!isset($mblnTurnOffStrToLowerForFinding))
		$mblnTurnOffStrToLowerForFinding = False;
	if(($mblnTurnOffStrToLowerForFinding == False) && (!is_numeric($string)))
		$string = strtolower($string);
	$result = false;
	if(in_array($string,$array) == true)
		return true;
	foreach ($array as $key => $value) {
		//unset ($array[$key]);
		if(($mblnTurnOffStrToLowerForFinding == False) && (!is_numeric($value)))
			$value = strtolower($value);
		if (strpos($value, $string) !== false) {
			//$array[$key] = $value;
			$result = true;
			break;
		}
		if((is_numeric($value)) && (is_numeric($string))) {
			if((int)$string == (int)$value) {
				$result = true;
				break;
			}
		}
	}       
	return $result;
}

function Topline_xml2array($contents, $get_attributes=1, $priority = 'tag', $blnSetAttributesToCustomCode = false) {
    //Arguments : $contents - The XML text
    //            $get_attributes - 1 or 0. If this is 1 the function will get the attributes as well as the tag values - this results in a different array structure in the return value.
    //            $priority - Can be 'tag' or 'attribute'. This will change the way the resulting array sturcture. For 'tag', the tags are given more importance.
    //Return: The parsed XML in an array form. Use print_r() to see the resulting array structure
    if(!$contents) return array();
    if(!function_exists('xml_parser_create')) {
        //print "'xml_parser_create()' function not found!";
       	return array();
    }
    //Get the XML parser of PHP - PHP must have this module for the parser to work
    $parser = xml_parser_create('');
    xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    xml_parse_into_struct($parser, trim($contents), $xml_values);
    xml_parser_free($parser);	
    if(!$xml_values) return;//Hmm...
    //Initializations
    $xml_array = array();
    $parents = array();
    $opened_tags = array();
    $arr = array();
    $current = &$xml_array; //Refference
    //Go through the tags.
    $repeated_tag_index = array();//Multiple tags with same name will be turned into an array
    foreach($xml_values as $data) {
        unset($attributes,$value);//Remove existing values, or there will be trouble
        //This command will extract these variables into the foreach scope
       	// tag(string), type(string), level(int), attributes(array).
        extract($data);//We could use the array by itself, but this cooler.
       	$result = array();
	if($blnSetAttributesToCustomCode == false)
	        $attributes_data = array();
       	if(isset($value)) {
            if($priority == 'tag') $result = $value;
       	    else $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
        }
        //Set the attributes too.
       	if(isset($attributes) and $get_attributes) {
            foreach($attributes as $attr => $val) {
               	if($priority == 'tag') {
			$attributes_data[$attr] = $val;
		} else {
			$result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
		}
       	    }
        }
        //See tag status and do the needed.
       	if($type == "open") {//The starting of the tag '<tag>'
            $parent[$level-1] = &$current;
       	    if(!is_array($current) or (!Topline_find_in_array($tag, array_keys($current)))) { //Insert New tag
                $current[$tag] = $result;
                if($attributes_data) {
			if($blnSetAttributesToCustomCode == false)
				$current[$tag. '_attr'] = $attributes_data;
		}
                $repeated_tag_index[$tag.'_'.$level] = 1;
                $current = &$current[$tag];
       	    } else { //There was another element with the same tag name
                if(isset($current[$tag][0])) { //If there is a 0th element it is already an array
       	            $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
                    $repeated_tag_index[$tag.'_'.$level]++;
               	} else { //This section will make the value an array if multiple tags with the same name appear together
       	            $current[$tag] = array($current[$tag],$result); //This will combine the existing item and the new item together to make an array
                    $repeated_tag_index[$tag.'_'.$level] = 2;
                    if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
               	        $current[$tag]['0_attr'] = $current[$tag.'_attr'];
       	                unset($current[$tag.'_attr']);
                    }
                }
               	$last_item_index = $repeated_tag_index[$tag.'_'.$level]-1;
       	        $current = &$current[$tag][$last_item_index];
            }
        } elseif($type == "complete") { //Tags that ends in 1 line '<tag />'
       	    //See if the key is already taken.
            if(!isset($current[$tag])) { //New Key
               	$current[$tag] = $result;
       	        $repeated_tag_index[$tag.'_'.$level] = 1;
                if($priority == 'tag' and $attributes_data) {
			$current[$tag . '_attr'] = $attributes_data;
      				if($blnSetAttributesToCustomCode != false)
		        	$attributes_data = array();
		}
       	    } else { //If taken, put all things inside a list(array)
                if(isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array...
       	            // ...push the new element into that array.
                    $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
               	    if($priority == 'tag' and $get_attributes and $attributes_data) {
       	                $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
			if($blnSetAttributesToCustomCode != false)
		        	$attributes_data = array();
       	            }
                    $repeated_tag_index[$tag.'_'.$level]++;
                } else { //If it is not an array...
               	    $current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
       	            $repeated_tag_index[$tag.'_'.$level] = 1;
                    if($priority == 'tag' and $get_attributes) {
                       	if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well       
               	            $current[$tag]['0_attr'] = $current[$tag.'_attr'];
       	                    unset($current[$tag.'_attr']);
                        }                
                        if($attributes_data) {
				$current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
      					if($blnSetAttributesToCustomCode != false)
			        	$attributes_data = array();
       	                }
                    }
               	    $repeated_tag_index[$tag.'_'.$level]++; //0 and 1 index is already taken
       	        }
            }
       	} elseif($type == 'close') { //End of tag '</tag>'
            $current = &$parent[$level-1];
       	}
    }
    return($xml_array);
}

function Topline_human_filesize($bytes, $decimals = 2,$strAddSpaceBetweenNumbersAndType = false) {
	$sz = 'BKMGTP';
	$factor = floor((strlen($bytes) - 1) / 3);
	$strResult = sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
	if($strAddSpaceBetweenNumbersAndType == true)
		$strResult = substr($strResult,0,strlen($strResult)-1).' '.substr($strResult,-1);
	if(substr($strResult,-1) != "B")
		$strResult .= "B";
	return $strResult;
}
//-----------------------------------------------------------
// Advanced DateAdd Function
//-----------------------------------------------------------
function Topline_dateadd($givendate,$day=0,$mth=0,$yr=0,$blnIncludeTime=true,$strReturnedDateFormat='Y-m-d',$strReturnedTimeFormat='h:i:s')
{
	if(!is_numeric($givendate))
		$cd = strtotime($givendate);
	else
		$cd = $givendate;
	if($blnIncludeTime == true)
	{
		$strDateTimeFormat = $strReturnedDateFormat . ' ' . $strReturnedTimeFormat;
		$newdate = date($strDateTimeFormat, mktime(date('h',$cd),date('i',$cd), date('s',$cd), date('m',$cd)+$mth,date('d',$cd)+$day, date('Y',$cd)+$yr));
	}else
		$newdate = date($strReturnedDateFormat, mktime(date('h',$cd),date('i',$cd), date('s',$cd), date('m',$cd)+$mth,date('d',$cd)+$day, date('Y',$cd)+$yr));
	return $newdate;
}
//-----------------------------------------------------------
// Generate A Random Password
//-----------------------------------------------------------
function Topline_GenerateRandomPassword($length = 8,$useallcharacters = false)
{
	// start with a blank password
	$password = "";

	// define possible characters - any character in this string can be
	// picked for use in the password, so if you want to put vowels back in
	// or add special characters such as exclamation marks, this is where
	// you should do it
	if($useallcharacters == false)
		$possible = "2346789bcdfghjkmnpqrtvwxyzBCDFGHJKLMNPQRTVWXYZ";
	else
		$possible = "012346789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

	// we refer to the length of $possible a few times, so let's grab it now
	$maxlength = strlen($possible);
  
	// check for length overflow and truncate if necessary
	if ($length > $maxlength) {
		$length = $maxlength;
	}
	
	// set up a counter for how many characters are in the password so far
	$i = 0; 
	
	// add random characters to $password until $length is reached
	while ($i < $length) { 
		// pick a random character from the possible ones
		$char = substr($possible, mt_rand(0, $maxlength-1), 1);
		
		// have we already used this character in $password?
		if (!strstr($password, $char)) { 
			// no, so it's OK to add it onto the end of whatever we've already got...
			$password .= $char;
			// ... and increase the counter by one
			$i++;
		}
	}
	// done!
	return $password;
}
//-----------------------------------------------------------
// Save WHMCS Product Custom Field Value
//-----------------------------------------------------------
function Topline_SaveWHMCSCustomFieldValue($strFieldName,$intPackageRID,$intServiceRID,$strNewValue)
{
	$table = "tblcustomfields";
	$fields = "id";
	$where = array("fieldname"=>$strFieldName,"relid"=>$intPackageRID);
	$result = select_query($table,$fields,$where);
	$data = mysql_fetch_array($result);
	$intCustomFieldRID = $data[0];
	if(!empty($intCustomFieldRID))
	{
		$table = "tblcustomfieldsvalues";
		$fields = "relid,value";
		$where = array("relid"=>$intServiceRID,"fieldid"=>$intCustomFieldRID);
		$findresult = select_query($table,$fields,$where);
		$finddata = mysql_fetch_array($findresult);
		$strCustomFieldValueServiceID = $finddata[0];
		if(!is_numeric($strCustomFieldValueServiceID))
			$strCustomFieldValueServiceID = 0;
		else
			$strCustomFieldValueServiceID = (float)$strCustomFieldValueServiceID;
		if($strCustomFieldValueServiceID > 0)
		{
			$table = "tblcustomfieldsvalues";
			$array = array("value"=>$strNewValue);
			$where = array("relid"=>$intServiceRID,"fieldid"=>$intCustomFieldRID);
			update_query($table,$array,$where);
			return true;
		}else{
			$table = "tblcustomfieldsvalues";
			$values = array("relid"=>$intServiceRID,"fieldid"=>$intCustomFieldRID,"value"=>$strNewValue);
			$newid = insert_query($table,$values);
			return true;
		}
	}else{
		return false;
	}
}
//-----------------------------------------------------------
// Get WHMCS Product Custom Field Value
//-----------------------------------------------------------
function Topline_GetWHMCSCustomFieldValue($strFieldName,$intPackageRID,$intServiceRID)
{
	logModuleCall("sitebuilder","Topline_GetWHMCSCustomFieldValue","$strFieldName, $intPackageRID, $intServiceRID");
	$data = mysql_fetch_array(select_query("tblcustomfields","id",array("fieldname"=>$strFieldName,"relid"=>$intPackageRID)));
	$intCustomFieldRID = $data[0];
	if(!empty($intCustomFieldRID))
	{
		$data = mysql_fetch_array(select_query("tblcustomfieldsvalues","value",array("relid"=>$intServiceRID,"fieldid"=>$intCustomFieldRID)));
		logModuleCall("sitebuilder","Topline_GetWHMCSCustomFieldValue Return",$data[0]);
		return $data[0];
	}
}
//-----------------------------------------------------------
// Get Module Settings From DB
//-----------------------------------------------------------
function Topline_GetModuleSettings()
{
	global $mstrModuleName;
	$table = "tbladdonmodules";
	$fields = "value";
	$where = array("module"=>$mstrModuleName,"setting"=>"PartnerAuthKey");
	$result = select_query($table,$fields,$where);
	$modulesettings = mysql_fetch_array($result);
	$strParterGUID = $modulesettings["value"];

	$table = "tbladdonmodules";
	$fields = "value";
	$where = array("module"=>$mstrModuleName,"setting"=>"BrandID");
	$result = select_query($table,$fields,$where);
	$modulesettings = mysql_fetch_array($result);
	$strParterID = $modulesettings["value"];

	$table = "tbladdonmodules";
	$fields = "value";
	$where = array("module"=>$mstrModuleName,"setting"=>"DeleteDBTablesOnUninstall");
	$result = select_query($table,$fields,$where);
	$modulesettings = mysql_fetch_array($result);
	$strDeleteDBTablesOnUninstall = $modulesettings["value"];
	if(strlen(trim($strDeleteDBTablesOnUninstall)) == 0)
		$strDeleteDBTablesOnUninstall = "no";

	$table = "tbladdonmodules";
	$fields = "value";
	$where = array("module"=>$mstrModuleName,"setting"=>"StagingMode");
	$result = select_query($table,$fields,$where);
	$modulesettings = mysql_fetch_array($result);
	$strStagingMode = $modulesettings["value"];
	return Array($strParterGUID,$strParterID,$strDeleteDBTablesOnUninstall,$strStagingMode);
}
//-------------------------------------------------
// Convert SimpleXML object into php array
//-------------------------------------------------
function Topline_SimpleXML2Array($xmlObject,$out = array())
{
	foreach((array)$xmlObject as $index => $node)
	{
		$out[$index] = ( is_object ( $node ) ) ? xml2array ( $node ) : $node;
	}
	return $out;
}

//-------------------------------------------------
// From yola api class file
//-------------------------------------------------
class ToplineAPI {
	private $curl;
	private $response ="";
	private $headers = array();
	private $url;
	private $blnStagingMode = false;
	private $strPartnerAuthKey;
	private $strPartnerBrand;
	private $strStagingAPIURL = 'https://sandbox.sbsapi.com';
	private $strProductionAPIURL = 'https://sbsapi.com';
	
	/**
	* Constructor, sets default options
	*/
	public function __construct() {
	}

	public function TurnOnStatingMode($strSetValue) {
		if(is_bool($strSetValue)) {
			$this->blnStagingMode = (bool)$strSetValue;
		}
		elseif(is_numeric($strSetValue)) {
			if((int)$strSetValue == 1)
				$this->blnStagingMode = true;
			else
				$this->blnStagingMode = false;
		}
		elseif(strtolower($strSetValue) == "yes" || $strSetValue == "true" || $strSetValue == "on") {
			$this->blnStagingMode = true;
		}else{
			$this->blnStagingMode = false;
		}
	}

	public function SetPartnerAuthKey($strAuthKey) {
		$this->strPartnerAuthKey = $strAuthKey;
	}

	public function SetPartnerBrand($strBrand) {
		$this->strPartnerBrand = $strBrand;
	}

	private function CallService($strAction,$intCallType,$aryFields = "")
	{
		//logModuleCall("sitebuilder","ToplineAPI:CallService","Calling $strAction with Call Type: $intCallType, with fields:",$aryFields);

		if(is_array($aryFields)) {
			$strContent = json_encode($aryFields);
			$contentMD5 = md5($strContent);
			$contentType = "application/json; charset=UTF-8";
		}else{
			$contentMD5 = "";
			$contentType = "";
		}

		if($this->blnStagingMode == false)
			$url = $this->strProductionAPIURL . "/" . $this->strPartnerBrand . $strAction;
		else
			$url = $this->strStagingAPIURL . "/" . $this->strPartnerBrand . $strAction;
		$this->url = $url;

		$apicurl = new ToplineAPICurl;
		$apicurl->create($url);

		// $intCallType = 1 = Get, 2 = Post, 3 = Put, 4 = Delete , 5 = File Upload
		if($intCallType == 1) {
			$httpVerb = "GET";
			$apicurl->get("");
		}
		elseif($intCallType ==2) {
			$httpVerb = "POST";
			$apicurl->post($strContent);
		}
		elseif($intCallType == 3) {
			$httpVerb = "PUT";
			$apicurl->put($strContent);
		}
		elseif($intCallType == 4) {
			$httpVerb = "DELETE";
			$contentType = "application/x-www-form-urlencoded";
			$apicurl->delete("");
		}


		$strExpires = (time()*1000) + (1000*5*60);	// 5 minutes (must be in milliseconds)
		$stringToSign = $httpVerb . "\n" . $contentMD5 . "\n" . $contentType . "\n" . $strExpires . "\n";

		$apicurl->http_header("SBS-Expires",$strExpires);
		$apicurl->http_header("SBS-Signature",(base64_encode(hash_hmac("sha1", $stringToSign, $this->strPartnerAuthKey, true))));
		$apicurl->http_header("SBS-AgentID","AISO_TLAPI_1.0");
		if(strlen($contentType) > 0)
			$apicurl->http_header("Content-Type",$contentType);

		// Prevent a 303 follow on curl for SSO login
		if(strpos(strtolower($strAction),"yolalogin") !== false) {
			$apicurl->options(array(CURLOPT_FOLLOWLOCATION => false));
		}

		$strReturnData = $apicurl->execute();
		//if(strlen($apicurl->error_string) > 0)
		//print("Curl Error:".$apicurl->error_string);
		$aryResponseData = $apicurl->GetInfo();
		$intResponseCode = (int)$aryResponseData["http_code"];
		$aryResponseData["response_headers"] = $apicurl->getResponseHeaders(true);
		//print_r($apicurl->getResponseHeaders(true));
		//print_r($apicurl->getRequestHeaders());
		unset($apicurl);
		//logModuleCall("sitebuilder","ToplineAPI:CallService","Calling $strAction with Call Type: $intCallType, with results:",$aryResponseData);
		return array($intResponseCode,$strReturnData,$aryResponseData);
	}

	public function AddNewCustomer($strUserID,$strPassword,$strFirstName = "",$strLastName = "",$strEmail = "",$strPhone = "",$strFTPAddress,$strFTPUsername,$strFTPPassword,$intFTPPort = 21,$strFTPWWWRoot,$intFTPMode,$strDomain,$strPlanID,$strWHMCSServiceRID = "",$intFTPProtocol = 1,$intStatus = 1)
	{
		// $intStatus = 1 = Active (old system & new system)
		// $intStatus = 2 = Trial (old system)
		// $intStatus = 3 = Suspended (new system)
		// $intStatus = 5 = Deleted (new system)

		// First Create The User
		if($intStatus < 1 || $intStatus > 5 || $intStatus == 3 || $intStatus == 5) {
			logModuleCall("sitebuilder","ToplineAPI:AddNewCustomer:AddUser ERROR: Invalid User Status, Cannot Be Suspended Or Deleted During Creation", "","");
			return false;
		}

		if($intFTPMode == 1)
			$intFTPMode = "Active";
		else
			$intFTPMode = "Passive";
		if(!is_numeric($intFTPProtocol))
			$intFTPProtocol = 1;
		$intFTPProtocol = (int)$intFTPProtocol;
		if($intFTPProtocol < 1 || $intFTPProtocol > 3)
			$intFTPProtocol = 1;

		$aryFields["userID"] = $strUserID;
		$aryFields["domain"] = $strDomain;
		$aryFields["firstName"] = $strFirstName;
		$aryFields["lastName"] = $strLastName;
		$aryFields["email"] = $strEmail;
		if(strlen($strPhone) > 0)
			$aryFields["phone"] = $strPhone;
		$aryFields["password"] = $strPassword;
		//$aryFields["countryCode"] = "";
		//$aryFields["currency"] = "";
		//$aryFields["language"] = "";
		//$aryFields["timezone"] = "";
		if($intStatus == 2) {
			if(strlen($strFTPAddress) < 1)
				$strFTPAddress = "temp.local";
			if(strlen($strFTPUsername) < 1)
				$strFTPUsername = "tempuser";
			if(strlen($strFTPPassword) < 1)
				$strFTPPassword = "temppassw0rd";
			if(strlen($intFTPPort) < 1)
				$intFTPPort = 21;
			if(strlen($strFTPWWWRoot) < 1)
				$strFTPWWWRoot = "/";
		}
		$aryFields["ftpAddress"] = $strFTPAddress;
		$aryFields["ftpUserid"] = $strFTPUsername;
		$aryFields["ftpPassword"] = $strFTPPassword;
		$aryFields["ftpPort"] = $intFTPPort;
		$aryFields["ftpWwwroot"] = $strFTPWWWRoot;
		$aryFields["ftpProtocol"] = $intFTPProtocol;
		$aryFields["fdifm"] = 0;	// ??
		$aryFields["host1"] = $strWHMCSServiceRID;

		list($intResponse,$strResults,$aryAllData) = $this->CallService("/users",2,$aryFields);
		$aryCreateUserResultsData = json_decode($strResults,true);
		logModuleCall("sitebuilder","ToplineAPI:AddNewCustomer:AddUser",$aryFields,$strResults);
		if(isset($aryCreateUserResultsData["detail"]["userID"])) {
			$strUserID = $aryCreateUserResultsData["detail"]["userID"];
			unset($aryFields);

			if($intStatus == 1) {
				// Next Add A Subscription To That User
				$aryFields["planID"] = $strPlanID;
				//$aryFields["currency"] = "";
				//$aryFields["campID"] = "";
				$aryFields["hostPlan"] = $strWHMCSServiceRID;
	
				list($intResponse,$strResults,$aryAllData) = $this->CallService("/users/$strUserID/subscriptions",2,$aryFields);
				$aryCreateSubscriptionResultsData = json_decode($strResults,true);
				logModuleCall("sitebuilder","ToplineAPI:AddNewCustomer:AddSubscription",$aryFields,$strResults);
				if(isset($aryCreateSubscriptionResultsData["detail"]["subID"])) {
					return true;
				}else{
					logModuleCall("sitebuilder","ToplineAPI:AddSubscription ERROR",$aryFields,$aryAllData);
					return false;
				}
			}
			elseif($intStatus == 2) {
				// Next Add Trial Subscription To That User
				$aryFields["planID"] = $strPlanID;

				list($intResponse,$strResults,$aryAllData) = $this->CallService("/users/$strUserID/trials",2,$aryFields);
				$aryCreateSubscriptionResultsData = json_decode($strResults,true);
				logModuleCall("sitebuilder","ToplineAPI:AddNewCustomer:AddTriaSubscription",$aryFields,$strResults);
				if(isset($aryCreateSubscriptionResultsData["detail"]["trialID"])) {
					return true;
				}else{
					logModuleCall("sitebuilder","ToplineAPI:AddNewCustomer:AddTrialSubscription ERROR",$aryFields,$aryAllData);
					return false;
				}
			}
		}else{
			// Error Creating user
			logModuleCall("sitebuilder","ToplineAPI:AddNewCustomer ERROR",$aryFields,$aryAllData);
			return false;
		}
	}
	public function ModifyCustomer($strUserID,$strPassword = "",$strFirstName = "",$strLastName = "",$strEmail = "",$strPhone = "",$strFTPAddress,$strFTPUsername,$strFTPPassword,$intFTPPort = 21,$strFTPWWWRoot,$intFTPMode,$strDomain,$intStatus,$strPlanID,$intFTPProtocol = 1,$strWHMCSServiceRID = "")
	{
		// $intStatus = 1 = Active (old system & new system)
		// $intStatus = 2 = Trial (old system)
		// $intStatus = 3 = Suspended (new system)
		// $intStatus = 5 = Deleted (new system)

		// First Create The User
		if($intStatus < 1 || $intStatus > 5) {
			// || $intStatus == 5 || $intStatus == 2) {
			logModuleCall("sitebuilder","ToplineAPI:AddNewCustomer:ModifyUser ERROR: Invalid User Status ($intStatus)", "","");
			return false;
		}

		// Delete User If Requested
		if($intStatus == 5) {
			return $this->DeleteCustomer($strUserID);
		}

		if($intFTPMode == 1)
			$intFTPMode = "Active";
		else
			$intFTPMode = "Passive";
		$intFTPProtocol = (int)$intFTPProtocol;
		if($intFTPProtocol < 1 || $intFTPProtocol > 2)
			$intFTPProtocol = 1;

		$aryFields["domain"] = $strDomain;
		if(strlen($strFirstName) > 0)
			$aryFields["firstName"] = $strFirstName;
		if(strlen($strLastName) > 0)
			$aryFields["lastName"] = $strLastName;
		if(strlen($strEmail) > 0)
			$aryFields["email"] = $strEmail;
		if(strlen($strPhone) > 0)
			$aryFields["phone"] = $strPhone;
		//if(strlen($strPassword) > 0)
		//	$aryFields["password"] = $strPassword;
		//$aryFields["countryCode"] = "";
		//$aryFields["currency"] = "";
		//$aryFields["language"] = "";
		//$aryFields["timezone"] = "";
		if(strlen($strFTPAddress) > 0)
			$aryFields["ftpAddress"] = $strFTPAddress;
		if(strlen($strFTPUsername) > 0)
			$aryFields["ftpUserid"] = $strFTPUsername;
		if(strlen($strFTPPassword) > 0)
			$aryFields["ftpPassword"] = $strFTPPassword;
		if(strlen($intFTPPort) > 0)
			$aryFields["ftpPort"] = $intFTPPort;
		if(strlen($strFTPWWWRoot) > 0)
			$aryFields["ftpWwwroot"] = $strFTPWWWRoot;
		if(strlen($intFTPProtocol) > 0)
			$aryFields["ftpProtocol"] = $intFTPProtocol;
		$aryFields["difm"] = 0;	// ??
		if(strlen($strWHMCSServiceRID) > 0)
			$aryFields["host1"] = $strWHMCSServiceRID;

		list($intResponse,$strResults,$aryAllData) = $this->CallService("/users/$strUserID",3,$aryFields);
		$aryUserEditResultsData = json_decode($strResults,true);
		logModuleCall("sitebuilder","ToplineAPI:ModifyCustomer",$aryFields,$aryAllData);
		if($aryUserEditResultsData["code"] == "201")
		{
			unset($aryFields);
			// Check User Status And Modify
			list($intResponse,$strResults,$aryAllData) = $this->CallService("/users/$strUserID",1);
			$aryCurrentUserResultsData = json_decode($strResults,true);
			if(isset($aryCurrentUserResultsData["detail"]["status"]))
				$intCurrentStatus = (int)$aryCurrentUserResultsData["detail"]["status"];
			else
				$intCurrentStatus = 0;
			if($intStatus == 3 && $intCurrentStatus == 1) {
				// Suspend User
				list($intResponse,$strResults,$aryAllData) = $this->CallService("/users/$strUserID/suspend",3);	
				logModuleCall("sitebuilder","ToplineAPI:ModifyCustomer:SuspendUser",$strUserID,$strResults);
			}
			elseif($intStatus == 1 && $intCurrentStatus == 3) {
				// Unsuspend/Reactivate User
				list($intResponse,$strResults,$aryAllData) = $this->CallService("/users/$strUserID/reactivate-all",3);
				logModuleCall("sitebuilder","ToplineAPI:ModifyCustomer:UnsuspendUser",$strUserID,$strResults);
			}
			// Now Check Trial Status And Modify
			list($intResponse,$strResults,$aryAllData) = $this->CallService("/users/$strUserID/trials",1);
			$aryCurrentTrialSubscriptionResultsData = json_decode($strResults,true);
			$intCurrentSubscriptionTrialCount = 0;
			if(isset($aryCurrentTrialSubscriptionResultsData["detail"]["count"])) {
				$intExpired = 0;
				if(isset($aryCurrentTrialSubscriptionResultsData["detail"]["trialIDs"][0]["expired"]))
					$intExpired = (int)$aryCurrentTrialSubscriptionResultsData["detail"]["trialIDs"][0]["expired"];
				if($intExpired == 0)
					$intCurrentSubscriptionTrialCount = (int)$aryCurrentTrialSubscriptionResultsData["detail"]["count"];
			}
			if($intCurrentSubscriptionTrialCount > 0 && $intCurrentStatus == 1 && ($intStatus == 2 || $intStatus == 1)) {
				// Convert Trial Account To Active Subscription
				if(is_numeric($strPlanID)) {
					$strPlanID = $this->GetBundleIDFromRID($strPlanID);
				}
				$aryFields["planID"] = $strPlanID;
				//$aryFields["currency"] = "";
				//$aryFields["campID"] = "";
				$aryFields["hostPlan"] = $strWHMCSServiceRID;
	
				list($intResponse,$strResults,$aryAllData) = $this->CallService("/users/$strUserID/subscriptions",2,$aryFields);
				$aryCreateSubscriptionResultsData = json_decode($strResults,true);
				logModuleCall("sitebuilder","ToplineAPI:ModifyCustomer:AddSubscription",$aryFields,$strResults);
				if(isset($aryCreateSubscriptionResultsData["detail"]["subID"])) {
					// Was Successfull In Upgrading Trial To Active
					logModuleCall("sitebuilder","ToplineAPI:ModifyCustomer:ConvertTrialToActiveSubscription",$aryFields,$aryAllData);
				}else{
					logModuleCall("sitebuilder","ToplineAPI:ModifyCustomer:AddSubscription ERROR",$aryFields,$aryAllData);
				}
			}

			// Now edit the users plan/subscription If Needed
			$blnSubscriptionPassedIsDifferent = false;
			list($intResponse,$strResults,$aryAllData) = $this->CallService("/users/$strUserID/subscriptions",1);
			$aryCurrentSubscriptionsResultsData = json_decode($strResults,true);
			if(isset($aryCurrentSubscriptionsResultsData["detail"]["subIDs"])) {
				foreach($aryCurrentSubscriptionsResultsData["detail"]["subIDs"] as $arySubscriptionData) {
					if(((int)$arySubscriptionData["status"] == 1 || (int)$arySubscriptionData["status"] == 2 || (int)$arySubscriptionData["status"] == 3) && strtolower($arySubscriptionData["planID"]) == strtolower($strPlanID)) {
						break;
					}else{
						$intSubscriptionID = $arySubscriptionData["subID"];
						$blnSubscriptionPassedIsDifferent = true;
					}
				}
			}
			if($blnSubscriptionPassedIsDifferent == true) {
				$aryFields["planID"] = $strPlanID;
				if(strlen($strWHMCSServiceRID) > 0)
					$aryFields["hostPlan"] = $strWHMCSServiceRID;
				list($intResponse,$strResults,$aryAllData) = $this->CallService("/users/$strUserID/subscriptions/$intSubscriptionID",3,$aryFields);
				$aryEditSubscriptionResultsData = json_decode($strResults,true);
				logModuleCall("sitebuilder","ToplineAPI:ModifyCustomer:ModifySubscription",$aryFields,$strResults);
				if(isset($aryEditSubscriptionResultsData["detail"]["subID"])) {
					return true;
				}else{
					logModuleCall("sitebuilder","ToplineAPI:ModifyCustomer:ModifySubscription ERROR",$aryFields,$aryAllData);
					return false;
				}
			}else{
				return true;
			}
		}else{
			logModuleCall("sitebuilder","ToplineAPI:ModifyCustomer ERROR",$aryFields,$aryAllData);
		}
		return false;
	}
	public function DeleteCustomer($strUserID) {
		list($intResponse,$strResults,$aryAllData) = $this->CallService("/users/$strUserID",4);
		$aryUserDeleteResultsData = json_decode($strResults,true);
		//logModuleCall("sitebuilder","ToplineAPI:DeleteCustomer",$aryFields,$aryAllData);
		if($aryAllData["http_code"] == "204")
		{
			return true;
		}else{
			logModuleCall("sitebuilder","ToplineAPI:DeleteCustomer ERROR",$aryFields,$aryAllData);
			return false;
		}
	}
	public function CheckIfDomainExists($strDomainName) {
		$aryUserData = $this->GetAccountInfo($strDomainName);
		if(isset($aryUserData["userid"]))
			return true;
		else
			return false;
		// Below is code to do a pagintion search, which would be slower
		$intRecordLimit = 50;
		list($intResponse,$strResults,$aryAllData) = $this->CallService("/users?size=1",1);
		$aryUsersResultsData = json_decode($strResults,true);
		if(isset($aryUsersResultsData["detail"]["users"])) {
			$intRecordCount = $aryUsersResultsData["detail"]["count"];
			$intPages = ceil($intRecordCount/$intRecordLimit);
			for ($intPage = 1; $intPage < ($intPages+1); $intPage++){
				list($intResponse,$strResults,$aryAllData) = $this->CallService("/users?page=".$intPage."&size=".$intRecordLimit,1);
				$aryUsersResultsData = json_decode($strResults,true);
				if(isset($aryUsersResultsData["detail"]["users"])) {
					foreach($aryUsersResultsData["detail"]["users"] as $aryUser) {
						if(strtolower($aryUser["domain"]) == strtolower($strDomainName))
							return true;
					}
				}
			}
			return false;
		}else{
			return false;
		}
	}
	public function GetAccountInfo($strDomainName = "", $strUserID = "")
	{
		if(strlen($strUsername) < 1 && strlen($strDomainName) > 0) {
			// Get User ID From Domain Name
			// list($intResponse,$strResults,$aryAllData) = $this->CallService("/users?domain=".urlencode('LIKE "%'.$strDomainName.'%"'),1);
			list($intResponse,$strResults,$aryAllData) = $this->CallService("/users?domain=".$strDomainName,1);
			$aryUserDataPre = json_decode($strResults,true);
			$aryUserData = $aryUserDataPre["detail"]["users"][0];
			logModuleCall("sitebuilder","ToplineAPI:GetAccountInfo:SearchByDomain",$strDomainName,$strResults);
		}else{
			list($intResponse,$strResults,$aryAllData) = $this->CallService("/users/$strUserID",1);
			$aryUserDataPre = json_decode($strResults,true);
			$aryUserData = $aryUserDataPre["detail"];
			logModuleCall("sitebuilder","ToplineAPI:GetAccountInfo:User",$strUserID,$strResults);
		}
		if(isset($aryUserData["userID"])) {
			$strUserID = $aryUserData["userID"];
			$intUserStatus = $aryUserData["status"];
			list($intResponse,$strResults,$aryAllData) = $this->CallService("/users/$strUserID/trials",1);
			$aryCurrentTrialSubscriptionResultsData = json_decode($strResults,true);
			$intCurrentSubscriptionTrialCount = 0;
			if(isset($aryCurrentTrialSubscriptionResultsData["detail"]["count"])) {
				$intExpired = 0;
				if(isset($aryCurrentTrialSubscriptionResultsData["detail"]["trialIDs"][0]["expired"]))
					$intExpired = (int)$aryCurrentTrialSubscriptionResultsData["detail"]["trialIDs"][0]["expired"];
				if($intExpired == 0)
					$intCurrentSubscriptionTrialCount = (int)$aryCurrentTrialSubscriptionResultsData["detail"]["count"];
			}
			if($intCurrentSubscriptionTrialCount > 0 && $intUserStatus == 1)
				$intUserStatus = 2;
			// Get The Current Subscription If Not In Trial Mode
			if($intUserStatus <> 2) {
				list($intResponse,$strResults,$aryAllData) = $this->CallService("/users/$strUserID/subscriptions",1);
				$aryCurrentSubscriptionsResultsData = json_decode($strResults,true);
				if(isset($aryCurrentSubscriptionsResultsData["detail"]["subIDs"])) {
					foreach($aryCurrentSubscriptionsResultsData["detail"]["subIDs"] as $arySubscriptionData) {
						if(((int)$arySubscriptionData["status"] == 1 || (int)$arySubscriptionData["status"] == 2 || (int)$arySubscriptionData["status"] == 3) && version_compare($arySubscriptionData["productID3"], '2.0.10', '>=') && version_compare($arySubscriptionData["productID3"], '2.1.40', '<')) {
							$strCurrentPlanID = $arySubscriptionData["planID"];
							break;
						}
					}
				}
			}
			return Array("userid"=>$strUserID,"status"=>$intUserStatus,"creationdate"=>"","modifieddate"=>"","planid"=>$strCurrentPlanID);
		}
		//logModuleCall("sitebuilder","ToplineAPI:GetAccountInfo ERROR",$aryFields,$aryAllData);
		return Array();
	}
	public function GetTokenLoginURL($strUserID)
	{
		$strURL = "";
		$strToken = "";
		list($intResponse,$strResults,$aryAllData) = $this->CallService("/users/$strUserID/yolalogin",1);
		logModuleCall("sitebuilder","ToplineAPI:GetTokenLoginURL",$strUserID,$aryAllData);
		//$aryLoginData = json_decode($strResults,true);
		//if($aryLoginData["code"] == "303") {
		if($aryAllData["http_code"] == "303") {
			$strURL = $aryAllData["redirect_url"];
		}else{
			logModuleCall("sitebuilder","ToplineAPI:GetTokenLoginURL ERROR",$strUserID,$aryAllData);
		}
		return $strURL;

	}
	public function DoDirectLoginURL($strUserID,$strPassword) {
		// Not Supported Currently
	}
	public function GetBundleID($strBundleName)
	{
		$aryBundles = $this->GetBundles();
		foreach($aryBundles as $aryBundle)
		{
			if(strtolower((string)$aryBundle["planName"]) == strtolower($strBundleName))
				return $aryBundle["planID"];
			elseif(strtolower((string)$aryBundle["planID"]) == strtolower($strBundleName))
				return $aryBundle["planID"];
			elseif(strtolower((string)$aryBundle["ID"]) == strtolower($strBundleName))
				return $aryBundle["planID"];
		}
		return "";
	}
	public function GetBundleName($strBundleID)
	{
		$aryBundles = $this->GetBundles();
		foreach($aryBundles as $aryBundle)
		{
			if(strtolower((string)$aryBundle["planID"]) == strtolower($strBundleID))
				return $aryBundle["planName"];
		}
		return "";
	}
	public function GetBundleIDFromRID($strBundleRID)
	{
		$aryBundles = $this->GetBundles();
		foreach($aryBundles as $aryBundle)
		{
			if((float)$aryBundle["ID"] == (float)$strBundleRID)
				return $aryBundle["planID"];
		}
		return "";
	}
	public function VerifyBundleIDAllowed($strBundleID)
	{
		if(strlen($strBundleID) < 1)
			return false;
		$aryBundles = $this->GetBundles();
		foreach($aryBundles as $aryBundle)
		{
			if(strtolower((string)$aryBundle["planID"]) == strtolower($strBundleID))
				return true;
		}
		return false;
	}
	public function GetBundles() {
		list($intResponse,$strResults,$aryAllData) = $this->CallService("/plans",1);
		$aryBrandPlanData = json_decode($strResults,true);
		logModuleCall("sitebuilder","ToplineAPI:GetBundles","",$strResults);
		return $aryBrandPlanData["detail"]["planIDs"];
	}
}
//-----------------------------------------------------------
// Topline API Curl Wrapper Class
//-----------------------------------------------------------
class ToplineAPICurl {
	protected $response = '';		// Contains the cURL response for debug
	protected $responseheaders = '';	// Contains the cURL response headers
	protected $session;			// Contains the cURL handler for a session
	protected $url;				// URL of the session
	protected $options = array();		// Populates curl_setopt_array
	protected $headers = array();		// Populates extra HTTP headers
	protected $requestheaders = '';		// Contains the cURL Request headers
	protected $requestarguments = array();	// Containts the cURL Request arguments (params)
	protected $httpmethod = 'GET';		// Containts the cURL Request method
	public $error_code;			// Error code returned as an int
	public $error_string;			// Error message returned as a string
	protected $info;				// Returned after request (elapsed time, etc)

	function __construct($url = '')
	{
		$url AND $this->create($url);
	}

	public function __call($method, $arguments)
	{
		if (in_array($method, array('simple_get', 'simple_post', 'simple_put', 'simple_delete', 'simple_patch')))
		{
			// Take off the "simple_" and past get/post/put/delete/patch to _simple_call
			$verb = str_replace('simple_', '', $method);
			array_unshift($arguments, $verb);
			return call_user_func_array(array($this, '_simple_call'), $arguments);
		}
	}

	/* =================================================================================
	 * SIMPLE METHODS
	 * Using these methods you can make a quick and easy cURL call with one line.
	 * ================================================================================= */

	public function _simple_call($method, $url, $params = array(), $options = array())
	{
		// Get acts differently, as it doesnt accept parameters in the same way
		if ($method === 'get')
		{
			// If a URL is provided, create new session
			$this->create($url.($params ? '?'.http_build_query($params, NULL, '&') : ''));
		}

		else
		{
			// If a URL is provided, create new session
			$this->create($url);

			$this->{$method}($params);
		}

		// Add in the specific options provided
		$this->options($options);

		return $this->execute();
	}

	public function simple_ftp_get($url, $file_path, $username = '', $password = '')
	{
		// If there is no ftp:// or any protocol entered, add ftp://
		if ( ! preg_match('!^(ftp|sftp)://! i', $url))
		{
			$url = 'ftp://' . $url;
		}

		// Use an FTP login
		if ($username != '')
		{
			$auth_string = $username;

			if ($password != '')
			{
				$auth_string .= ':' . $password;
			}

			// Add the user auth string after the protocol
			$url = str_replace('://', '://' . $auth_string . '@', $url);
		}

		// Add the filepath
		$url .= $file_path;

		$this->option(CURLOPT_BINARYTRANSFER, TRUE);
		$this->option(CURLOPT_VERBOSE, TRUE);

		return $this->execute();
	}

	/* =================================================================================
	 * ADVANCED METHODS
	 * Use these methods to build up more complex queries
	 * ================================================================================= */

	public function post($params = array(), $options = array(), $blnKeepParamsAsArray = false)
	{
		// If its an array (instead of a query string) then format it correctly
		if (is_array($params) && $blnKeepParamsAsArray == false)
		{
			$this->requestarguments = $params;
			$params = http_build_query($params, NULL, '&');
		}

		// Add in the specific options provided
		$this->options($options);
		$this->httpmethod = "POST";
		$this->http_method('post');
		$this->option(CURLOPT_POST, TRUE);
		$this->option(CURLOPT_POSTFIELDS, $params);
	}

	public function put($params = array(), $options = array())
	{
		// If its an array (instead of a query string) then format it correctly
		if (is_array($params))
		{
			$this->requestarguments = $params;
			$params = http_build_query($params, NULL, '&');
		}

		// Add in the specific options provided
		$this->options($options);
		$this->httpmethod = "PUT";
		$this->http_method('put');
		$this->option(CURLOPT_POSTFIELDS, $params);

		// Override method, I think this overrides $_POST with PUT data but... we'll see eh?
		$this->option(CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: PUT'));
	}
	
	public function patch($params = array(), $options = array())
	{
		// If its an array (instead of a query string) then format it correctly
		if (is_array($params))
		{
			$this->requestarguments = $params;
			$params = http_build_query($params, NULL, '&');
		}

		// Add in the specific options provided
		$this->options($options);
		$this->httpmethod = "PATCH";
		$this->http_method('patch');
		$this->option(CURLOPT_POSTFIELDS, $params);

		// Override method, I think this overrides $_POST with PATCH data but... we'll see eh?
		$this->option(CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: PATCH'));
	}

	public function delete($params, $options = array())
	{
		// If its an array (instead of a query string) then format it correctly
		if (is_array($params))
		{
			$this->requestarguments = $params;
			$params = http_build_query($params, NULL, '&');
		}

		// Add in the specific options provided
		$this->options($options);
		$this->httpmethod = "DELETE";
		$this->http_method('delete');

		$this->option(CURLOPT_POSTFIELDS, $params);
	}

	public function set_cookies($params = array())
	{
		if (is_array($params))
		{
			$params = http_build_query($params, NULL, '&');
		}

		$this->option(CURLOPT_COOKIE, $params);
		return $this;
	}

	public function http_header($header, $content = NULL)
	{
		$this->headers[] = $content ? $header . ': ' . $content : $header;
		return $this;
	}

	public function http_method($method)
	{
		$this->options[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
		return $this;
	}

	public function http_login($username = '', $password = '', $type = 'any')
	{
		$this->option(CURLOPT_HTTPAUTH, constant('CURLAUTH_' . strtoupper($type)));
		$this->option(CURLOPT_USERPWD, $username . ':' . $password);
		return $this;
	}

	public function proxy($url = '', $port = 80)
	{
		$this->option(CURLOPT_HTTPPROXYTUNNEL, TRUE);
		$this->option(CURLOPT_PROXY, $url . ':' . $port);
		return $this;
	}

	public function proxy_login($username = '', $password = '')
	{
		$this->option(CURLOPT_PROXYUSERPWD, $username . ':' . $password);
		return $this;
	}

	public function ssl($verify_peer = TRUE, $verify_host = 2, $path_to_cert = NULL)
	{
		if ($verify_peer)
		{
			$this->option(CURLOPT_SSL_VERIFYPEER, TRUE);
			$this->option(CURLOPT_SSL_VERIFYHOST, $verify_host);
			if (isset($path_to_cert)) {
				$path_to_cert = realpath($path_to_cert);
				$this->option(CURLOPT_CAINFO, $path_to_cert);
			}
		}
		else
		{
			$this->option(CURLOPT_SSL_VERIFYPEER, FALSE);
		}
		return $this;
	}

	public function options($options = array())
	{
		// Merge options in with the rest - done as array_merge() does not overwrite numeric keys
		foreach ($options as $option_code => $option_value)
		{
			$this->option($option_code, $option_value);
		}

		// Set all options provided
		curl_setopt_array($this->session, $this->options);

		return $this;
	}

	public function option($code, $value, $prefix = 'opt')
	{
		if (is_string($code) && !is_numeric($code))
		{
			$code = constant('CURL' . strtoupper($prefix) . '_' . strtoupper($code));
		}

		$this->options[$code] = $value;
		return $this;
	}

	// Start a session from a URL
	public function create($url)
	{
		$this->url = $url;
		$this->session = curl_init($this->url);

		return $this;
	}

	// End a session and return the results
	public function execute()
	{		
		// Set two default options, and merge any extra ones in
		if ( ! isset($this->options[CURLOPT_TIMEOUT]))
		{
			$this->options[CURLOPT_TIMEOUT] = 30;
		}
		if ( ! isset($this->options[CURLOPT_RETURNTRANSFER]))
		{
			$this->options[CURLOPT_RETURNTRANSFER] = TRUE;
		}
		if ( ! isset($this->options[CURLOPT_FAILONERROR]))
		{
			$this->options[CURLOPT_FAILONERROR] = TRUE;
		}

		// Only set follow location if not running securely
		if ( ! ini_get('safe_mode') && ! ini_get('open_basedir'))
		{
			// Ok, follow location is not set already so lets set it to true
			if ( ! isset($this->options[CURLOPT_FOLLOWLOCATION]))
			{
				$this->options[CURLOPT_FOLLOWLOCATION] = TRUE;
			}
		}

		if ( ! empty($this->headers))
		{
			$this->option(CURLOPT_HTTPHEADER, $this->headers);
		}

		//$this->option(CURLOPT_USERAGENT, "HEAP Software KayakoCloud WHMCS Module");

		// Enable The Display Of Request And Response Headers
		$this->options[CURLOPT_HEADER] = TRUE;
		$this->options[CURLINFO_HEADER_OUT] = TRUE;

		$this->options();

		// Execute the request & and hide all output
		$this->response = curl_exec($this->session);
		$this->info = curl_getinfo($this->session);

		// Request failed
		if ($this->response === FALSE)
		{
			$this->requestheaders = curl_getinfo($this->session, CURLINFO_HEADER_OUT);
			$header_size = curl_getinfo($this->session, CURLINFO_HEADER_SIZE);
			$this->responseheaders = substr($this->response, 0, $header_size);
			$this->response = substr($this->response, $header_size);
			$errno = curl_errno($this->session);
			$error = curl_error($this->session);

			curl_close($this->session);
			$this->set_defaults();

			$this->error_code = $errno;
			$this->error_string = $error;

			return FALSE;
		}

		// Request successful
		else
		{
			$this->requestheaders = curl_getinfo($this->session, CURLINFO_HEADER_OUT);
			$header_size = curl_getinfo($this->session, CURLINFO_HEADER_SIZE);
			curl_close($this->session);
			$this->responseheaders = substr($this->response, 0, $header_size);
			$this->response = substr($this->response, $header_size);
			$this->last_response = $this->response;
			$this->set_defaults();
			return $this->last_response;
		}
	}

	public function is_enabled()
	{
		return function_exists('curl_init');
	}

	public function GetInfo()
	{
		return $this->info;
	}

	public function debug()
	{
		echo "=============================================<br/>\n";
		echo "<h2>CURL Test</h2>\n";
		echo "=============================================<br/>\n";
		echo "<h3>Response</h3>\n";
		echo "<code>" . nl2br(htmlentities($this->last_response)) . "</code><br/>\n\n";

		if ($this->error_string)
		{
			echo "=============================================<br/>\n";
			echo "<h3>Errors</h3>";
			echo "<strong>Code:</strong> " . $this->error_code . "<br/>\n";
			echo "<strong>Message:</strong> " . $this->error_string . "<br/>\n";
		}

		echo "=============================================<br/>\n";
		echo "<h3>Info</h3>";
		echo "<pre>";
		print_r($this->info);
		echo "</pre>";
	}

	public function debug_request()
	{
		return array(
			'url' => $this->url
		);
	}

	public function set_defaults()
	{
		$this->response = '';
		$this->headers = array();
		$this->options = array();
		$this->error_code = NULL;
		$this->error_string = '';
		$this->session = NULL;
	}

	public function getResponseHeaders($blnReturnAsArray = false)
	{
		if($blnReturnAsArray == false) {
			return $this->responseheaders;
		}else{
			$headers = array();
			$header_text = substr($this->responseheaders, 0, strpos($this->responseheaders, "\r\n\r\n"));
			$intHTTPVersionLoc1 = strpos($this->responseheaders,"HTTP/1");
			$intHTTPVersionLoc2 = strpos(substr($this->responseheaders,$intHTTPVersionLoc1+strlen("HTTP/1")),"HTTP/1");
			if(!is_numeric($intHTTPVersionLoc2))
				$intHTTPVersionLoc2 = 0;
			if((int)$intHTTPVersionLoc1 == 0 && (int)$intHTTPVersionLoc2 > 1) {
				$header_text = substr($this->responseheaders, strpos($this->responseheaders, "\r\n\r\n")+strlen("\r\n\r\n"));
			}
			foreach (explode("\r\n", $header_text) as $i => $line)
				if ($i === 0)
					$headers['http_code'] = $line;
			else
			{
				list ($key, $value) = explode(': ', $line);
				$headers[$key] = $value;
			}
			return $headers;
		}
	}

	public function getRequestHeaders() {
		return $this->requestheaders;
	}
}

function Topline_GetCurrentVersionFromGithub()
{
	$strCurrentVersion = "0.00";
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, "https://api.github.com/repos/aiso-net/topline-sitebuilder-whmcs/branches");
	//curl_setopt($curl, CURLOPT_POSTFIELDS, "");
	curl_setopt($curl, CURLOPT_HEADER, 0);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10); 
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13');
	$cont = curl_exec($curl);
	curl_close($curl);
	unset($curl);
	$commits = json_decode($cont, true);
	if(isset($commits[0]["commit"]["url"]))
	{
		$strCommitsURL = $commits[0]["commit"]["url"];
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $strCommitsURL);
		//curl_setopt($curl, CURLOPT_POSTFIELDS, "");
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10); 
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13');
		$cont2 = curl_exec($curl);
		curl_close($curl);
		unset($curl);
		$commitsData = json_decode($cont2, true);
		if(isset($commitsData["commit"]["message"]))
		{
			if(strpos($commitsData["commit"]["message"],"\n") !== false)
			{
				$aryMessageData = explode("\n",$commitsData["commit"]["message"]);
				$strCurrentVersion = strtolower(trim($aryMessageData[0]));
				if(strpos($strCurrentVersion,"version") !== false)
				{
					$strCurrentVersion = str_replace("version","",$strCurrentVersion);
				}
				$strCurrentVersion = str_replace(" ","",$strCurrentVersion);
				$strCurrentVersion = str_replace(":","",$strCurrentVersion);
			}
		}
	}
	return $strCurrentVersion;
}
function Topline_CheckForModuleUpdate($strCurrentInstalledVersion)
{
	$strNewestVersion = Topline_GetCurrentVersionFromGithub();
	$blnUpgradePossible = false;
	if(strpos($strCurrentInstalledVersion,"-") !== false)
		$aryNewestVersion = explode("-",$strCurrentInstalledVersion);
	else
		if(strlen($strCurrentInstalledVersion) == 0)
			$aryNewestVersion = Array("0","0");
		else
			$aryNewestVersion = Array($strCurrentInstalledVersion,"0");
	if(version_compare($strNewestVersion,$aryNewestVersion[0], "gt"))
	{
		$NewestVersion = $strNewestVersion;
		$blnUpgradePossible = true;
	}
	elseif(version_compare($strNewestVersion,$aryNewestVersion[0], "eq"))
	{
		// same version, check release version
		if(version_compare($RowVerChk['Release'],$aryNewestVersion[1], "gt"))
		{
			$NewestVersion = $strNewestVersion;
			$blnUpgradePossible = true;
		}
	}
	return $blnUpgradePossible;
}

function Topline_GetDomainFromURL($url){
	$strDomain = Topline_getRegisteredDomain($url,false);
	if($strDomain != NULL)
	{
		return $strDomain;
	}else{
		if (strpos($url,"http://") !== false){
			$httpurl=$url;
		} else {
			$httpurl="http://".$url;
		}
		$parse = parse_url($httpurl);
		$domain=$parse['host'];
	
		$portion=explode(".",$domain);
		$count=sizeof($portion)-1;
		if ($count>1){
			$result=$portion[$count-1].".".$portion[$count];
		} else {
			$result=$domain;
		}
		return $result;
	}
}

/**
 * Load $_LANG from language file
 */
function Topline_load_language() {
	global $_LANG;
	$dh = opendir (dirname(__FILE__).'/lang/');

	while (false !== $file2 = readdir ($dh)) {
		if (!is_dir ('' . 'lang/' . $file2) ) {
			$pieces = explode ('.', $file2);
			if ($pieces[1] == 'txt') {
				$arrayoflanguagefiles[] = $pieces[0];
				continue;
			}
			continue;
		}
	};

	closedir ($dh);
	if(isset($_SESSION['Language']))
		$language = $_SESSION['Language'];
	else
		$language = Array();
	if ( ! in_array ($language, $arrayoflanguagefiles) )
		$language =  "English";
	if ( file_exists( dirname(__FILE__) . "/lang/$language.txt" ) ) {
		ob_start ();
		include dirname(__FILE__) . "/lang/$language.txt";
		$templang = ob_get_contents();
		ob_end_clean ();
		eval ($templang);
	}
}

// http://www.dkim-reputation.org/regdom-lib-downloads/

/*
 * Calculate the effective registered domain of a fully qualified domain name.
 *
 * <@LICENSE>
 * Licensed to the Apache Software Foundation (ASF) under one or more
 * contributor license agreements.  See the NOTICE file distributed with
 * this work for additional information regarding copyright ownership.
 * The ASF licenses this file to you under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with
 * the License.  You may obtain a copy of the License at:
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * </@LICENSE>
 *
 * Florian Sager, 25.07.2008, sager@agitos.de, http://www.agitos.de
 */

/*
 * Remove subdomains from a signing domain to get the registered domain.
 *
 * dkim-reputation.org blocks signing domains on the level of registered domains
 * to rate senders who use e.g. a.spamdomain.tld, b.spamdomain.tld, ... under
 * the most common identifier - the registered domain - finally.
 *
 * This function returns NULL if $signingDomain is TLD itself
 * 
 * $signingDomain has to be provided lowercase (!)
 */

function Topline_getRegisteredDomain($signingDomain, $fallback = TRUE) {
	global $tldTree;

	require_once dirname(__FILE__) . '/effectiveTLDs.inc.php';

	$signingDomain = str_replace("http://","",$signingDomain);
	$signingDomain = str_replace("https://","",$signingDomain);

	$signingDomainParts = split('\.', $signingDomain);

	$result = Topline_findRegisteredDomain($signingDomainParts, $tldTree);

	if ($result===NULL || $result=="") {
		// this is an invalid domain name
		return NULL;
	}

	// assure there is at least 1 TLD in the stripped signing domain
	if (!strpos($result, '.')) {
		if ($fallback===FALSE) {
			return NULL;
		}
		$cnt = count($signingDomainParts);
		if ($cnt==1 || $signingDomainParts[$cnt-2]=="") return NULL;
		if (!Topline_validDomainPart($signingDomainParts[$cnt-2]) || !Topline_validDomainPart($signingDomainParts[$cnt-1])) return NULL;
		return $signingDomainParts[$cnt-2].'.'.$signingDomainParts[$cnt-1];
	}
	return $result;
}

function Topline_validDomainPart($domPart) {
	// see http://www.register.com/domain-extension-rules.rcmx
	
	$len = strlen($domPart);

	// not more than 63 characters
	if ($len>63) return FALSE;

	// not less than 1 characters --> there are TLD-specific rules that could be considered additionally
	if ($len<1) return FALSE;
	
	// Use only letters, numbers, or hyphen ("-")
	// not beginning or ending with a hypen (this is TLD specific, be aware!)
	if (!preg_match("/^([a-z0-9])(([a-z0-9-])*([a-z0-9]))*$/", $domPart)) return FALSE;

	return TRUE;
}

// recursive helper method
function Topline_findRegisteredDomain($remainingSigningDomainParts, &$treeNode) {

	$sub = array_pop($remainingSigningDomainParts);

	$result = NULL;
	if (isset($treeNode['!'])) {
		return '#';
	}
	
	if (!Topline_validDomainPart($sub)) {
		return NULL;
	}

	if (is_array($treeNode) && array_key_exists($sub, $treeNode)) {
		$result = Topline_findRegisteredDomain($remainingSigningDomainParts, $treeNode[$sub]);
	} else if (is_array($treeNode) && array_key_exists('*', $treeNode)) {
		$result = Topline_findRegisteredDomain($remainingSigningDomainParts, $treeNode['*']);
	} else {
		return $sub;
	}

	// this is a hack 'cause PHP interpretes '' as NULL
	if ($result == '#') {
		return $sub;
	} else if (strlen($result)>0) {
		return $result.'.'.$sub;
	}
	return NULL;
}
?>