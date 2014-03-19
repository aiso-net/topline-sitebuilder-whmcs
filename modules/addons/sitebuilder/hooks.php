<?php

require_once dirname(__FILE__) . '/sitebuilder_functions.php';

function sitebuilder_client_area_page($vars)
{
	global $_LANG;
	$intServiceRID = $vars['id'];
	$intProductRID = $vars['pid'];
	$intClientRID = $vars['clientsdetails']['id'];
	//-----------------------------
	// See If WHMCS Product Is Tied To A Topline Bundle
	//-----------------------------
	$table = "mod_sitebuilder_bundlexproducts";
	$fields = "intRecordID,txtYolaBundleID";
	$where = array("tblproducts_id"=>$intProductRID);
	$result = select_query($table,$fields,$where);
	$data = mysql_fetch_array($result);
	$intYolaProductBundleRID = $data['intRecordID'];
	$strYolaBundleIDSelected = $data['txtYolaBundleID'];
	if(is_numeric($intYolaProductBundleRID))
		$intYolaProductBundleRID = (int)$intYolaProductBundleRID;
	else
		$intYolaProductBundleRID = 0;
	//-----------------------------
	if($intYolaProductBundleRID > 0)
	{
		//-----------------------------
		// See if its a trial product
		//-----------------------------
		// Grab The Product Name To Search For Trial Word
		//-----------------------------
		$table = "tblproducts";
		$fields = "name,description,autoterminatedays";
		$where = array("id"=>$intProductRID);
		$result = select_query($table,$fields,$where);
		$data = mysql_fetch_array($result);
		$strProductName = $data['name'];
		$strProductDescription = $data['description'];
		$intAutoTerminateDays = $data['autoterminatedays'];
		//-----------------------------
		// Get The Trial Word To Search For In Product Name/Description
		//-----------------------------
		$strTrialWord = Topline_GetGlobalModuleSetting('txtTrialWord',true);
		if(strpos($strProductName,$strTrialWord) !== false || strpos($strProductDescription,$strTrialWord) !== false)
		{
			$blnLoginURLValid = Topline_DisplayProductDetailsLoginLink($intClientRID,$intServiceRID,true);
			if($blnLoginURLValid == true)
			{
				$strTrialLoginLinkText = $_LANG["toplinetrialloginlinktext"];
				$strTrialExpiresText = $_LANG["toplinetrialexpirestext"];
				$strURLToPrintData = Topline_GetClientAreaProductLoginLinkHTML();
				if(strlen($strURLToPrintData) == 0)
					$strURLToPrintData = '<a target="_blank" href="{loginurl}"><b>' . $strTrialLoginLinkText . '</b></a><br/>';
				$strURLToPrintData = str_replace('{loginurl}','index.php?m=sitebuilder&t=2&a=login&id=' . $intServiceRID,$strURLToPrintData);
				if(strlen($strTrialExpiresText) > 0)
				{
					$table = "tblhosting";
					$fields = "regdate";
					$where = array("id"=>$intServiceRID);
					$hostingresult = select_query($table,$fields,$where);
					$hostingdata = mysql_fetch_array($hostingresult);
					$strRegDate = $hostingdata['regdate'];
					if(strlen($strRegDate) > 0)
					{
						$strWHMCSDateFormat = str_replace("MM","m",$GLOBALS['CONFIG']['DateFormat']);
						$strWHMCSDateFormat = str_replace(".","-",$strWHMCSDateFormat);
						$strWHMCSDateFormat = str_replace("/","-",$strWHMCSDateFormat);
						$strWHMCSDateFormat = str_replace("YYYY","Y",$strWHMCSDateFormat);
						$strWHMCSDateFormat = str_replace("DD","d",$strWHMCSDateFormat);
						$strTrialExpirationDate = Topline_dateadd($strRegDate,$intAutoTerminateDays,0,0,False,$strWHMCSDateFormat);
						$strURLToPrintData = str_replace("$DATE$",$strTrialExpirationDate,"$strURLToPrintData$strTrialExpiresText");
					}
				}
				return array('moduleclientarea'=> $strURLToPrintData);
			}
		}
	}
}

function sitebuilder_after_module_create($vars)
{
	logModuleCall("sitebuilder","after_module_create","Staring function...",$vars);
	//-----------------------------
	// Grab the info from whmcs thats passed
	//-----------------------------
	$intServiceRID = $vars['params']['serviceid'];
	$strDomainName = $vars['params']['domain'];
	$strServiceUsername = $vars['params']['username'];	// WHMCS Generated Username
	$strServicePassword = $vars['params']['password'];	//WHMCS Generated Password
	$intProductRID = $vars['params']['pid'];
	$intServerRID = $vars['params']['serverid'];
	$strProductType = strtolower($vars['params']['producttype']);
	$strServerModuleName = strtolower($vars['params']['moduletype']);
	$aryProductConfigOptions = $vars['params']['configoptionX'];
	$aryCustomFields = $vars['params']['customfields'];
	$aryConfigOptions = $vars['params']['configoptions'];
	//-----------------------------
	// See If WHMCS Product Is Tied To A Topline Bundle
	//-----------------------------
	$table = "mod_sitebuilder_bundlexproducts";
	$fields = "intRecordID,txtYolaBundleID";
	$where = array("tblproducts_id"=>$intProductRID);
	$result = select_query($table,$fields,$where);
	$data = mysql_fetch_array($result);
	$intYolaProductBundleRID = $data['intRecordID'];
	$strYolaBundleIDSelected = $data['txtYolaBundleID'];
	if(is_numeric($intYolaProductBundleRID))
		$intYolaProductBundleRID = (int)$intYolaProductBundleRID;
	else
		$intYolaProductBundleRID = 0;
	//-----------------------------
	if($intYolaProductBundleRID > 0)
	{
		logModuleCall("sitebuilder","after_module_create","Topline Product Bundle Assigned To This Product. PID: " . $intProductRID . " , YBID: " . $strYolaBundleIDSelected);
		//-----------------------------
		// If its a Topline trial create a trial account instead. Tthe trial product has the autorelease module assigned, since we dont want to create anything on the server yet.
		//-----------------------------
		// Grab The Product Name To Search For Trial Word
		//-----------------------------
		$table = "tblproducts";
		$fields = "name,description";
		$where = array("id"=>$intProductRID);
		$result = select_query($table,$fields,$where);
		$data = mysql_fetch_array($result);
		$strProductName = $data['name'];
		$strProductDescription = $data['description'];
		//-----------------------------
		// Get The Trial Word To Search For In Product Name/Description
		//-----------------------------
		$strTrialWord = Topline_GetGlobalModuleSetting('txtTrialWord',true);
		if(strpos($strProductName,$strTrialWord) !== false || strpos($strProductDescription,$strTrialWord) !== false)
		{
			logModuleCall("sitebuilder","after_module_create","Trial Word Found, Doing Trial Account Creation Instead.");
			$intYolaAccountStatus = 2;	// Account Is A Trial
		}else{
			$intYolaAccountStatus = 1;	// Account Is Active
		}
		//-----------------------------
		// Get Package RecordID And User ID For Hosting Product
		//-----------------------------
		$table = "tblhosting";
		$fields = "packageid,userid";
		$where = array("id"=>$intServiceRID);
		$result = select_query($table,$fields,$where);
		$servicedata = mysql_fetch_array($result);
		$intPackageRID = $servicedata["packageid"];
		$intUserRID = $servicedata["userid"];
		if(is_numeric($intPackageRID))
			$intPackageRID = (int)$intPackageRID;
		else
			$intPackageRID = 0;
		//-----------------------------
		// Get Client Details
		//-----------------------------
		$table = "tblclients";
		$fields = "firstname,lastname,email";
		$where = array("id"=>$intUserRID);
		$result = select_query($table,$fields,$where);
		$clientdata = mysql_fetch_array($result);
		$strClientFirstName = $clientdata["firstname"];
		$strClientLastName = $clientdata["lastname"];
		$strClientEmailAddress = $clientdata["email"];
		//--------------
		// Get Yola Login Info From Module Settings
		//--------------
		$aryModuleSettings = Topline_GetModuleSettings();
		if(!is_array($aryModuleSettings))
		{
			logModuleCall("sitebuilder","after_module_create","ERROR: Could Not Get Module Settings",$aryModuleSettings);
			return;
		}
		//-----------------------------
		// Check To See If The Topline User Account Already Exists, If It Does, Then We Dont Need To Save A New Topline User Id
		//-----------------------------
		$Topline = new ToplineAPI;
		$Topline->SetPartnerGUID($aryModuleSettings[0]);
		$Topline->SetPartnerID($aryModuleSettings[1]);
		$blnHasCurrentTrialAccount = False;
		$aryCurrentToplineUserResult = $Topline->GetAccountInfo($strDomainName);
		if(isset($aryCurrentToplineUserResult["status"]))
		{
			if((int)$aryCurrentToplineUserResult["status"] == 2)
			{
				$blnHasCurrentTrialAccount = True;
				$strToplineUserIDToModifyFromTrial = $aryCurrentToplineUserResult["userid"];
				logModuleCall("sitebuilder","after_module_create","Trial Account Found With Same Domain, Will Do Trial To Active Conversion For $strToplineUserIDToModifyFromTrial");
			}
		}
		unset($Topline);
		//-----------------------------
		// Do Topline Creation Of Topline Account And Extra FTP Account As Needed
		//-----------------------------
		if($strServerModuleName != "autorelease")
		{
			//-----------------------------	
			// Get Global Product Custom Field Names Used For Username & FTP Info Storage From DB & Global Server Settings
			//-----------------------------
			$globalcustomfieldnamesdata = Topline_GetGlobalModuleSetting("txtGlobalYolaUserIDProductCustomFieldName,txtGlobalYolaFTPUsernameProductCustomFieldName,txtGlobalYolaFTPPasswordProductCustomFieldName,txtGlobalFTPHostname,txtGlobalFTPHomeDirectory,txtGlobalFTPPort,txtGlobalFTPMode",false);
			//-----------------------------
			// Get Custom Server FTP Settings
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
			// Get Server Info From WHMCS
			//--------------
			$table = "tblservers";
			$fields = "ipaddress,username,password,accesshash";
			$where = array("id"=>$intServerRID);
			$result = select_query($table,$fields,$where);
			$serverdata = mysql_fetch_array($result);
			$strServerIPAddress = $serverdata["ipaddress"];
			$strServerUsername = $serverdata["username"];
			$strServerPassword = decrypt($serverdata["password"]);
			$strServerAccessHash = $serverdata["accesshash"];

			$table = "tblservers";
			$fields = "localipaddress";
			$where = array("id"=>$intServerRID);
			$result = select_query($table,$fields,$where);
			$serverdata2 = mysql_fetch_array($result);
			$strServerLocalIPAddress = $serverdata2["localipaddress"];

			if(!is_numeric($intServerRID))
			{
				logActivity("Topline Creation <font color=\"red\">FAILED</font>. Server RID not defined ($intServerRID).");
				logModuleCall("sitebuilder","after_module_create","Topline Create FAILED, Server RID not defined ($intServerRID).");
			}
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
			logModuleCall("sitebuilder","after_module_create","Function Settings Before Variable Change: Server Address: " . $strServerIPAddress . ", FTP IP Address: " . $strServerFTPIPAddress . ", FTP Directory: " . $strFTPDirectory . ", FTP Port: " . $strFTPPort . ", FTP Mode: " . $intFTPMode . ", User ID PCFN: " . $strYolaUserIDProductCustomFieldName . ", Topline FTP Username PCFN: " . $strYolaFTPUsernameProductCustomFieldName . ", Topline FTP Password PCFN: " . $strYolaFTPPasswordProductCustomFieldName);
			//-----------------------------
			// Make the changes to the settings if there is a variable in it
			//-----------------------------
			$strServerFTPIPAddress = str_replace('$serverip',$strServerIPAddress,$strServerFTPIPAddress);
			$strServerFTPIPAddress = str_replace('$domainname',$strDomainName,$strServerFTPIPAddress);
			$strServerFTPIPAddress = str_replace('$username',$strServiceUsername,$strServerFTPIPAddress);
			$strFTPDirectory = str_replace('$domainname',$strDomainName,$strFTPDirectory);
			$strFTPDirectory = str_replace('$username',$strServiceUsername,$strFTPDirectory);
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
				$blnGetYolaUserID = true;
			}else{
				// Save The Custom Field Value To WHMCS
				$strYolaUserIDProductCustomFieldNameToLookup = $strYolaUserIDProductCustomFieldName;
				$blnGetYolaUserID = false;
			}
			if(strpos($strYolaFTPUsernameProductCustomFieldName,"{") !== false && strpos($strYolaFTPUsernameProductCustomFieldName,"}") !== false)
			{
				// Get The Custom Field Value From WHMCS
				preg_match("/\{([^\]]+)\}/", $strYolaFTPUsernameProductCustomFieldName , $aryYolaFTPUsernameProductCustomFieldNameMatches);
				$strYolaFTPUsernameProductCustomFieldNameToLookup = $aryYolaFTPUsernameProductCustomFieldNameMatches[1];
				$strYolaFTPUsernameProductCustomFieldNameLookupResult = Topline_GetWHMCSCustomFieldValue($strYolaFTPUsernameProductCustomFieldNameToLookup,$intPackageRID,$intServiceRID);
				$strYolaFTPUsernameProductCustomFieldName = str_replace($aryYolaFTPUsernameProductCustomFieldNameMatches[0],$strYolaFTPUsernameProductCustomFieldNameLookupResult,$strYolaFTPUsernameProductCustomFieldName);
				$blnGetFTPUsername = true;
			}else{
				// Save The Custom Field Value To WHMCS
				$strYolaFTPUsernameProductCustomFieldNameToLookup = $strYolaFTPUsernameProductCustomFieldName;
				$blnGetFTPUsername = false;
			}
			if(strpos($strYolaFTPPasswordProductCustomFieldName,"{") !== false && strpos($strYolaFTPPasswordProductCustomFieldName,"}") !== false)
			{
				// Get The Custom Field Value From WHMCS
				preg_match("/\{([^\]]+)\}/", $strYolaFTPPasswordProductCustomFieldName , $aryYolaFTPPasswordProductCustomFieldNameMatches);
				$strYolaFTPPasswordProductCustomFieldNameToLookup = $aryYolaFTPPasswordProductCustomFieldNameMatches[1];
				$strYolaFTPPasswordProductCustomFieldNameLookupResult = Topline_GetWHMCSCustomFieldValue($strYolaFTPPasswordProductCustomFieldNameToLookup,$intPackageRID,$intServiceRID);
				$strYolaFTPPasswordProductCustomFieldName = str_replace($aryYolaFTPPasswordProductCustomFieldNameMatches[0],$strYolaFTPPasswordProductCustomFieldNameLookupResult,$strYolaFTPPasswordProductCustomFieldName);
				$blnGetFTPPassword = true;
			}else{
				// Save The Custom Field Value To WHMCS
				$strYolaFTPPasswordProductCustomFieldNameToLookup = $strYolaFTPPasswordProductCustomFieldName;
				$blnGetFTPPassword = false;
			}
	
			$strCustomFieldChangeResult = "";
			if($blnGetYolaUserID == true)
				$strCustomFieldChangeResult .= "GetYUID: yes,";
			else
				$strCustomFieldChangeResult .= "GetYUID: no,";
			if($blnGetFTPUsername == true)
				$strCustomFieldChangeResult .= "GetFTPU: yes,";
			else
				$strCustomFieldChangeResult .= "GetFTPU: no,";
			if($blnGetFTPPassword == true)
				$strCustomFieldChangeResult .= "GetFTPP: yes";
			else
				$strCustomFieldChangeResult .= "GetFTPP: no";
			logModuleCall("sitebuilder","after_module_create","Function Settings After Variable Change: Server Address: " . $strServerIPAddress . ", FTP IP Address: " . $strServerFTPIPAddress . ", FTP Directory: " . $strFTPDirectory . ", FTP Port: " . $strFTPPort . ", FTP Mode: " . $intFTPMode . ", User ID PCFN: " . $strYolaUserIDProductCustomFieldName . ", Topline FTP Username PCFN: " . $strYolaFTPUsernameProductCustomFieldName . ", Topline FTP Password PCFN: " . $strYolaFTPPasswordProductCustomFieldName . ". " . $strCustomFieldChangeResult);

			//-----------------------------
			// cPanel Server Module
			//-----------------------------
			$blnFTPAccountOk = false;
			if($strServerModuleName == "cpanel")
			{
				if(strlen($strServerIPAddress) < 7)
				{
					logActivity("Topline Creation <font color=\"red\">FAILED</font>. cPanel Server IP not defined ($strServerIPAddress).");
					logModuleCall("sitebuilder","after_module_create","Topline Create FAILED, cPanel Server IP not defined ($strServerIPAddress).");
				}
				//-----------------------------
				// Create new FTP account for Topline to use
				//-----------------------------
				//--------------
				// Set FTP And Yola ID Info If Not Already Set
				//--------------
				$strUserAccountName = $strServiceUsername;
	
				if($blnGetFTPUsername == false)
					$strNewFTPUsername = $strUserAccountName . "_sb";
				else
					$strNewFTPUsername = $strYolaFTPUsernameProductCustomFieldName;

				if($blnGetYolaUserID == false)
					$strNewYolaUserID = $strNewFTPUsername;
				else
					$strNewYolaUserID = $strYolaUserIDProductCustomFieldName;
	
				if($blnGetFTPPassword == false)
					$strNewFTPPassword = Topline_GenerateRandomPassword(12,false);
				else
					$strNewFTPPassword = $strYolaFTPPasswordProductCustomFieldName;
	
				$strNewYolaUserPassword = $strNewFTPPassword;
				//--------------
				logModuleCall("sitebuilder","after_module_create","Running cPanel Module Create. Topline User ID: " . $strNewYolaUserID . ", FTP Username: " . $strNewFTPUsername . ", FTP Password: " . $strNewFTPPassword);
				//--------------
				// Next Create The Yola FTP Account In Control Panel
				//--------------
				require_once dirname(__FILE__) . "/cpclasses/cpanel-xmlapi.php";
	
				$cpanelxmlapi = new xmlapi($strServerIPAddress);
	
				if(strlen($strServerAccessHash) > 1)
				{
					$cpanelxmlapi->hash_auth($strServerUsername,$strServerAccessHash);
				}else{
					$cpanelxmlapi->password_auth($strServerUsername,$strServerPassword);
				}
	
				$cpanelxmlapi->return_xml(1);
				$cpanelxmlapi->set_debug(0);
				//--------------
				// Get The users Home Directory
				//--------------
				//$strXMLServerResult = $cpanelxmlapi->api2_query($strUserAccountName, "Fileman", "getdir");
				//$aryServerResult = Topline_ConvertCPanelXMLToArray($strXMLServerResult);
				//$strFTPHomeDirectory = urldecode($aryServerResult["cpanelresult"]["data"]["dir"]);
				//logModuleCall("sitebuilder","after_module_create","cPanel FTP Home Directory Result",$aryServerResult,"Home Directory: " . $strFTPHomeDirectory);
				$strFTPHomeDirectory = "/";
				//--------------
				// Get Users Disk Quota
				//--------------
				$strXMLServerResult = $cpanelxmlapi->api2_query($strUserAccountName, "Fileman", "getdiskinfo");
				$aryServerResult = Topline_ConvertCPanelXMLToArray($strXMLServerResult);
				$intDiskQuota = $aryServerResult["cpanelresult"]["data"]["spacelimit"];
				if(is_numeric($intDiskQuota))
				{
					$intDiskQuota = (int)$intDiskQuota;
					$intDiskQuotaInMB = $intDiskQuota / 1024 / 1024;
				}else{
					$intDiskQuota = 100;	// Default To 100 MB
				}
				logModuleCall("sitebuilder","after_module_create","cPanel FTP Users Disk Quota Result",$aryServerResult,"Disk Quota Returned: " . $intDiskQuota . " Bytes. Convered: " . $intDiskQuotaInMB . " MB");
				//--------------
				// Create New Yola FTP Account
				//--------------
				$strXMLServerResult = $cpanelxmlapi->api2_query($strUserAccountName, "Ftp", "addftp", array(user=>$strNewFTPUsername, pass=>$strNewFTPPassword, quota=>$intDiskQuotaInMB, homedir=>$strFTPHomeDirectory) );
				$aryServerResult = Topline_ConvertCPanelXMLToArray($strXMLServerResult);
				if((int)$aryServerResult["cpanelresult"]["data"]["result"] == 1)
					$blnFTPAccountOk = true;
				logModuleCall("sitebuilder","after_module_create","cPanel FTP User Create Result",$aryServerResult);
				if($blnFTPAccountOk == true)
					$strFTPAccountOk = "Good";
				else
					$strFTPAccountOk = "Bad";				
				logModuleCall("sitebuilder","after_module_create","cPanel FTP Create Result",$aryServerResult,$strFTPAccountOk);
				//--------------
				// Convert FTP Username Created Into cPanel Formatted FTP Username
				//--------------
				$strNewFTPUsername = $strNewFTPUsername . "@" . $strDomainName;
				//--------------
				// Save Yola Info Into Customers Product Custom Fields If Needed
				//--------------
				if($blnHasCurrentTrialAccount == True)
				{
					logModuleCall("sitebuilder","after_module_create","cPanel Module, Not Saving Topline User ID Due To User Account Account Already Exists As Trial Account");
					$blnGetYolaUserID = True;
					$strNewYolaUserID = $strToplineUserIDToModifyFromTrial;
				}else{
					if($blnGetYolaUserID != true)
					{
						Topline_SaveWHMCSCustomFieldValue($strYolaUserIDProductCustomFieldNameToLookup,$intPackageRID,$intServiceRID,$strNewYolaUserID);
						logModuleCall("sitebuilder","after_module_create","cPanel Module, Saving Topline User ID To WHMCS Service");
					}
				}
				if($blnGetFTPUsername != true)
				{
					Topline_SaveWHMCSCustomFieldValue($strYolaFTPUsernameProductCustomFieldName,$intPackageRID,$intServiceRID,$strNewFTPUsername);
					logModuleCall("sitebuilder","after_module_create","cPanel Module, Saving FTP Username To WHMCS Service");
				}
				if($blnGetFTPPassword != true)
				{
					Topline_SaveWHMCSCustomFieldValue($strYolaFTPPasswordProductCustomFieldName,$intPackageRID,$intServiceRID,$strNewFTPPassword);
					logModuleCall("sitebuilder","after_module_create","cPanel Module, Saving FTP Password To WHMCS Service");
				}
			}
			//-----------------------------
			// Custom Server Module, Just Grab Custom Field Values And Go On To Create Yola Account
			//-----------------------------
			else
			{
				if($blnHasCurrentTrialAccount == True)
				{
					logModuleCall("sitebuilder","after_module_create","Custom Server Module, Not Getting/Saving Topline User ID Due To User Account Account Already Exists As Trial Account");
					$blnGetYolaUserID = True;
					$strNewYolaUserID = $strToplineUserIDToModifyFromTrial;
				}else{
					if(strpos($strYolaUserIDProductCustomFieldName,"{") !== false && strpos($strYolaUserIDProductCustomFieldName,"}") !== false)
					{
						// Get The Custom Field Value From WHMCS
						$strNewYolaUserID = $strYolaUserIDProductCustomFieldName;
					}else{
						// Save The Custom Field Value To WHMCS
						$strYolaUserIDProductCustomFieldNameToLookup = $strYolaUserIDProductCustomFieldName;
						$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
						$length = 10;
						if(strlen($strServiceUsername) < 1)
						{
							if(strpos($strClientEmailAddress,"@") !== false)
							{
								$aryClientEmailAddress = explode("@",$strClientEmailAddress);
								$strServiceUsername = $aryClientEmailAddress[0];
							}else{
								$randomString = '';
								for ($i = 0; $i < $length; $i++) {
									$randomString .= $characters[rand(0, strlen($characters) - 1)];
								}
								$strServiceUsername = $randomString;
							}
						}
						$strNewYolaUserID = $strServiceUsername . "-" . date("mdY");
						//--------------
						// Check To See If Username Already Exists In WHMCS, If So Then Re-generate
						//--------------
						$table = "tblhosting";
						$fields = "id,userid";
						$where = array("username"=>$strNewYolaUserID);
						$result = select_query($table,$fields,$where);
						$serviceusercheckdata = mysql_fetch_array($result);
						$intUserIDUsernameFound = $serviceusercheckdata["id"];
						if(is_numeric($intUserIDUsernameFound))
							$intUserIDUsernameFound = (int)$intUserIDUsernameFound;
						else
							$intUserIDUsernameFound = 0;
						if($intUserIDUsernameFound > 0)
						{
							do {
								$randomString = '';
								for ($i = 0; $i < $length; $i++) {
									$randomString .= $characters[rand(0, strlen($characters) - 1)];
								}
								$strServiceUsername = $randomString;
								$strNewYolaUserID = $strServiceUsername . "-" . date("mdY");
		
								$table = "tblhosting";
								$fields = "id,userid";
								$where = array("username"=>$strNewYolaUserID);
								$result = select_query($table,$fields,$where);
								$serviceusercheckdata = mysql_fetch_array($result);
								$intUserIDUsernameFound = $serviceusercheckdata["id"];
								if(is_numeric($intUserIDUsernameFound))
									$intUserIDUsernameFound = (int)$intUserIDUsernameFound;
								else
									$intUserIDUsernameFound = 0;
							} while ($intUserIDUsernameFound != 0);
						}
						$blnGetYolaUserID = false;
					}
					if($blnGetYolaUserID != true)
					{
						Topline_SaveWHMCSCustomFieldValue($strYolaUserIDProductCustomFieldNameToLookup,$intPackageRID,$intServiceRID,$strNewYolaUserID);
						logModuleCall("sitebuilder","after_module_create","Custom Server Module, Saving Topline User ID To WHMCS Service");
					}
				}

				$strNewFTPUsername = $strYolaFTPUsernameProductCustomFieldName;
				$strNewFTPPassword = $strYolaFTPPasswordProductCustomFieldName;
				$strNewYolaUserPassword = $strNewFTPPassword;
	
				logModuleCall("sitebuilder","after_module_create","Running Custom Module Create. Topline User ID: " . $strNewYolaUserID . ", FTP Username: " . $strNewFTPUsername . ", FTP Password: " . $strNewFTPPassword);
	
				if(strlen($strNewYolaUserID) > 0 && strlen($strNewFTPUsername) > 0 && strlen($strNewFTPPassword) > 0)
					$blnFTPAccountOk = true;
	
				if($blnFTPAccountOk == true)
					$strFTPAccountOk = "Good";
				else
					$strFTPAccountOk = "Bad";				
				logModuleCall("sitebuilder","after_module_create","Custom Module FTP Create Result","",$strFTPAccountOk);
			}
			//-----------------------------
			// Create Yola Account Or Set Trial Account To Active Account
			//-----------------------------
			if($blnFTPAccountOk == true)
			{
				logModuleCall("sitebuilder","after_module_create","Creating Topline Account.");
				$Topline = new ToplineAPI;
				$Topline->SetPartnerGUID($aryModuleSettings[0]);
				$Topline->SetPartnerID($aryModuleSettings[1]);
				if($blnHasCurrentTrialAccount == true)
				{
					$blnYolaModifyResult = $Topline->ModifyCustomer($strToplineUserIDToModifyFromTrial,$strNewYolaUserPassword,$strClientFirstName,$strClientLastName,$strClientEmailAddress,"",$strServerFTPIPAddress,$strNewFTPUsername,$strNewFTPPassword,$strFTPPort,$strFTPDirectory,$intFTPMode,$strDomainName,$intYolaAccountStatus,$strYolaBundleIDSelected);
					if($blnYolaModifyResult == true)
					{
						logActivity("Topline Trial User Account Converted To Active Mode Successfully");
						logModuleCall("sitebuilder","after_module_create","Topline Trial User Account Converted To Active Mode Successfully");
					}else{
						logActivity("Topline Trial User Account Conversion <font color=\"red\">FAILED</font>");
						logModuleCall("sitebuilder","after_module_create","Topline Trial User Account Conversion FAILED");
					}
				}else{
					$blnYolaCreateResult = $Topline->AddNewCustomer($strNewYolaUserID,$strNewYolaUserPassword,$strClientFirstName,$strClientLastName,$strClientEmailAddress,"",$strServerFTPIPAddress,$strNewFTPUsername,$strNewFTPPassword,$strFTPPort,$strFTPDirectory,$intFTPMode,$strDomainName,$intYolaAccountStatus,$strYolaBundleIDSelected,"","",0,$intFTPProtocol,$intServiceRID);
					if($blnYolaCreateResult == true)
					{
						logActivity("Topline User Account Created Successfully");
						logModuleCall("sitebuilder","after_module_create","Topline User Account Created Successfully");
					}else{
						logActivity("Topline User Account Creation <font color=\"red\">FAILED</font>");
						logModuleCall("sitebuilder","after_module_create","Topline User Account Create FAILED");
					}
				}
			}else{
				logActivity("Topline User Account Creation <font color=\"red\">FAILED</font>. FTP Account For SiteBuilder Not Verfied As Created.");
			}
		}
		elseif($intYolaAccountStatus == 2 && $strServerModuleName == "autorelease")
		{
			logModuleCall("sitebuilder","after_module_create","Trial Account Type With AutoRelease WHMCS Module Selected...");
			//-----------------------------
			// Get Global Product Custom Field Names Used For Username & FTP Info Storage From DB & Global Server Settings
			//-----------------------------
			$globalcustomfieldnamesdata = Topline_GetGlobalModuleSetting("txtGlobalYolaUserIDProductCustomFieldName");
			//-----------------------------
			// Get Custom Module Product Custom Field Names Used For Username & FTP Info Storage From DB
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
				$strYolaUserIDProductCustomFieldName = $globalcustomfieldnamesdata["txtGlobalYolaUserIDProductCustomFieldName"];
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
				$blnGetYolaUserID = true;
			}else{
				// Save The Custom Field Value To WHMCS
				$strYolaUserIDProductCustomFieldNameToLookup = $strYolaUserIDProductCustomFieldName;
				$blnGetYolaUserID = false;
			}
			//-----------------------------
			// Setup Yola User ID
			//-----------------------------
			logModuleCall("sitebuilder","after_module_create","Setting Up Topline User ID.");
			if($blnGetYolaUserID == false || strlen($strYolaUserIDProductCustomFieldName) < 1)
			{
				logModuleCall("sitebuilder","after_module_create","Create Topline User ID Or Retrieved User ID ($strYolaUserIDProductCustomFieldName) Blank");
				$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
				$length = 10;
				if(strlen($strServiceUsername) < 1)
				{
					if(strpos($strClientEmailAddress,"@") !== false)
					{
						$aryClientEmailAddress = explode("@",$strClientEmailAddress);
						$strServiceUsername = $aryClientEmailAddress[0];
					}else{
						$randomString = '';
						for ($i = 0; $i < $length; $i++) {
							$randomString .= $characters[rand(0, strlen($characters) - 1)];
						}
						$strServiceUsername = $randomString;
					}
				}
				$strNewYolaUserID = $strServiceUsername . "-" . date("mdY");
				//--------------
				// Check To See If Username Already Exists In WHMCS, If So Then Re-generate
				//--------------
				$table = "tblhosting";
				$fields = "id,userid";
				$where = array("username"=>$strNewYolaUserID);
				$result = select_query($table,$fields,$where);
				$serviceusercheckdata = mysql_fetch_array($result);
				$intUserIDUsernameFound = $serviceusercheckdata["id"];
				if(is_numeric($intUserIDUsernameFound))
					$intUserIDUsernameFound = (int)$intUserIDUsernameFound;
				else
					$intUserIDUsernameFound = 0;
				if($intUserIDUsernameFound > 0)
				{
					do {
						$randomString = '';
						for ($i = 0; $i < $length; $i++) {
							$randomString .= $characters[rand(0, strlen($characters) - 1)];
						}
						$strServiceUsername = $randomString;
						$strNewYolaUserID = $strServiceUsername . "-" . date("mdY");

						$table = "tblhosting";
						$fields = "id,userid";
						$where = array("username"=>$strNewYolaUserID);
						$result = select_query($table,$fields,$where);
						$serviceusercheckdata = mysql_fetch_array($result);
						$intUserIDUsernameFound = $serviceusercheckdata["id"];
						if(is_numeric($intUserIDUsernameFound))
							$intUserIDUsernameFound = (int)$intUserIDUsernameFound;
						else
							$intUserIDUsernameFound = 0;
					} while ($intUserIDUsernameFound != 0);
				}
				//--------------
				// Save Topline User ID To WHMCS Service Custom Field
				//--------------
				Topline_SaveWHMCSCustomFieldValue($strYolaUserIDProductCustomFieldNameToLookup,$intPackageRID,$intServiceRID,$strNewYolaUserID);
				logModuleCall("sitebuilder","after_module_create","Saving Topline User ID To WHMCS Service");
			}else{
				$strNewYolaUserID = $strYolaUserIDProductCustomFieldName;
				logModuleCall("sitebuilder","after_module_create","Get Topline User ID From WHMCS Product Custom Field Name Value");
			}
			if(strlen($strServicePassword) > 0)
				$strNewYolaUserPassword = $strServicePassword;
			else
				$strNewYolaUserPassword = Topline_GenerateRandomPassword(10,true);
			logModuleCall("sitebuilder","after_module_create","Topline User ID Retrieved","User ID: $strNewYolaUserID, Password: $strNewYolaUserPassword");
			//-----------------------------
			// Create Topline Account
			//-----------------------------
			logModuleCall("sitebuilder","after_module_create","Creating Topline Account.");
			$Topline = new ToplineAPI;
			$Topline->SetPartnerGUID($aryModuleSettings[0]);
			$Topline->SetPartnerID($aryModuleSettings[1]);
			$blnYolaCreateResult = $Topline->AddNewCustomer($strNewYolaUserID,$strNewYolaUserPassword,$strClientFirstName,$strClientLastName,$strClientEmailAddress,"","","","","","","",$strDomainName,$intYolaAccountStatus,$strYolaBundleIDSelected,"","",0,$intFTPProtocol,$intServiceRID);
			if($blnYolaCreateResult == true)
			{
				logActivity("Topline User Account (Trial) Created Successfully");
				logModuleCall("sitebuilder","after_module_create","Topline User Account (Trial) Created Successfully");
			}else{
				logActivity("Topline User Account (Trial) Creation <font color=\"red\">FAILED</font>");
				logModuleCall("sitebuilder","after_module_create","Topline User Account (Trial) Create FAILED");
			}
		}
	}else{
		logModuleCall("sitebuilder","after_module_create","No Bundle Tied To This WHMCS Product. PID: " . $intProductRID);
	}
	logModuleCall("sitebuilder","after_module_create","Function finished.");
}

function sitebuilder_pre_module_terminate($vars)
{
	logModuleCall("sitebuilder","pre_module_terminate","Staring function...",$vars);
	//-----------------------------
	// Grab the info from whmcs thats passed
	//-----------------------------
	$intServiceRID = $vars['params']['serviceid'];
	$strDomainName = $vars['params']['domain'];
	$strServiceUsername = $vars['params']['username'];	// WHMCS Generated Username
	$strServicePassword = $vars['params']['password'];	//WHMCS Generated Password
	$intProductRID = $vars['params']['pid'];
	$intServerRID = $vars['params']['serverid'];
	$strProductType = strtolower($vars['params']['producttype']);
	$strServerModuleName = strtolower($vars['params']['moduletype']);
	$aryProductConfigOptions = $vars['params']['configoptionX'];
	$aryCustomFields = $vars['params']['customfields'];
	$aryConfigOptions = $vars['params']['configoptions'];
	//-----------------------------
	// See If WHMCS Product Is Tied To A Topline Bundle
	//-----------------------------
	$table = "mod_sitebuilder_bundlexproducts";
	$fields = "intRecordID,txtYolaBundleID";
	$where = array("tblproducts_id"=>$intProductRID);
	$result = select_query($table,$fields,$where);
	$data = mysql_fetch_array($result);
	$intYolaProductBundleRID = $data['intRecordID'];
	$strYolaBundleIDSelected = $data['txtYolaBundleID'];
	if(is_numeric($intYolaProductBundleRID))
		$intYolaProductBundleRID = (int)$intYolaProductBundleRID;
	else
		$intYolaProductBundleRID = 0;
	//-----------------------------
	if($intYolaProductBundleRID > 0)
	{
		logModuleCall("sitebuilder","pre_module_terminate","Topline Product Bundle Assigned To This Product. PID: " . $intProductRID . " , YBID: " . $strYolaBundleIDSelected);
		//-----------------------------------------
		// Delete Yola Account
		//-----------------------------------------
		//-----------------------------
		// Get Package RecordID For Hosting Product
		//-----------------------------
		$table = "tblhosting";
		$fields = "packageid,userid";
		$where = array("id"=>$intServiceRID);
		$result = select_query($table,$fields,$where);
		$servicedata = mysql_fetch_array($result);
		$intPackageRID = $servicedata["packageid"];
		$intUserRID = $servicedata["userid"];
		if(is_numeric($intPackageRID))
			$intPackageRID = (int)$intPackageRID;
		else
			$intPackageRID = 0;
		//---------------
		// Get Global Product Custom Field Names Used For Yola User ID
		//---------------
		$globalcustomfieldnamesdata = Topline_GetGlobalModuleSetting("txtGlobalYolaUserIDProductCustomFieldName");
		//---------------
		// Get Custom Module Product Custom Field Names Used For Yola User ID
		//---------------
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
			$strYolaUserIDProductCustomFieldName = $globalcustomfieldnamesdata["txtGlobalYolaUserIDProductCustomFieldName"];
		}
		logModuleCall("sitebuilder","pre_module_terminate","Function Settings Before Variable Change: User ID PCFN: " . $strYolaUserIDProductCustomFieldName);
		//---------------
		// Do Work On The Custom Field Names To Determine If We Are Getting Values From WHMCS, Or Creating Our Own And Saving Them To WHMCS
		//---------------
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
		logModuleCall("sitebuilder","pre_module_terminate","Function Settings After Variable Change: User ID PCFN: " . $strYolaUserIDProductCustomFieldName . ".");
		//---------------
		// Assign The Yola User ID From The Custom Field Value From WHMCS To The Variable
		//---------------
		$strYolaUserID = $strYolaUserIDProductCustomFieldName;
		if(strlen($strYolaUserID) > 0)
		{
			//--------------
			// Get Topline Login Info From Module Settings
			//--------------
			$aryModuleSettings = Topline_GetModuleSettings();
			if(!is_array($aryModuleSettings))
			{
				logModuleCall("sitebuilder","pre_module_terminate","ERROR: Could Not Get Module Settings",$aryModuleSettings);
				logActivity("Topline User Account Deletion <font color=\"red\">FAILED</font> for Service ID: $intServiceRID, Domain: $strDomainName. Could Not Get Module Settings.");
				return;
			}
			$Topline = new ToplineAPI;
			$Topline->SetPartnerGUID($aryModuleSettings[0]);
			$Topline->SetPartnerID($aryModuleSettings[1]);
			$blnYolaDeleteResult = $Topline->DeleteCustomer($strYolaUserID);
			if($blnYolaDeleteResult == true)
			{
				logModuleCall("sitebuilder","pre_module_terminate","Account deleted successfully");
				logActivity("Topline User Account Deleted Successfully");
			}else{
				logModuleCall("sitebuilder","pre_module_terminate","Account Deletion FAILED for Service ID: " . $intServiceRID . ", Domain: " . $strDomainName);
				logActivity("Topline User Account Deletion <font color=\"red\">FAILED</font> for Service ID: $intServiceRID, Domain: $strDomainName.");
			}
		}else{
			logModuleCall("sitebuilder","pre_module_terminate","ERROR: Could not get sitebuilder user id.",$strYolaUserID);
			logActivity("Topline User Account Deletion <font color=\"red\">FAILED</font> for Service ID: $intServiceRID, Domain: $strDomainName. Could not get sitebuilder user id.");
		}
		//-----------------------------
	}
	logModuleCall("sitebuilder","pre_module_terminate","Function completed.");
}

function sitebuilder_after_product_upgrade($vars)
{
	logModuleCall("sitebuilder","after_product_upgrade","Function starting....",$vars);

	$intUpgradeFunctionToRun = Topline_GetGlobalModuleSetting("txtUpgradeFunctionToRun",true); // 1 = AfterModuleChangePackage, 2 = AfterProductUpgrade
	if(is_numeric($intUpgradeFunctionToRun))
		$intUpgradeFunctionToRun = (int)$intUpgradeFunctionToRun;
	else
		$intUpgradeFunctionToRun = 1;
	if($intUpgradeFunctionToRun == 1)
		$strUpgradeFunctionToRunType = "AfterModuleChangePackage";
	elseif($intUpgradeFunctionToRun == 2)
		$strUpgradeFunctionToRunType = "AfterProductUpgrade";
	else
		$strUpgradeFunctionToRunType = "Unknown";
	logModuleCall("sitebuilder","after_product_upgrade","Upgrade function type to run: $intUpgradeFunctionToRun ($strUpgradeFunctionToRunType)","");

	$intUpgradeRID = 0;
	$intServiceRID = 0;
	if(isset($vars['upgradeid']))
	{
		if(is_numeric($vars['upgradeid']))
		{
			$intUpgradeRID = $vars['upgradeid'];
		}
	}
	elseif(isset($vars['params']['upgradeid']))
	{
		if(is_numeric($vars['params']['upgradeid']))
		{
			$intUpgradeRID = $vars['params']['upgradeid'];
		}
	}
	if(isset($vars['serviceid']))
	{
		if(is_numeric($vars['serviceid']))
		{
			$intServiceRID = (int)$vars['serviceid'];
		}
	}
	elseif(isset($vars['params']['serviceid']))
	{
		if(is_numeric($vars['params']['serviceid']))
		{
			$intServiceRID = (int)$vars['params']['serviceid'];
		}
	}
	if(($intUpgradeFunctionToRun == 0 || $intUpgradeFunctionToRun == 1) && $intUpgradeRID > 0)
	{
		logModuleCall("sitebuilder","after_product_upgrade","Upgrade function was 0 or 1 and the upgrade RID was greater then 0 ($intUpgradeRID)","");
		return;
	}
	if($intUpgradeFunctionToRun == 2 && $intServiceRID > 0)
	{
		logModuleCall("sitebuilder","after_product_upgrade","Upgrade function was 2 and service id was greater then 0 ($intServiceRID)","");
		return;
	}

	$aryOriginalValuesFromDB = Array();
	$aryNewValues = Array();
	$strOriginalValuesFromDB = "";
	$strNewValuesFromDB = "";
	$strUpgradeType = "";

	if($intServiceRID > 0)
	{
		$table = "tblupgrades";
		$fields = "orderid";
		$where = array("relid"=>$intServiceRID,"paid"=>"Y");
		$sort = "orderid";
		$sortorder = "DESC";
		$limits = "1";
		$getorderrid_select = select_query($table,$fields,$where,$sort,$sortorder,$limits,$join);
		$getorderrid_result = mysql_fetch_array($getorderrid_select);
		$intOrderRID = $getorderrid_result[0];
	}

	if(is_numeric($intOrderRID))
		$intOrderRID = (int)$intOrderRID;
	else
		$intOrderRID = 0;
	if(is_numeric($intUpgradeRID))
		$intUpgradeRID = (int)$intUpgradeRID;
	else
		$intUpgradeRID = 0;

	$table = "tblupgrades";
	$fields = "originalvalue,newvalue,type,relid";
	if($intOrderRID > 0)
	{
		$where = array("relid"=>$intServiceRID,"orderid"=>$intOrderRID); //"paid"=>"Y",
	}
	elseif($intUpgradeRID > 0)
	{
		$where = array("id"=>$intUpgradeRID);
	}
	else
	{
		logModuleCall("sitebuilder","after_product_upgrade","Could Not Find A Order Record ID ($intOrderRID) Or Upgrade Record ID ($intUpgradeRID)","");
		return;
	}
	$upgrade_select = select_query($table,$fields,$where);
	while ($upgrade_row = mysql_fetch_array($upgrade_select))
	{
		$strOriginalValuesFromDB = $strOriginalValuesFromDB . "," . $upgrade_row[0];
		$strNewValuesFromDB = $strNewValuesFromDB . "," . $upgrade_row[1];
		$strUpgradeType = $upgrade_row[2];
		if($intServiceRID < 1)
			$intServiceRID = $upgrade_row[3];
	}
	if(strpos($strOriginalValuesFromDB,",") === false)
		$strOriginalValuesFromDB = $strOriginalValuesFromDB . ",";
	$aryOriginalValuesFromDB = explode(",",$strOriginalValuesFromDB);
	if(strpos($strNewValuesFromDB,",") === false)
		$strNewValuesFromDB = $strNewValuesFromDB . ",";
	$aryNewValuesFromDB = explode(",",$strNewValuesFromDB);
	$aryOriginalValues = Array();
	$intArrayPosition = 0;
	//--------------------------
	// Its a package ugprade
	//--------------------------
	logModuleCall("sitebuilder","after_product_upgrade","Upgrade Type: $strUpgradeType","");
	if($strUpgradeType == 'package')
	{
		//--------------------------
		// Get Old Package RID and New Package RID
		//--------------------------
		for ($i=1; $i<=count($aryOriginalValuesFromDB); $i++)
		{
			if(is_numeric($aryOriginalValuesFromDB[$i]) && is_numeric($aryNewValuesFromDB[$i]))
			{
				$intOldPlanRID = $aryOriginalValuesFromDB[$i];
				$intNewPlanRID = $aryNewValuesFromDB[$i];
				break;
			}
		}
		logModuleCall("sitebuilder","after_product_upgrade","Package upgrade, Old Plan ID: $intOldPlanRID, New Plan ID: $intNewPlanRID","");
		//--------------------------
		// Now Check To Make Sure Old Package And New Package Is A Sitebuilder Assigned Package
		//--------------------------
		$table = "mod_sitebuilder_bundlexproducts";
		$fields = "intRecordID,txtYolaBundleID";
		$where = array("tblproducts_id"=>$intOldPlanRID);
		$result = select_query($table,$fields,$where);
		$data = mysql_fetch_array($result);
		$intYolaProductBundleRIDForOldPlan = $data['intRecordID'];
		$strYolaBundleIDSelectedForOldPlan = $data['txtYolaBundleID'];
		if(is_numeric($intYolaProductBundleRIDForOldPlan))
			$intYolaProductBundleRIDForOldPlan = (int)$intYolaProductBundleRIDForOldPlan;
		else
			$intYolaProductBundleRIDForOldPlan = 0;
		//-----------------------------
		if($intYolaProductBundleRIDForOldPlan > 0)
		{
			$table = "mod_sitebuilder_bundlexproducts";
			$fields = "intRecordID,txtYolaBundleID";
			$where = array("tblproducts_id"=>$intNewPlanRID);
			$result = select_query($table,$fields,$where);
			$data = mysql_fetch_array($result);
			$intYolaProductBundleRIDForNewPlan = $data['intRecordID'];
			$strYolaBundleIDSelectedForNewPlan = $data['txtYolaBundleID'];
			if(is_numeric($intYolaProductBundleRIDForNewPlan))
				$intYolaProductBundleRIDForNewPlan = (int)$intYolaProductBundleRIDForNewPlan;
			else
				$intYolaProductBundleRIDForNewPlan = 0;
			//-----------------------------
			if($intYolaProductBundleRIDForNewPlan > 0)
			{
				// Old And New Plans Are SiteBuilder Plans, Do Upgrade
				//-----------------------------
				// See if its a trial product
				//-----------------------------
				// Grab The Product Name To Search For Trial Word
				//-----------------------------
				$table = "tblproducts";
				$fields = "name,description";
				$where = array("id"=>$intOldPlanRID);
				$result = select_query($table,$fields,$where);
				$data = mysql_fetch_array($result);
				$strProductName = $data['name'];
				$strProductDescription = $data['description'];
				//-----------------------------
				// Get The Trial Word To Search For In Product Name/Description
				//-----------------------------
				$strTrialWord = Topline_GetGlobalModuleSetting('txtTrialWord',true);
				//-----------------------------
				// Is Old Site Builder Plan A Trial, If So Then Do Server Setup And Activate Yola Account
				//-----------------------------
				if(strpos($strProductName,$strTrialWord) !== false || strpos($strProductDescription,$strTrialWord) !== false)
				{
					logModuleCall("sitebuilder","after_product_upgrade","Old Site Builder Plan Is A Trial, Do Server Setup And Set Topline Account To Active...","");
					$table = "tblhosting";
					$fields = "domain,username,password,packageid,server";
					$where = array("id"=>$intServiceRID);
					$result = select_query($table,$fields,$where);
					$hostingdata = mysql_fetch_array($result);
					$table = "tblservers";
					$fields = "type";
					$where = array("id"=>$hostingdata['server']);
					$result = select_query($table,$fields,$where);
					$serverdata = mysql_fetch_array($result);
					$vars['params']['serviceid'] = $intServiceRID;
					$vars['params']['domain'] = $hostingdata['domain'];
					$vars['params']['username'] = $hostingdata['username'];
					$vars['params']['password'] = $hostingdata['password'];
					$vars['params']['pid'] = $hostingdata['packageid'];
					$vars['params']['serverid'] = $hostingdata['server'];
					$vars['params']['moduletype'] = $serverdata['type'];
					$vars['params']['ModifyToplineAccountFromTrial'] = "1";
					$vars['params']['ToplineUserIDToModifyFromTrial'] = Topline_GetYolaUserIDFromWHMCSService($intOldPlanRID);
					logModuleCall("sitebuilder","after_product_upgrade","Calling after_module_create function now and passing variables...",$vars);
					sitebuilder_after_module_create($vars);
				}
				//-----------------------------
				// Change From One Site Builder Plan To Another
				//-----------------------------
				else{
					logModuleCall("sitebuilder","after_product_upgrade","Changing From One Site Builder Plan To Another...","");
					//-----------------------------
					// Get Service Server ID, User ID And Service Password
					//-----------------------------
					$table = "tblhosting";
					$fields = "userid,password,server,domain";
					$where = array("id"=>$intServiceRID);
					$result = select_query($table,$fields,$where);
					$data = mysql_fetch_array($result);
					$intUserRID = $data["userid"];
					$strServicePassword = decrypt($data["password"]);
					$intServerRID = $data["server"];
					$strDomainName = $data["domain"];
					//-----------------------------
					// Get Client Details
					//-----------------------------
					$table = "tblclients";
					$fields = "firstname,lastname,email";
					$where = array("id"=>$intUserRID);
					$result = select_query($table,$fields,$where);
					$clientdata = mysql_fetch_array($result);
					$strClientFirstName = $clientdata["firstname"];
					$strClientLastName = $clientdata["lastname"];
					$strClientEmailAddress = $clientdata["email"];
					//-----------------------------
					// Get Custom Server FTP Settings
					//-----------------------------
					list($strServerFTPIPAddress,$strFTPDirectory,$strFTPPort,$intFTPMode) = Topline_GetCustomFTPServerSettings($intServerRID,$intServiceRID);
					//--------------
					// Get FTP Username, Password And Yola User ID
					//--------------
					list($strNewFTPUsername,$strNewFTPPassword) = Topline_GetCustomFTPLoginInfo($intNewPlanRID,$intServiceRID);
					if(strlen($strNewFTPUsername) < 1)
						list($strNewFTPUsername,$strNewFTPPassword) = Topline_GetCustomFTPLoginInfo($intOldPlanRID,$intServiceRID);
					//-----------------------------
					// Get Topline User ID
					//-----------------------------
					$strToplineUserIDToModify = Topline_GetYolaUserIDFromWHMCSService($intServiceRID);
					//-----------------------------
					$aryModuleSettings = Topline_GetModuleSettings();
					if(!is_array($aryModuleSettings))
					{
						logModuleCall("sitebuilder","after_module_create","ERROR: Could Not Get Module Settings",$aryModuleSettings);
					} 
					else
					{
						if(strlen($strToplineUserIDToModify) > 0)
						{
							$Topline = new ToplineAPI;
							$Topline->SetPartnerGUID($aryModuleSettings[0]);
							$Topline->SetPartnerID($aryModuleSettings[1]);
							//-----------------------------
							// Get Account Status
							//-----------------------------
							$aryUserResult = $Topline->GetAccountInfo($strDomainName);
							if(isset($aryUserResult["status"]))
							{
								$intYolaAccountStatus = $aryUserResult["status"];
							}
							if(is_numeric($intYolaAccountStatus))
								$intYolaAccountStatus = (int)$intYolaAccountStatus;
							else
								$intYolaAccountStatus = 0;
							//-----------------------------
							logModuleCall("sitebuilder","after_product_upgrade","Modifing Topline User ($strToplineUserIDToModify) To New Bundle ID: $strYolaBundleIDSelectedForNewPlan","");
							$blnYolaModifyResult = $Topline->ModifyCustomer($strToplineUserIDToModify,$strServicePassword,$strClientFirstName,$strClientLastName,$strClientEmailAddress,"",$strServerFTPIPAddress,$strNewFTPUsername,$strNewFTPPassword,$strFTPPort,$strFTPDirectory,$intFTPMode,$strDomainName,$intYolaAccountStatus,$strYolaBundleIDSelectedForNewPlan);
							if($blnYolaModifyResult == true)
							{
								logActivity("Topline User Account Plan Type Modified Successfully");
								logModuleCall("sitebuilder","after_module_create","Topline User Account Plan Type Modified Successfully","");
							}else{
								logActivity("Topline User Account Plan Type Modify <font color=\"red\">FAILED</font>");
								logModuleCall("sitebuilder","after_module_create","Topline User Account Plan Type Modify FAILED","");
							}
						}else{
							logModuleCall("sitebuilder","after_product_upgrade","Could Not Get Topline User ID, result was: $strToplineUserIDToModify. Service ID: $intServiceRID","");
						}
					}
				}
			}
		}
	}
	logModuleCall("sitebuilder","after_product_upgrade","Function finished.","");
}

function sitebuilder_after_module_change_package($vars)
{
	logModuleCall("sitebuilder","after_module_change_package","Function starting....",$vars);
	//-----------------------------
	// Grab the info from whmcs thats passed
	//-----------------------------
	$intServiceRID = $vars['params']['serviceid'];
	$strDomainName = $vars['params']['domain'];
	$strServiceUsername = $vars['params']['username'];	// WHMCS Generated Username
	$strServicePassword = $vars['params']['password'];	//WHMCS Generated Password
	$intProductRID = $vars['params']['pid'];
	$intServerRID = $vars['params']['serverid'];
	$strProductType = strtolower($vars['params']['producttype']);
	$strServerModuleName = strtolower($vars['params']['moduletype']);
	$aryProductConfigOptions = $vars['params']['configoptionX'];
	$aryCustomFields = $vars['params']['customfields'];
	$aryConfigOptions = $vars['params']['configoptions'];
	//-----------------------------
	// See If WHMCS Product Is Tied To A Topline Bundle
	//-----------------------------
	$table = "mod_sitebuilder_bundlexproducts";
	$fields = "intRecordID,txtYolaBundleID";
	$where = array("tblproducts_id"=>$intProductRID);
	$result = select_query($table,$fields,$where);
	$data = mysql_fetch_array($result);
	$intYolaProductBundleRID = $data['intRecordID'];
	$strYolaBundleIDSelected = $data['txtYolaBundleID'];
	if(is_numeric($intYolaProductBundleRID))
		$intYolaProductBundleRID = (int)$intYolaProductBundleRID;
	else
		$intYolaProductBundleRID = 0;
	//-----------------------------
	if($intYolaProductBundleRID > 0)
	{
		logModuleCall("sitebuilder","after_module_change_package","Topline Product Bundle Assigned To This Product. PID: " . $intProductRID . " , YBID: " . $strYolaBundleIDSelected);
		//-----------------------------
		// Get Serivce Password
		//-----------------------------
		$strNewYolaUserPassword = $strServicePassword;
		//-----------------------------
		// Get Package RecordID And User ID For Hosting Product
		//-----------------------------
		$table = "tblhosting";
		$fields = "packageid,userid,password";
		$where = array("id"=>$intServiceRID);
		$result = select_query($table,$fields,$where);
		$servicedata = mysql_fetch_array($result);
		$intPackageRID = $servicedata["packageid"];
		$intUserRID = $servicedata["userid"];
		$strServicePassword = decrypt($servicedata["password"]);
		if(is_numeric($intPackageRID))
			$intPackageRID = (int)$intPackageRID;
		else
			$intPackageRID = 0;
		//-----------------------------
		// Get Client Details
		//-----------------------------
		$table = "tblclients";
		$fields = "firstname,lastname,email";
		$where = array("id"=>$intUserRID);
		$result = select_query($table,$fields,$where);
		$clientdata = mysql_fetch_array($result);
		$strClientFirstName = $clientdata["firstname"];
		$strClientLastName = $clientdata["lastname"];
		$strClientEmailAddress = $clientdata["email"];
		//-----------------------------
		// Get Custom Server FTP Settings
		//-----------------------------
		list($strServerFTPIPAddress,$strFTPDirectory,$strFTPPort,$intFTPMode) = Topline_GetCustomFTPServerSettings($intServerRID,$intServiceRID);
		//--------------
		// Get Server Info From WHMCS
		//--------------
		$table = "tblservers";
		$fields = "ipaddress,username,password,accesshash";
		$where = array("id"=>$intServerRID);
		$result = select_query($table,$fields,$where);
		$data = mysql_fetch_array($result);
		$strServerIPAddress = $data["ipaddress"];
		$strServerUsername = $data["username"];
		$strServerPassword = decrypt($data["password"]);
		$strServerAccessHash = $data["accesshash"];

		$table = "tblservers";
		$fields = "localipaddress";
		$where = array("id"=>$intServerRID);
		$result = select_query($table,$fields,$where);
		$data = mysql_fetch_array($result);
		$strServerLocalIPAddress = $data["localipaddress"];

		//--------------
		// Get FTP Username, Password And Yola User ID
		//--------------
		list($strNewFTPUsername,$strNewFTPPassword,$strYolaUserID) = Topline_GetCustomFTPLoginInfo($intPackageRID,$intServiceRID,true);
		//--------------
		// Get Yola Login Info From Module Settings
		//--------------
		$aryModuleSettings = Topline_GetModuleSettings();
		if(!is_array($aryModuleSettings))
		{
			logModuleCall("sitebuilder","after_module_create","ERROR: Could Not Get Module Settings",$aryModuleSettings);
			return;
		}
		//-----------------------------
		$Topline = new ToplineAPI;
		$Topline->SetPartnerGUID($aryModuleSettings[0]);
		$Topline->SetPartnerID($aryModuleSettings[1]);
		$blnHasCurrentTrialAccount = false;
		$aryUserResult = $Topline->GetAccountInfo($strDomainName);
		if(isset($aryUserResult["status"]))
		{
			$strToplineUserIDToModify = $aryUserResult["userid"];
			$intYolaAccountStatus = $aryUserResult["status"];
			if($strYolaUserID == $strToplineUserIDToModify)
			{
				if($intYolaAccountStatus == 1)
				{
						logActivity("Topline Trial User Account Conversion Not Needed, Already Converted");
						logModuleCall("sitebuilder","after_module_change_package","Topline Trial User Account Conversion Not Needed, Already Converted","ID To Modify From WHMCS Service: $strYolaUserID, Received ID: $strToplineUserIDToModify, Received ID Status: $intYolaAccountStatus");
				}else{
					$blnYolaModifyResult = $Topline->ModifyCustomer($strToplineUserIDToModify,$strServicePassword,$strClientFirstName,$strClientLastName,$strClientEmailAddress,"",$strServerFTPIPAddress,$strNewFTPUsername,$strNewFTPPassword,$strFTPPort,$strFTPDirectory,$intFTPMode,$strDomainName,$intYolaAccountStatus,$intYolaProductBundleRID);
					if($blnYolaModifyResult == true)
					{
						logActivity("Topline Trial User Account Converted To Active Mode Successfully");
						logModuleCall("sitebuilder","after_module_change_package","Topline Trial User Account Converted To Active Mode Successfully","ID To Modify From WHMCS Service: $strYolaUserID, Received ID: $strToplineUserIDToModify, Received ID Status: $intYolaAccountStatus");
					}else{
						logActivity("Topline Trial User Account Conversion <font color=\"red\">FAILED</font>");
						logModuleCall("sitebuilder","after_module_change_package","Topline Trial User Account Conversion FAILED","ID To Modify From WHMCS Service: $strYolaUserID, Received ID: $strToplineUserIDToModify, Received ID Status: $intYolaAccountStatus");
					}
				}
			}else{
				logActivity("Topline User Account Modification <font color=\"red\">FAILED</font>, Account Not The Same.");
				logModuleCall("sitebuilder","after_module_change_package","Topline User Account Modification FAILED, Account Not The Same.","ID To Modify From WHMCS Service: $strYolaUserID, Received ID: $strToplineUserIDToModify, Received ID Status: $intYolaAccountStatus");
			}
		}else{
			logActivity("Topline User Account Modification <font color=\"red\">FAILED</font>, Account Not Found");
			logModuleCall("sitebuilder","after_module_change_package","Topline User Account Modification FAILED, Account Not Found",$aryUserResult);
		}
	}
	logModuleCall("sitebuilder","after_module_change_package","Function finished.");
}

function sitebuilder_after_configoptions_upgrade($vars)
{

}

function sitebuilder_addon_module_create($vars)
{
	logModuleCall("sitebuilder","addon_module_create","Staring function...",$vars);
	//-----------------------------
	// Grab the info from whmcs thats passed
	//-----------------------------
	$intAddonServiceRID = $vars['id'];
	$intUserRID = $vars['userid'];
	$intServiceRID = $vars['serviceid'];
	$intAddonRID = $vars['addonid'];
	//-----------------------------
	// See If WHMCS Product Add-on Is Tied To A Topline Bundle
	//-----------------------------
	$table = "mod_sitebuilder_bundlexproducts";
	$fields = "intRecordID,txtYolaBundleID";
	$where = array("tbladdons_id"=>$intAddonRID);
	$result = select_query($table,$fields,$where);
	$data = mysql_fetch_array($result);
	$intYolaProductBundleRID = $data['intRecordID'];
	$strYolaBundleIDSelected = $data['txtYolaBundleID'];
	if(is_numeric($intYolaProductBundleRID))
		$intYolaProductBundleRID = (int)$intYolaProductBundleRID;
	else
		$intYolaProductBundleRID = 0;
	//-----------------------------
	if($intYolaProductBundleRID > 0)
	{
		logModuleCall("sitebuilder","addon_module_create","Topline Product Bundle Assigned To This Product. PAOID: " . $intAddonRID . " , YBID: " . $strYolaBundleIDSelected);
		//-----------------------------
		// If its a Topline trial create a trial account instead. Tthe trial product we dont want to create anything on the server yet.
		//-----------------------------
		// Grab The Product Addon Name/Description To Search For Trial Word
		//-----------------------------
		$table = "tbladdons";
		$fields = "name,description";
		$where = array("id"=>$intAddonRID);
		$result = select_query($table,$fields,$where);
		$data = mysql_fetch_array($result);
		$strProductAddonName = $data['name'];
		$strProductAddonDescription = $data['description'];
		//-----------------------------
		// Get The Trial Word To Search For In Product Name/Description
		//-----------------------------
		$strTrialWord = Topline_GetGlobalModuleSetting("txtTrialWord",true);
		if(strpos($strProductAddonName,$strTrialWord) !== false || strpos($strProductAddonDescription,$strTrialWord) !== false)
		{
			logModuleCall("sitebuilder","addon_module_create","Trial Word Found, Doing Trial Account Creation Instead.");
			$intYolaAccountStatus = 2;	// Account Is A Trial
		}else{
			$intYolaAccountStatus = 1;	// Account Is Active
		}



// START HERE TO FINISH


		//-----------------------------
		// Get Package RecordID And User ID For Hosting Product
		//-----------------------------
		$table = "tblhosting";
		$fields = "packageid,userid";
		$where = array("id"=>$intServiceRID);
		$result = select_query($table,$fields,$where);
		$servicedata = mysql_fetch_array($result);
		$intPackageRID = $servicedata["packageid"];
		$intUserRID = $servicedata["userid"];
		if(is_numeric($intPackageRID))
			$intPackageRID = (int)$intPackageRID;
		else
			$intPackageRID = 0;
		//-----------------------------
		// Get Client Details
		//-----------------------------
		$table = "tblclients";
		$fields = "firstname,lastname,email";
		$where = array("id"=>$intUserRID);
		$result = select_query($table,$fields,$where);
		$clientdata = mysql_fetch_array($result);
		$strClientFirstName = $clientdata["firstname"];
		$strClientLastName = $clientdata["lastname"];
		$strClientEmailAddress = $clientdata["email"];
		//-----------------------------
		// Do Topline Creation Based On Account Type (Active/Paid User or Trial User)
		//-----------------------------
		if($intYolaAccountStatus == 1)
		{
			//-----------------------------	
			// Get Global Product Custom Field Names Used For Username & FTP Info Storage From DB & Global Server Settings
			//-----------------------------
			$globalcustomfieldnamesdata = Topline_GetGlobalModuleSetting("txtGlobalYolaUserIDProductCustomFieldName,txtGlobalYolaFTPUsernameProductCustomFieldName,txtGlobalYolaFTPPasswordProductCustomFieldName,txtGlobalFTPHostname,txtGlobalFTPHomeDirectory,txtGlobalFTPPort,txtGlobalFTPMode");
			//-----------------------------
			// Get Custom Server FTP Settings
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
			// Get Server Info From WHMCS
			//--------------
			$table = "tblservers";
			$fields = "ipaddress,username,password,accesshash";
			$where = array("id"=>$intServerRID);
			$result = select_query($table,$fields,$where);
			$data = mysql_fetch_array($result);
			$strServerIPAddress = $data["ipaddress"];
			$strServerUsername = $data["username"];
			$strServerPassword = decrypt($data["password"]);
			$strServerAccessHash = $data["accesshash"];

			$table = "tblservers";
			$fields = "localipaddress";
			$where = array("id"=>$intServerRID);
			$result = select_query($table,$fields,$where);
			$data = mysql_fetch_array($result);
			$strServerLocalIPAddress = $data["localipaddress"];
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
			logModuleCall("sitebuilder","after_module_create","Function Settings Before Variable Change: FTP IP Address: " . $strServerFTPIPAddress . ", FTP Directory: " . $strFTPDirectory . ", FTP Port: " . $strFTPPort . ", FTP Mode: " . $intFTPMode . ", User ID PCFN: " . $strYolaUserIDProductCustomFieldName . ", Topline FTP Username PCFN: " . $strYolaFTPUsernameProductCustomFieldName . ", Topline FTP Password PCFN: " . $strYolaFTPPasswordProductCustomFieldName);
			//-----------------------------
			// Make the changes to the settings if there is a variable in it
			//-----------------------------
			$strServerFTPIPAddress = str_replace('$serverip',$strServerIPAddress,$strServerFTPIPAddress);
			$strServerFTPIPAddress = str_replace('$domainname',$strDomainName,$strServerFTPIPAddress);
			$strServerFTPIPAddress = str_replace('$username',$strServiceUsername,$strServerFTPIPAddress);
			$strFTPDirectory = str_replace('$domainname',$strDomainName,$strFTPDirectory);
			$strFTPDirectory = str_replace('$username',$strServiceUsername,$strFTPDirectory);
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
				$blnGetYolaUserID = true;
			}else{
				// Save The Custom Field Value To WHMCS
				$strYolaUserIDProductCustomFieldNameToLookup = $strYolaUserIDProductCustomFieldName;
				$blnGetYolaUserID = false;
			}
			if(strpos($strYolaFTPUsernameProductCustomFieldName,"{") !== false && strpos($strYolaFTPUsernameProductCustomFieldName,"}") !== false)
			{
				// Get The Custom Field Value From WHMCS
				preg_match("/\{([^\]]+)\}/", $strYolaFTPUsernameProductCustomFieldName , $aryYolaFTPUsernameProductCustomFieldNameMatches);
				$strYolaFTPUsernameProductCustomFieldNameToLookup = $aryYolaFTPUsernameProductCustomFieldNameMatches[1];
				$strYolaFTPUsernameProductCustomFieldNameLookupResult = Topline_GetWHMCSCustomFieldValue($strYolaFTPUsernameProductCustomFieldNameToLookup,$intPackageRID,$intServiceRID);
				$strYolaFTPUsernameProductCustomFieldName = str_replace($aryYolaFTPUsernameProductCustomFieldNameMatches[0],$strYolaFTPUsernameProductCustomFieldNameLookupResult,$strYolaFTPUsernameProductCustomFieldName);
				$blnGetFTPUsername = true;
			}else{
				// Save The Custom Field Value To WHMCS
				$strYolaFTPUsernameProductCustomFieldNameToLookup = $strYolaFTPUsernameProductCustomFieldName;
				$blnGetFTPUsername = false;
			}
			if(strpos($strYolaFTPPasswordProductCustomFieldName,"{") !== false && strpos($strYolaFTPPasswordProductCustomFieldName,"}") !== false)
			{
				// Get The Custom Field Value From WHMCS
				preg_match("/\{([^\]]+)\}/", $strYolaFTPPasswordProductCustomFieldName , $aryYolaFTPPasswordProductCustomFieldNameMatches);
				$strYolaFTPPasswordProductCustomFieldNameToLookup = $aryYolaFTPPasswordProductCustomFieldNameMatches[1];
				$strYolaFTPPasswordProductCustomFieldNameLookupResult = Topline_GetWHMCSCustomFieldValue($strYolaFTPPasswordProductCustomFieldNameToLookup,$intPackageRID,$intServiceRID);
				$strYolaFTPPasswordProductCustomFieldName = str_replace($aryYolaFTPPasswordProductCustomFieldNameMatches[0],$strYolaFTPPasswordProductCustomFieldNameLookupResult,$strYolaFTPPasswordProductCustomFieldName);
				$blnGetFTPPassword = true;
			}else{
				// Save The Custom Field Value To WHMCS
				$strYolaFTPPasswordProductCustomFieldNameToLookup = $strYolaFTPPasswordProductCustomFieldName;
				$blnGetFTPPassword = false;
			}
	
			$strCustomFieldChangeResult = "";
			if($blnGetYolaUserID == true)
				$strCustomFieldChangeResult .= "GetYUID: yes,";
			else
				$strCustomFieldChangeResult .= "GetYUID: no,";
			if($blnGetFTPUsername == true)
				$strCustomFieldChangeResult .= "GetFTPU: yes,";
			else
				$strCustomFieldChangeResult .= "GetFTPU: no,";
			if($blnGetFTPPassword == true)
				$strCustomFieldChangeResult .= "GetFTPP: yes";
			else
				$strCustomFieldChangeResult .= "GetFTPP: no";
			logModuleCall("sitebuilder","after_module_create","Function Settings After Variable Change: FTP IP Address: " . $strServerFTPIPAddress . ", FTP Directory: " . $strFTPDirectory . ", FTP Port: " . $strFTPPort . ", FTP Mode: " . $intFTPMode . ", User ID PCFN: " . $strYolaUserIDProductCustomFieldName . ", Topline FTP Username PCFN: " . $strYolaFTPUsernameProductCustomFieldName . ", Topline FTP Password PCFN: " . $strYolaFTPPasswordProductCustomFieldName . ". " . $strCustomFieldChangeResult);
	
			//-----------------------------
			// cPanel Server Module
			//-----------------------------
			$blnFTPAccountOk = false;
			if($strServerModuleName == "cpanel")
			{
				//-----------------------------
				// Create new FTP account for Topline to use
				//-----------------------------
				//--------------
				// Set FTP And Yola ID Info If Not Already Set
				//--------------
				$strUserAccountName = $strServiceUsername;
	
				if($blnGetFTPUsername == false)
					$strNewFTPUsername = $strUserAccountName . "_sb";
				else
					$strNewFTPUsername = $strYolaFTPUsernameProductCustomFieldName;
	
				if($blnGetYolaUserID == false)
					$strNewYolaUserID = $strNewFTPUsername;
				else
					$strNewYolaUserID = $strYolaUserIDProductCustomFieldName;
	
				if($blnGetFTPPassword == false)
					$strNewFTPPassword = Topline_GenerateRandomPassword(12,false);
				else
					$strNewFTPPassword = $strYolaFTPPasswordProductCustomFieldName;
	
				$strNewYolaUserPassword = $strNewFTPPassword;
				//--------------
				logModuleCall("sitebuilder","after_module_create","Running cPanel Module Create. Topline User ID: " . $strNewYolaUserID . ", FTP Username: " . $strNewFTPUsername . ", FTP Password: " . $strNewFTPPassword);
				//--------------
				// Next Create The Yola FTP Account In Control Panel
				//--------------
				require_once dirname(__FILE__) . "/cpclasses/cpanel-xmlapi.php";
	
				$cpanelxmlapi = new xmlapi($strServerIPAddress);
	
				if(strlen($strServerAccessHash) > 1)
				{
					$cpanelxmlapi->hash_auth($strServerUsername,$strServerAccessHash);
				}else{
					$cpanelxmlapi->password_auth($strServerUsername,$strServerPassword);
				}
	
				$cpanelxmlapi->return_xml(1);
				$cpanelxmlapi->set_debug(0);
				//--------------
				// Get The users Home Directory
				//--------------
				//$strXMLServerResult = $cpanelxmlapi->api2_query($strUserAccountName, "Fileman", "getdir");
				//$aryServerResult = Topline_ConvertCPanelXMLToArray($strXMLServerResult);
				//$strFTPHomeDirectory = urldecode($aryServerResult["cpanelresult"]["data"]["dir"]);
				//logModuleCall("sitebuilder","after_module_create","cPanel FTP Home Directory Result",$aryServerResult,"Home Directory: " . $strFTPHomeDirectory);
				$strFTPHomeDirectory = "/";
				//--------------
				// Get Users Disk Quota
				//--------------
				$strXMLServerResult = $cpanelxmlapi->api2_query($strUserAccountName, "Fileman", "getdiskinfo");
				$aryServerResult = Topline_ConvertCPanelXMLToArray($strXMLServerResult);
				$intDiskQuota = $aryServerResult["cpanelresult"]["data"]["spacelimit"];
				if(is_numeric($intDiskQuota))
				{
					$intDiskQuota = (int)$intDiskQuota;
					$intDiskQuotaInMB = $intDiskQuota / 1024 / 1024;
				}else{
					$intDiskQuota = 100;	// Default To 100 MB
				}
				logModuleCall("sitebuilder","after_module_create","cPanel FTP Users Disk Quota Result",$aryServerResult,"Disk Quota Returned: " . $intDiskQuota . " Bytes. Convered: " . $intDiskQuotaInMB . " MB");
				//--------------
				// Create New Yola FTP Account
				//--------------
				$strXMLServerResult = $cpanelxmlapi->api2_query($strUserAccountName, "Ftp", "addftp", array(user=>$strNewFTPUsername, pass=>$strNewFTPPassword, quota=>$intDiskQuotaInMB, homedir=>$strFTPHomeDirectory) );
				$aryServerResult = Topline_ConvertCPanelXMLToArray($strXMLServerResult);
				if((int)$aryServerResult["cpanelresult"]["data"]["result"] == 1)
					$blnFTPAccountOk = true;
				logModuleCall("sitebuilder","after_module_create","cPanel FTP User Create Result",$aryServerResult);
				if($blnFTPAccountOk == true)
					$strFTPAccountOk = "Good";
				else
					$strFTPAccountOk = "Bad";				
				logModuleCall("sitebuilder","after_module_create","cPanel FTP Create Result",$aryServerResult,$strFTPAccountOk);
				//--------------
				// Convert FTP Username Created Into cPanel Formatted FTP Username
				//--------------
				$strNewFTPUsername = $strNewFTPUsername . "@" . $strDomainName;
				//--------------
				// Save Yola Info Into Customers Product Custom Fields If Needed
				//--------------
				if($blnGetYolaUserID != true)
				{
					Topline_SaveWHMCSCustomFieldValue($strYolaUserIDProductCustomFieldNameToLookup,$intPackageRID,$intServiceRID,$strNewYolaUserID);
					logModuleCall("sitebuilder","after_module_create","cPanel Module, Saving Topline User ID To WHMCS Service");
				}
				if($blnGetFTPUsername != true)
				{
					Topline_SaveWHMCSCustomFieldValue($strYolaFTPUsernameProductCustomFieldName,$intPackageRID,$intServiceRID,$strNewFTPUsername);
					logModuleCall("sitebuilder","after_module_create","cPanel Module, Saving FTP Username To WHMCS Service");
				}
				if($blnGetFTPPassword != true)
				{
					Topline_SaveWHMCSCustomFieldValue($strYolaFTPPasswordProductCustomFieldName,$intPackageRID,$intServiceRID,$strNewFTPPassword);
					logModuleCall("sitebuilder","after_module_create","cPanel Module, Saving FTP Password To WHMCS Service");
				}
			}
			//-----------------------------
			// Custom Server Module, Just Grab Custom Field Values And Go On To Create Yola Account
			//-----------------------------
			else
			{
				$strNewYolaUserID = $strYolaUserIDProductCustomFieldName;
				$strNewFTPUsername = $strYolaFTPUsernameProductCustomFieldName;
				$strNewFTPPassword = $strYolaFTPPasswordProductCustomFieldName;
				$strNewYolaUserPassword = $strNewFTPPassword;
	
				logModuleCall("sitebuilder","after_module_create","Running Custom Module Create. Topline User ID: " . $strNewYolaUserID . ", FTP Username: " . $strNewFTPUsername . ", FTP Password: " . $strNewFTPPassword);
	
				if(strlen($strNewYolaUserID) > 0 && strlen($strNewFTPUsername) > 0 && strlen($strNewFTPPassword) > 0)
					$blnFTPAccountOk = true;
	
				if($blnFTPAccountOk == true)
					$strFTPAccountOk = "Good";
				else
					$strFTPAccountOk = "Bad";				
				logModuleCall("sitebuilder","after_module_create","Custom Module FTP Create Result","",$strFTPAccountOk);
			}
			//-----------------------------
			// Create Yola Account
			//-----------------------------
			if($blnFTPAccountOk == true)
			{
				logModuleCall("sitebuilder","after_module_create","Creating Topline Account.");
				//--------------
				// Get Yola Login Info From Module Settings
				//--------------
				$aryModuleSettings = Topline_GetModuleSettings();
				if(!is_array($aryModuleSettings))
				{
					logModuleCall("sitebuilder","after_module_create","ERROR: Could Not Get Module Settings",$aryModuleSettings);
					return;
				}
				$Topline = new ToplineAPI;
				$Topline->SetPartnerGUID($aryModuleSettings[0]);
				$Topline->SetPartnerID($aryModuleSettings[1]);
				$blnYolaCreateResult = $Topline->AddNewCustomer($strNewYolaUserID,$strNewYolaUserPassword,$strClientFirstName,$strClientLastName,$strClientEmailAddress,"",$strServerFTPIPAddress,$strNewFTPUsername,$strNewFTPPassword,$strFTPPort,$strFTPDirectory,$intFTPMode,$strDomainName,$intYolaAccountStatus,$strYolaBundleIDSelected,"","",0,$intFTPProtocol,$intServiceRID);
				if($blnYolaCreateResult == true)
				{
					logActivity("Topline User Account Created Successfully");
					logModuleCall("sitebuilder","after_module_create","Topline User Account Created Successfully");
				}else{
					logActivity("Topline User Account Creation <font color=\"red\">FAILED</font>");
					logModuleCall("sitebuilder","after_module_create","Topline User Account Create FAILED");
				}
			}else{
				logActivity("Topline User Account Creation <font color=\"red\">FAILED</font>. FTP Account For SiteBuilder Not Verfied As Created.");
			}
		}
		elseif($intYolaAccountStatus == 2)
		{
			//-----------------------------	
			// Get Global Product Custom Field Names Used For Username & FTP Info Storage From DB & Global Server Settings
			//-----------------------------
			$globalcustomfieldnamesdata = Topline_GetGlobalModuleSetting("txtGlobalYolaUserIDProductCustomFieldName");
			//-----------------------------
			// Get Custom Module Product Custom Field Names Used For Username & FTP Info Storage From DB
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
				$strYolaUserIDProductCustomFieldName = $globalcustomfieldnamesdata["txtGlobalYolaUserIDProductCustomFieldName"];
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
				$blnGetYolaUserID = true;
			}else{
				// Save The Custom Field Value To WHMCS
				$strYolaUserIDProductCustomFieldNameToLookup = $strYolaUserIDProductCustomFieldName;
				$blnGetYolaUserID = false;
			}
			//-----------------------------
			// Setup Yola User ID
			//-----------------------------
			logModuleCall("sitebuilder","after_module_create","Setting Up Topline User ID.");
			if($blnGetYolaUserID == false)
			{
				$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
				$length = 10;
				if(strlen($strServiceUsername) < 1)
				{
					if(strpos($strClientEmailAddress,"@") !== false)
					{
						$aryClientEmailAddress = explode("@",$strClientEmailAddress);
						$strServiceUsername = $aryClientEmailAddress[0];
					}else{
						$randomString = '';
						for ($i = 0; $i < $length; $i++) {
							$randomString .= $characters[rand(0, strlen($characters) - 1)];
						}
						$strServiceUsername = $randomString;
					}
				}
				$strNewYolaUserID = $strServiceUsername . "-" . date("mdY");
				//--------------
				// Check To See If Username Already Exists In WHMCS, If So Then Re-generate
				//--------------
				$table = "tblhosting";
				$fields = "id,userid";
				$where = array("username"=>$strNewYolaUserID);
				$result = select_query($table,$fields,$where);
				$serviceusercheckdata = mysql_fetch_array($result);
				$intUserIDUsernameFound = $serviceusercheckdata["id"];
				if(is_numeric($intUserIDUsernameFound))
					$intUserIDUsernameFound = (int)$intUserIDUsernameFound;
				else
					$intUserIDUsernameFound = 0;
				if($intUserIDUsernameFound > 0)
				{
					do {
						$randomString = '';
						for ($i = 0; $i < $length; $i++) {
							$randomString .= $characters[rand(0, strlen($characters) - 1)];
						}
						$strServiceUsername = $randomString;
						$strNewYolaUserID = $strServiceUsername . "-" . date("mdY");

						$table = "tblhosting";
						$fields = "id,userid";
						$where = array("username"=>$strNewYolaUserID);
						$result = select_query($table,$fields,$where);
						$serviceusercheckdata = mysql_fetch_array($result);
						$intUserIDUsernameFound = $serviceusercheckdata["id"];
						if(is_numeric($intUserIDUsernameFound))
							$intUserIDUsernameFound = (int)$intUserIDUsernameFound;
						else
							$intUserIDUsernameFound = 0;
					} while ($intUserIDUsernameFound != 0);
				}
				//--------------
				// Save Topline User ID To WHMCS Service Custom Field
				//--------------
				Topline_SaveWHMCSCustomFieldValue($strYolaUserIDProductCustomFieldNameToLookup,$intPackageRID,$intServiceRID,$strNewYolaUserID);
				logModuleCall("sitebuilder","after_module_create","Saving Topline User ID To WHMCS Service");
			}else{
				$strNewYolaUserID = $strYolaUserIDProductCustomFieldName;
			}
			if(strlen($strServicePassword) > 0)
				$strNewYolaUserPassword = $strServicePassword;
			else
				$strNewYolaUserPassword = Topline_GenerateRandomPassword(10,true);
			logModuleCall("sitebuilder","after_module_create","Topline User ID Retrieved","User ID: $strNewYolaUserID, Password: $strNewYolaUserPassword");
			//--------------
			// Get Topline Login Info From Module Settings
			//--------------
			$aryModuleSettings = Topline_GetModuleSettings();
			if(!is_array($aryModuleSettings))
			{
				logModuleCall("sitebuilder","after_module_create","ERROR: Could Not Get Module Settings",$aryModuleSettings);
				return;
			}
			//-----------------------------
			// Create Topline Account
			//-----------------------------
			logModuleCall("sitebuilder","after_module_create","Creating Topline Account.");
			$Topline = new ToplineAPI;
			$Topline->SetPartnerGUID($aryModuleSettings[0]);
			$Topline->SetPartnerID($aryModuleSettings[1]);
			$blnYolaCreateResult = $Topline->AddNewCustomer($strNewYolaUserID,$strNewYolaUserPassword,$strClientFirstName,$strClientLastName,$strClientEmailAddress,"","","","","","","","",$intYolaAccountStatus,$strYolaBundleIDSelected,"","",0,$intFTPProtocol,$intServiceRID);
			if($blnYolaCreateResult == true)
			{
				logActivity("Topline User Account (Trial) Created Successfully");
				logModuleCall("sitebuilder","after_module_create","Topline User Account (Trial) Created Successfully");
			}else{
				logActivity("Topline User Account (Trial) Creation <font color=\"red\">FAILED</font>");
				logModuleCall("sitebuilder","after_module_create","Topline User Account (Trial) Create FAILED");
			}
		}
	}else{
		logModuleCall("sitebuilder","after_module_create","No Bundle Tied To This WHMCS Product. PID: " . $intProductRID);
	}
	logModuleCall("sitebuilder","after_module_create","Function finished.");
}

function sitebuilder_addon_module_terminate($vars)
{

}

function sitebuilder_server_delete($vars)
{
	// WHMCS Server Deleted, Delete Topline Server Record
	$intServerRID = $vars['serverid'];
	if(!is_numeric($intServerRID))
		$intServerRID = $vars['params']['serverid'];
	if(is_numeric($intServerRID))
	{
		$deleterecordsql = "DELETE FROM mod_sitebuilder_servers WHERE tblservers_id = $intServerRID";
		$deleterecord_row = full_query($deleterecordsql);	
	}
}

function sitebuilder_product_delete($vars)
{
	// WHMCS Product Deleted, Delete Topline Product Records
	$intProductRID = $vars['pid'];
	if(!is_numeric($intProductRID))
		$intProductRID = $vars['params']['pid'];
	if(is_numeric($intProductRID))
	{
		$deleterecordsql = "DELETE FROM mod_sitebuilder_bundlexproducts WHERE tblproducts_id = $intProductRID";
		$deleterecord_row = full_query($deleterecordsql);	
	}
}
function sitebuilder_addon_delete($vars)
{
	// WHMCS Add-on Product Deleted, Delete Topline Add-on Product Records
	$intProductAddonRID = $vars['id'];
	if(!is_numeric($intProductAddonRID))
		$intProductAddonRID = $vars['params']['id'];
	if(is_numeric($intProductAddonRID))
	{
		$deleterecordsql = "DELETE FROM mod_sitebuilder_bundlexproducts WHERE tbladdons_id = $intProductAddonRID";
		$deleterecord_row = full_query($deleterecordsql);	
	}
}

function sitebuilder_trial_hook_pre_send_email($vars) {
	$strEmailTemplateName = $vars['messagename']; # Email template name being sent
	$strRelatedID = $vars['relid']; # Related ID it's being sent for - client ID, invoice ID, etc...

	$hostingtable = "tblhosting";
	$hostingfields = "packageid,regdate";
	$hostingwhere = array("id"=>$strRelatedID);
	$hostingresult = select_query($hostingtable,$hostingfields,$hostingwhere);
	$hostingdata = mysql_fetch_array($hostingresult);
	$intProductRID = $hostingdata['packageid'];
	$strProductRegDate = $hostingdata['regdate'];
	if(is_numeric($intProductRID))
		$intProductRID = (int)$intProductRID;
	else
		$intProductRID = 0;
	if($intProductRID > 0)
	{
		$producttable = "tblproducts";
		$productfields = "autoterminatedays";
		$productwhere = array("id"=>$intProductRID);
		$productresult = select_query($producttable,$productfields,$productwhere);
		$productdata = mysql_fetch_array($productresult);
		$intAutoTerminateDays = $productdata['autoterminatedays'];
		if(is_numeric($intAutoTerminateDays))
		{
			$strWHMCSDateFormat = str_replace("MM","m",$GLOBALS['CONFIG']['DateFormat']);
			$strWHMCSDateFormat = str_replace(".","-",$strWHMCSDateFormat);
			$strWHMCSDateFormat = str_replace("/","-",$strWHMCSDateFormat);
			$strWHMCSDateFormat = str_replace("YYYY","Y",$strWHMCSDateFormat);
			$strWHMCSDateFormat = str_replace("DD","d",$strWHMCSDateFormat);
			$merge_fields = array();
			$merge_fields['autoterminateday'] = Topline_dateadd($strProductRegDate,$intAutoTerminateDays,0,0,False,$strWHMCSDateFormat);
			return $merge_fields;
		}
	}
}

add_hook("ClientAreaPage",5,"sitebuilder_client_area_page");
add_hook("AfterModuleCreate",5,"sitebuilder_after_module_create");
add_hook("AfterModuleChangePackage",5,"sitebuilder_after_module_change_package");
add_hook("PreModuleTerminate",5,"sitebuilder_pre_module_terminate");
add_hook("ServerDelete",5,"sitebuilder_server_delete");
add_hook("ProductDelete",5,"sitebuilder_product_delete");
add_hook("AfterProductUpgrade",5,"sitebuilder_after_product_upgrade");
add_hook("EmailPreSend",11,"sitebuilder_trial_hook_pre_send_email");

//add_hook("AddonActivation",5,"sitebuilder_addon_module_create");
//add_hook("AddonActivated",5,"sitebuilder_addon_module_create");
//add_hook("AddonTerminated",5,"sitebuilder_addon_module_terminate");
//add_hook("AddonDeleted",5,"sitebuilder_addon_module_terminate");

//add_hook("AfterConfigOptionsUpgrade",5,"sitebuilder_after_configoptions_upgrade");
?>