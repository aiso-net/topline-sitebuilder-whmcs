<?php
//-------------------------------------------------
// Global WHMCS Add-On Module Variables
//-------------------------------------------------
global $mstrModuleName,$mstrModuleLink;

$mstrModuleName = "sitebuilder";
$mstrModuleLink = "addonmodules.php?module=".$mstrModuleName;

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
				$Topline->SetPartnerGUID($aryModuleSettings[0]);
				$Topline->SetPartnerID($aryModuleSettings[1]);
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
			//-----------------------------
			// Get Custom Module Product Custom Field Names Used For Yola User ID
			//-----------------------------
			$table = "mod_sitebuilder_modulexcustomfields";
			$fields = "intRecordID,txtYolaUserIDProductCustomFieldName";
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
			}else{
				// Use Global Custom Field Names
				$strYolaUserIDProductCustomFieldName = $globalcustomfieldnamesdata["txtValue"];
			}
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
	logModuleCall("sitebuilder","Topline_GetCustomFTPLoginInfo","Function Settings After Variable Change: User ID PCFN: " . $strYolaUserIDProductCustomFieldName . ", Topline FTP Username PCFN: " . $strYolaFTPUsernameProductCustomFieldName . ", Topline FTP Password PCFN: " . $strYolaFTPPasswordProductCustomFieldName . ".");
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
	$table = "tblhosting";
	$fields = "domain,username";
	$where = array("id"=>$intServiceRID);
	$result = select_query($table,$fields,$where);
	$data = mysql_fetch_array($result);
	$strDomainName = $data["domain"];
	$strServiceUsername = $data["username"];
	//--------------
	// Get Server Info From WHMCS
	//--------------
	$table = "tblservers";
	$fields = "ipaddress";
	$where = array("id"=>$intServerRID);
	$result = select_query($table,$fields,$where);
	$data = mysql_fetch_array($result);
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
	$result = select_query($table,$fields,$where,$sort,$sortorder,$limits,$join);
	$data = mysql_fetch_array($result);
	$intCustomFieldRID = $data[0];
	if(!empty($intCustomFieldRID))
	{
		$table = "tblcustomfieldsvalues";
		$array = array("value"=>$strNewValue);
		$where = array("relid"=>$intServiceRID,"fieldid"=>$intCustomFieldRID);
		update_query($table,$array,$where);
		return true;
	}else{
		return false;
	}
}
//-----------------------------------------------------------
// Get WHMCS Product Custom Field Value
//-----------------------------------------------------------
function Topline_GetWHMCSCustomFieldValue($strFieldName,$intPackageRID,$intServiceRID)
{
	$table = "tblcustomfields";
	$fields = "id";
	$where = array("fieldname"=>$strFieldName,"relid"=>$intPackageRID);
	$result = select_query($table,$fields,$where,$sort,$sortorder,$limits,$join);
	$data = mysql_fetch_array($result);
	$intCustomFieldRID = $data[0];
	if(!empty($intCustomFieldRID))
	{
		$table = "tblcustomfieldsvalues";
		$fields = "value";
		$where = array("relid"=>$intServiceRID,"fieldid"=>$intCustomFieldRID);
		$result = select_query($table,$fields,$where,$sort,$sortorder,$limits,$join);
		$data = mysql_fetch_array($result);
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
	$where = array("module"=>$mstrModuleName,"setting"=>"PartnerGUID");
	$result = select_query($table,$fields,$where);
	$modulesettings = mysql_fetch_array($result);
	$strParterGUID = $modulesettings["value"];

	$table = "tbladdonmodules";
	$fields = "value";
	$where = array("module"=>$mstrModuleName,"setting"=>"PartnerID");
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

	return Array($strParterGUID,$strParterID,$strDeleteDBTablesOnUninstall);
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
	private $strPartnerGUID;
	private $strPartnerID;
	private $strAddUserTestAPIURL = 'https://api.sitebuilderservice.com/staging/addcustomer.php';
	private $strModifyUserTestAPIURL = 'https://api.sitebuilderservice.com/staging/modifycustomer.php';
	private $strRequestTokenTestAPIURL = 'https://api.sitebuilderservice.com/staging/request_token.php';
	private $strTokenLoginTestAPIURL = 'http://login.sitebuilderservice.com/staging/sitebuilder_session.php';
	private $strBundleInfoTestAPIURL = 'https://api.sitebuilderservice.com/bundleinfo.php';
	private $strQueryUserTestAPIURL = 'https://api.sitebuilderservice.com/staging/querycustomer.php';
	private $strAddUserAPIURL = 'https://api.sitebuilderservice.com/addcustomer.php';
	private $strModifyUserAPIURL = 'https://api.sitebuilderservice.com/modifycustomer.php';
	private $strDeleteUserAPIURL = 'https://api.sitebuilderservice.com/deletecustomer.php';
	private $strRequestTokenAPIURL = 'https://api.sitebuilderservice.com/request_token.php';
	private $strTokenLoginAPIURL = 'http://login.sitebuilderservice.com/sitebuilder_session.php';
	private $strBundleInfoAPIURL = 'https://api.sitebuilderservice.com/bundleinfo.php';
	private $strQueryUserAPIURL = 'https://api.sitebuilderservice.com/querycustomer.php';
	
	/**
	* Constructor, sets default options
	*/
	public function __construct() {
	}

	public function TurnOnStatingMode() {
		$this->blnStagingMode = true;
	}

	public function SetPartnerGUID($strGUID) {
		$this->strPartnerGUID = $strGUID;
	}

	public function SetPartnerID($strID) {
		$this->strPartnerID = $strID;
	}

	public function AddNewCustomer($strUserID,$strPassword,$strFirstName = "",$strLastName = "",$strEmail = "",$strPhone = "",$strFTPAddress,$strFTPUsername,$strFTPPassword,$intFTPPort = 21,$strFTPWWWRoot,$intFTPMode,$strDomain,$intStatus,$strBundleID,$strOldUserID = "",$strOldPartnerID = "",$intMoveFlag = 0,$intFTPProtocol = 1,$strWHMCSServiceRID = "")
	{
		if($intFTPMode == 1)
			$intFTPMode = "Active";
		else
			$intFTPMode = "Passive";
		$intFTPProtocol = (int)$intFTPProtocol;
		if($intFTPProtocol < 0 || $intFTPProtocol > 3)
			$intFTPProtocol = 1;
		$fields = array(
			'partner_guid' => urlencode($this->strPartnerGUID),
			'partner_id' => urlencode($this->strPartnerID),
			'userid' => urlencode($strUserID),
			'status' => urlencode($intStatus),
			'bundle_id' => urlencode($strBundleID)
		);
		if(strlen($strWHMCSServiceRID) > 0)
			$fields['host_id'] = urlencode($strWHMCSServiceRID);
		if(strlen($strFTPAddress) > 0)
			$fields['ftp_address'] = urlencode($strFTPAddress);
		if(strlen($strFTPUsername) > 0)
			$fields['ftp_userid'] = urlencode($strFTPUsername);
		if(strlen($strFTPPassword) > 0)
			$fields['ftp_password'] = urlencode($strFTPPassword);
		if(strlen($intFTPPort) > 0)
			$fields['ftp_port'] = urlencode($intFTPPort);
		if(strlen($strFTPWWWRoot) > 0)
			$fields['ftp_wwwroot'] = urlencode($strFTPWWWRoot);
		if(strlen($intFTPMode) > 0)
			$fields['ftp_mode'] = urlencode($intFTPMode);
		if(strlen($intFTPProtocol) > 0)
			$fields['ftp_protocol'] = urlencode($intFTPProtocol);
		if(strlen($strDomain) > 0)
			$fields['domain'] = urlencode($strDomain);
		if(strlen($strPassword) > 0)
			$fields['password'] = urlencode($strPassword);
		if(strlen($strFirstName) > 0)
			$fields['first_name'] = urlencode($strFirstName);
		if(strlen($strLastName) > 0)
			$fields['last_name'] = urlencode($strLastName);
		if(strlen($strEmail) > 0)
			$fields['email'] = urlencode($strEmail);
		if(strlen($strPhone) > 0)
			$fields['phone'] = urlencode($strPhone);
		if(strlen($strOldUserID) > 0 && strlen($strOldPartnerID) > 0 && $intMoveFlag == 1)
		{
			$fields['olduserid'] = urlencode($strOldUserID);
			$fields['oldpartnerid'] = urlencode($strOldPartnerID);
			$fields['moveflag'] = urlencode($intMoveFlag);
		}
		$xmldata = $this->CallService(1,$fields);
		$xmldata = $this->CleanUpFrontOfXML($xmldata);
		$xmldata = str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xmldata);
		$aryResult = simplexml_load_string($xmldata);
		logModuleCall("sitebuilder","ToplineAPI:AddNewCustomer",$fields,$xmldata);
		if(isset($aryResult["code"]))
			if($aryResult["code"] == "200")
				return true;
		return false;
	}

	public function ModifyCustomer($strUserID,$strPassword = "",$strFirstName = "",$strLastName = "",$strEmail = "",$strPhone = "",$strFTPAddress,$strFTPUsername,$strFTPPassword,$intFTPPort = 21,$strFTPWWWRoot,$intFTPMode,$strDomain,$intStatus,$intBundleID,$strOldUserID = "",$strOldPartnerID = "",$intMoveFlag = 0,$intFTPProtocol = 1)
	{
		if($intFTPMode == 1)
			$intFTPMode = "Active";
		else
			$intFTPMode = "Passive";
		$intFTPProtocol = (int)$intFTPProtocol;
		if($intFTPProtocol < 0 || $intFTPProtocol > 3)
			$intFTPProtocol = 1;
		$fields = array(
			'partner_guid' => urlencode($this->strPartnerGUID),
			'partner_id' => urlencode($this->strPartnerID),
			'userid' => urlencode($strUserID),
			'ftp_address' => urlencode($strFTPAddress),
			'ftp_userid' => urlencode($strFTPUsername),
			'ftp_password' => urlencode($strFTPPassword),
			'ftp_port' => urlencode($intFTPPort),
			'ftp_wwwroot' => urlencode($strFTPWWWRoot),
			'ftp_mode' => urlencode($intFTPMode),
			'ftp_protocol' => urlencode($intFTPProtocol),
			'domain' => urlencode($strDomain),
			'status' => urlencode($intStatus),
			'bundle_id' => urlencode($this->GetBundleID($intBundleID))
		);
		if(strlen($strPassword) > 0)
			$fields['password'] = urlencode($strPassword);
		if(strlen($strFirstName) > 0)
			$fields['first_name'] = urlencode($strFirstName);
		if(strlen($strLastName) > 0)
			$fields['last_name'] = urlencode($strLastName);
		if(strlen($strEmail) > 0)
			$fields['email'] = urlencode($strEmail);
		if(strlen($strPhone) > 0)
			$fields['phone'] = urlencode($strPhone);
		if(strlen($strOldUserID) > 0 && strlen($strOldPartnerID) > 0 && $intMoveFlag == 1)
		{
			$fields['olduserid'] = urlencode($strOldUserID);
			$fields['oldpartnerid'] = urlencode($strOldPartnerID);
			$fields['moveflag'] = urlencode($intMoveFlag);
		}
		$xmldata = $this->CallService(2,$fields);
		$xmldata = $this->CleanUpFrontOfXML($xmldata);
		$xmldata = str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xmldata);
		$aryResult = simplexml_load_string($xmldata);
		if($aryResult["code"] == "200")
		{
			return true;
		}
		return false;
	}

	public function DeleteCustomer($strUserID)
	{
		$fields = array(
			'partner_guid' => urlencode($this->strPartnerGUID),
			'partner_id' => urlencode($this->strPartnerID),
			'userid' => urlencode($strUserID)
		);
		$xmldata = $this->CallService(3,$fields);
		$xmldata = $this->CleanUpFrontOfXML($xmldata);
		$xmldata = str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xmldata);
		$aryResult = simplexml_load_string($xmldata);
		if($aryResult["code"] == "200")
		{
			return true;
		}
		return false;
	}

	public function CheckIfDomainExists($strDomainName)
	{
		$fields = array(
			'partner_guid' => urlencode($this->strPartnerGUID),
			'partner_id' => urlencode($this->strPartnerID),
			'domain' => urlencode($strDomainName)
		);
		$xmldata = $this->CallService(7,$fields);
		$xmldata = $this->CleanUpFrontOfXML($xmldata);
		$xmldata = str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xmldata);
		$aryResult = simplexml_load_string($xmldata);
		if($aryResult["code"] == "200")
		{
			return true;
		}
		return false;
	}

	public function GetAccountInfo($strDomainName = "", $strUsername = "")
	{
		$fields = array(
			'partner_guid' => urlencode($this->strPartnerGUID),
			'partner_id' => urlencode($this->strPartnerID),
			'domain' => urlencode($strDomainName)
		);
		$xmldata = $this->CallService(7,$fields);
		$xmldata = $this->CleanUpFrontOfXML($xmldata);
		$xmldata = str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xmldata);
		//$aryXMLResult = simplexml_load_string($xmldata);
		$aryResult = Topline_xml2array($xmldata,1,"tag",true);
		logModuleCall("sitebuilder","ToplineAPI:GetAccountInfo",$fields,$aryResult);
		if(isset($aryResult["querycustomer"]["user"][0]))
		{
			for ($i=0; $i<=count($aryResult["querycustomer"]["user"]); $i++)
			{
				if($aryResult["querycustomer"]["user"][$i]["domain"] == $strDomainName && (int)$aryResult["querycustomer"]["user"][$i]["status"] < 4)
				{
					$aryResult = $aryResult["querycustomer"]["user"][$i];
					return Array("userid"=>$aryResult["domain_attr"]["id"],"status"=>$aryResult["status"],"creationdate"=>$aryResult["creationdate"],"modifieddate"=>$aryResult["modifieddate"]);
				}
			}
		}
		elseif(isset($aryResult["querycustomer"]["user"]["domain"]))
		{
			if($aryResult["querycustomer"]["user"]["domain"] == $strDomainName && (int)$aryResult["querycustomer"]["user"]["status"] < 4)
			{
				$aryResult = $aryResult["querycustomer"]["user"];
				return Array("userid"=>$aryResult["domain_attr"]["id"],"status"=>$aryResult["status"],"creationdate"=>$aryResult["creationdate"],"modifieddate"=>$aryResult["modifieddate"]);
			}
		}
		return Array();
	}

	public function GetTokenLoginURL($strUserID)
	{
		$strURL = "";
		$strToken = "";
		$fields = array(
			'partner_guid' => urlencode($this->strPartnerGUID),
			'partner_id' => urlencode($this->strPartnerID),
			'userid' => urlencode($strUserID)
		);
		$result = $this->CallService(4,$fields);
		if(strpos($result,"<?xml") !== false)
		{
			$xmldata = $this->CleanUpFrontOfXML($result);
			$xmldata = str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xmldata);
			$aryResult = simplexml_load_string($xmldata);
		}else{
			$aryResult = json_decode($result,true);
		}
		logModuleCall("sitebuilder","ToplineAPI:GetTokenLoginURL",$fields,$aryResult);
		if($aryResult["code"] == "201")
		{
			$strToken = $aryResult["token"];
			if(strlen($strToken) > 1)
			{
				$urlfields = array(
					'sbstkn' => $strToken
				);
				$urlresult = $this->CallService(5,$urlfields);
				logModuleCall("sitebuilder","ToplineAPI:GetTokenLoginURL 2",$urlfields,$urlresult);
				if(strpos($urlresult,"<?xml") !== false)
				{
					$xmldata = $this->CleanUpFrontOfXML($urlresult);
					$xmldata = str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xmldata);
					$aryURLResult = simplexml_load_string($xmldata);
				}else{
					$aryURLResult = json_decode($urlresult,true);
				}
				if($aryURLResult["code"] == "303")
					if(strpos(strtolower("  ".$aryURLResult["location"]),"error") === false)
						$strURL = $aryURLResult["location"];
			}
		}
		return $strURL;
	}

	public function DoDirectLoginURL($strUserID,$strPassword)
	{
		$fields = array(
			'partner_id' => urlencode($this->strPartnerID),
			'userid' => urlencode($strUserID),
			'password' => urlencode($strPassword)
		);
		$result = $this->CallService(5,$fields);
		return $result;
	}

	public function GetBundleID($strBundleName)
	{
		$fields = array(
			'partner_guid' => urlencode($this->strPartnerGUID),
			'partner_id' => urlencode($this->strPartnerID)
		);
		$xmldata = $this->CallService(6,$fields);
		$xmldata = $this->CleanUpFrontOfXML($xmldata);
		$xmldata = str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xmldata);
		$aryBundle = simplexml_load_string($xmldata);
		foreach($aryBundle->bundle as $bundle)
		{
			if(strtolower((string)$bundle->bundlename) == strtolower($strBundleName))
				return $bundle->Attributes()->id;
			elseif(strtolower((string)$bundle->Attributes()->id) == strtolower($strBundleName))
				return $bundle->Attributes()->id;
		}
		return "";
	}

	public function GetBundleName($strBundleID)
	{
		$fields = array(
			'partner_guid' => urlencode($this->strPartnerGUID),
			'partner_id' => urlencode($this->strPartnerID)
		);
		$xmldata = $this->CallService(6,$fields);
		$xmldata = $this->CleanUpFrontOfXML($xmldata);
		$xmldata = str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xmldata);
		$aryBundle = simplexml_load_string($xmldata);
		foreach($aryBundle->bundle as $bundle)
		{
			if(strtolower((string)$bundle->Attributes()->id) == strtolower($strBundleID))
				return $bundle->bundlename;
		}
		return "";
	}

	public function VerifyBundleIDAllowed($strBundleID)
	{
		if(strlen($strBundleID) < 1)
			return false;
		$fields = array(
			'partner_guid' => urlencode($this->strPartnerGUID),
			'partner_id' => urlencode($this->strPartnerID)
		);
		$xmldata = $this->CallService(6,$fields);
		$xmldata = $this->CleanUpFrontOfXML($xmldata);
		$xmldata = str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xmldata);
		$aryBundle = simplexml_load_string($xmldata);
		foreach($aryBundle->bundle as $bundle)
		{
			if(strtolower(trim((string)$bundle->Attributes()->id)) == strtolower($strBundleID))
				return true;
		}
		return false;
	}

	public function GetBundles()
	{
		$fields = array(
			'partner_guid' => urlencode($this->strPartnerGUID),
			'partner_id' => urlencode($this->strPartnerID)
		);
		$xmldata = $this->CallService(6,$fields);
		$xmldata = $this->CleanUpFrontOfXML($xmldata);
		$xmldata = str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xmldata);
		$aryBundle = simplexml_load_string($xmldata);
		return $aryBundle;
	}

	private function CallService($intCallType,$aryFields)
	{
		//logModuleCall("sitebuilder","ToplineAPI:CallService","Calling Service ID: $intCallType, with fields:",$aryFields);

		$this->curl = curl_init();
		curl_setopt($this->curl,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($this->curl,CURLOPT_AUTOREFERER,true); // This make sure will follow redirects
		curl_setopt($this->curl,CURLOPT_FOLLOWLOCATION,true); // This too
		curl_setopt($this->curl,CURLOPT_HEADER,true); // This verbose option for extracting the headers
		//url-ify the data for the POST
		$fields_string = "";
		foreach($aryFields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
		$fields_string = rtrim($fields_string, '&');
		if($this->blnStagingMode == false)
		{
			if($intCallType == 1)
				$url = $this->strAddUserAPIURL;
			elseif($intCallType == 2)
				$url = $this->strModifyUserAPIURL;
			elseif($intCallType == 3)
				$url = $this->strDeleteUserAPIURL;
			elseif($intCallType == 4)
				$url = $this->strRequestTokenAPIURL;
			elseif($intCallType == 5)
				$url = $this->strTokenLoginAPIURL;
			elseif($intCallType == 6)
				$url = $this->strBundleInfoAPIURL;
			elseif($intCallType == 7)
				$url = $this->strQueryUserAPIURL;
		}else{
			if($intCallType == 1)
				$url = $this->strAddUserTestAPIURL;
			elseif($intCallType == 2)
				$url = $this->strModifyUserTestAPIURL;
			elseif($intCallType == 3)
				$url = $this->strDeleteUserTestAPIURL;
			elseif($intCallType == 4)
				$url = $this->strRequestTokenTestAPIURL;
			elseif($intCallType == 5)
				$url = $this->strTokenLoginTestAPIURL;
			elseif($intCallType == 6)
				$url = $this->strBundleInfoTestAPIURL;
			elseif($intCallType == 7)
				$url = $this->strQueryUserTestAPIURL;
		}
		$this->url = $url;
		//set the url, POST data, etc
		curl_setopt($this->curl,CURLOPT_URL, $url);
		curl_setopt($this->curl,CURLOPT_POST,true);
		curl_setopt($this->curl,CURLOPT_POSTFIELDS, $fields_string);
		if(substr($url,0,8) == "https://")
		{
			curl_setopt($this->curl,CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($this->curl,CURLOPT_SSL_VERIFYHOST, 2);
			//curl_setopt($this->curl,CURLOPT_SSLVERSION,3);
		}
		//curl_setopt($this->curl,CURLOPT_VERBOSE, 0);

		//execute post
		$r = curl_exec($this->curl);
		//print_r($r);

		$this->response = "";
		$this->headers = array();
		$this->treatResponse($r);

		//echo 'Curl error: ' . curl_error($this->curl);

		//close connection
		curl_close($this->curl);

		//logModuleCall("sitebuilder","ToplineAPI:CallService","Calling Service ID: $intCallType, with results:",$this->response);

		return $this->response;
	}
	private function CleanUpFrontOfXML($xmldata)
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
	/*
	* Treats the Response for extracting the Headers and Response
	*/ 
	private function treatResponse($r) {
        	if($r == null or strlen($r) < 1) {
	            return;
        	}
	        $parts  = explode("\n\r",$r); // HTTP packets define that Headers end in a blank line (\n\r) where starts the body
        	if(preg_match('@HTTP/1.[0-1] 100 Continue@',$parts[0])) {
	            // Continue header must be bypass
        	    for($i=1;$i<count($parts);$i++) {
                	$parts[$i - 1] = trim($parts[$i]);
	            }
        	    unset($parts[count($parts) - 1]);
	        }
        	preg_match("@Content-Type: ([a-zA-Z0-9-]+/?[a-zA-Z0-9-]*)@",$parts[0],$reg);// This extract the content type
	        $this->headers['content-type'] = $reg[1];
        	preg_match("@HTTP/1.[0-1] ([0-9]{3}) ([a-zA-Z ]+)@",$parts[0],$reg); // This extracts the response header Code and Message
	        $this->headers['code'] = $reg[1];
        	$this->headers['message'] = $reg[2];
	        $this->response = "";
        	for($i=1;$i<count($parts);$i++) {//This make sure that exploded response get back togheter
	            if($i > 1) {
        	        $this->response .= "\n\r";
	            }
        	    $this->response .= $parts[$i];
	        }
	}
	private function find_in_array($string, $array = array ())
	{
		if(!isset($this->mblnTurnOffStrToLowerForFinding))
			$this->mblnTurnOffStrToLowerForFinding = False;
		if(($this->mblnTurnOffStrToLowerForFinding == False) && (!is_numeric($string)))
			$string = strtolower($string);
		$result = false;
		if(in_array($string,$array) == true)
			return true;
		foreach ($array as $key => $value) {
			//unset ($array[$key]);
			if(($this->mblnTurnOffStrToLowerForFinding == False) && (!is_numeric($value)))
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
	private function xml2array($contents, $get_attributes=1, $priority = 'tag', $blnSetAttributesToCustomCode = false) {
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
        	    if(!is_array($current) or (!$this->find_in_array($tag, array_keys($current)))) { //Insert New tag
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

    $language = $_SESSION['Language'];

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
?>