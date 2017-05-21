<?php
//-----------------------------------------
// Version 1.15 - 2/18/17
// cPanel 11.27.x and later. 
// WHMCS 5.0 or later.
//-----------------------------------------

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

require_once dirname(__FILE__) . '/sitebuilder_functions.php';

function sitebuilder_config() {
	$configarray = array(
			"name" => "Topline SiteBuilder Module",
			"description" => "This module allows integration with the topline sitebuilder.",
			"version" => "1.15",
			"author" => "Topline",
			"language" => "english",
			"fields" => array(
			        "PartnerAuthKey" => array ("FriendlyName" => "Partner AuthKey", "Type" => "text", "Size" => "50",
                        	 "Description" => "Your Partner API AuthKey given to you by Topline", "Default" => "" ),
				"BrandID" => array ("FriendlyName" => "Brand ID", "Type" => "text", "Size" => "30",
				 "Description" => "Your Brand ID given to you by Topline", "Default" => "" ),
				"DeleteDBTablesOnUninstall" => array ("FriendlyName" => "Delete DB Tables On Uninstall", "Type" => "yesno", "Size" => "1",
				 "Description" => "Delete DB Tables On Uninstall"),
				"StagingMode" => array ("FriendlyName" => "Use Sandbox API", "Type" => "yesno", "Size" => "1",
				 "Description" => "Use Sandbox/Staging API Instead Of Production")
			)
	);
	return $configarray;
}

function sitebuilder_activate() {
	# Create Custom DB Tables
	$query = "CREATE TABLE IF NOT EXISTS `mod_sitebuilder_bundlexproducts` (`intRecordID` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,`txtYolaBundleID` VARCHAR(150), `tblproducts_id` INT(11), `tbladdons_id` INT(11))";
	$result = mysql_query($query);

	$query = "CREATE TABLE IF NOT EXISTS `mod_sitebuilder_modulexcustomfields` (`intRecordID` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,`txtModuleName` VARCHAR(150), `txtYolaUserIDProductCustomFieldName` VARCHAR(150), `txtYolaFTPUsernameProductCustomFieldName` VARCHAR(150), `txtYolaFTPPasswordProductCustomFieldName` VARCHAR(150))";
	$result = mysql_query($query);

	$query = "CREATE TABLE IF NOT EXISTS `mod_sitebuilder_servers` (`intRecordID` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY , `tblservers_id` INT(11), `txtFTPHostname` VARCHAR(150), `txtFTPHomeDirectory` VARCHAR(150), `intFTPPort` INT(6), `intFTPMode` INT(1))";
	$result = mysql_query($query);

	//$query = "CREATE TABLE IF NOT EXISTS `mod_sitebuilder_settings` (`intRecordID` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY , `txtGlobalYolaUserIDProductCustomFieldName` VARCHAR(150), `txtGlobalYolaFTPUsernameProductCustomFieldName` VARCHAR(150), `txtGlobalYolaFTPPasswordProductCustomFieldName` VARCHAR(150), `txtGlobalFTPHostname` VARCHAR(150), `txtGlobalFTPHomeDirectory` VARCHAR(150), `intGlobalFTPPort` INT(6), `intGlobalFTPMode` INT(2), `txtTrialWord` VARCHAR(50))";
	$query = "CREATE TABLE IF NOT EXISTS `mod_sitebuilder_settings` (`txtSetting` text , `txtValue` text)";
	$result = mysql_query($query);

	# Return Result
	return array('status'=>'success','description'=>'');
	#return array('status'=>'error','description'=>'You can use the error status return to indicate there was a problem activating the module');
	#return array('status'=>'info','description'=>'You can use the info status return to display a message to the user');

}

function sitebuilder_deactivate() {

	$aryModuleSettings = Topline_GetModuleSettings();
	$strDeleteDBTablesOnUninstall = $aryModuleSettings[2];
	if($strDeleteDBTablesOnUninstall == "on")
	{
		# Remove Custom DB Tables
		$query = "DROP TABLE IF EXISTS `mod_sitebuilder_bundlexproducts`;DROP TABLE IF EXISTS `mod_sitebuilder_modulexcustomfields`;DROP TABLE IF EXISTS `mod_sitebuilder_servers`;DROP TABLE IF EXISTS `mod_sitebuilder_settings`";
		$result = mysql_query($query);
		# Return Result
	}
	return array('status'=>'success','description'=>'');
	#return array('status'=>'error','description'=>'If an error occurs you can return an error message for display here');
	#return array('status'=>'info','description'=>'If you want to give an info message to a user you can return it here');
}

function sitebuilder_upgrade($vars) {
	$version = $vars['version'];
 
	// Run SQL Updates for V1.04 or lower to V1.05
	if ($version < 1.05) {
		$query = "ALTER TABLE `mod_sitebuilder_settings` ADD `txtTrialWord` VARCHAR(50); ALTER TABLE `mod_sitebuilder_bundlexproducts` ADD `tbladdons_id` INT(11);";
		$result = mysql_query($query);
	}
 
	// Run SQL Updates for V1.05 to V1.06
	if ($version < 1.06) {
		$table = "mod_sitebuilder_settings";

		$fields = "txtGlobalYolaUserIDProductCustomFieldName,txtGlobalYolaFTPUsernameProductCustomFieldName,txtGlobalYolaFTPPasswordProductCustomFieldName,txtGlobalFTPHostname,txtGlobalFTPHomeDirectory,intGlobalFTPPort,intGlobalFTPMode,txtTrialWord";
		$where = array("intRecordID"=>1);
		$result = select_query($table,$fields,$where);
		$oldsettingdata = mysql_fetch_array($result);
		$strGlobalYolaUserIDProductCustomFieldName = $oldsettingdata['txtGlobalYolaUserIDProductCustomFieldName'];
		$strGlobalYolaFTPUsernameProductCustomFieldName = $oldsettingdata['txtGlobalYolaFTPUsernameProductCustomFieldName'];
		$strGlobalYolaFTPPasswordProductCustomFieldName = $oldsettingdata['txtGlobalYolaFTPPasswordProductCustomFieldName'];
		$strGlobalFTPHostname = $oldsettingdata['txtGlobalFTPHostname'];
		$strGlobalFTPHomeDirectory = $oldsettingdata['txtGlobalFTPHomeDirectory'];
		$intGlobalFTPPort = $oldsettingdata['intGlobalFTPPort'];
		$intGlobalFTPMode = $oldsettingdata['intGlobalFTPMode'];
		$strTrialWord = $oldsettingdata['txtTrialWord'];

		$query = "DROP TABLE `$table`";
		$result = mysql_query($query);

		$query = "CREATE TABLE `$table` (`txtSetting` text , `txtValue` text)";
		$result = mysql_query($query);

		$aryUpdateData = Array("txtSetting"=>"txtGlobalYolaUserIDProductCustomFieldName","txtValue"=>$strGlobalYolaUserIDProductCustomFieldName);
		$newid = insert_query($table,$aryUpdateData);

		$aryUpdateData = Array("txtSetting"=>"txtGlobalYolaFTPUsernameProductCustomFieldName","txtValue"=>$strGlobalYolaFTPUsernameProductCustomFieldName);
		$newid = insert_query($table,$aryUpdateData);

		$aryUpdateData = Array("txtSetting"=>"txtGlobalYolaFTPPasswordProductCustomFieldName","txtValue"=>$strGlobalYolaFTPPasswordProductCustomFieldName);
		$newid = insert_query($table,$aryUpdateData);

		$aryUpdateData = Array("txtSetting"=>"txtGlobalFTPHostname","txtValue"=>$strGlobalFTPHostname);
		$newid = insert_query($table,$aryUpdateData);

		$aryUpdateData = Array("txtSetting"=>"txtGlobalFTPHomeDirectory","txtValue"=>$strGlobalFTPHomeDirectory);
		$newid = insert_query($table,$aryUpdateData);

		$aryUpdateData = Array("txtSetting"=>"txtGlobalFTPPort","txtValue"=>$intGlobalFTPPort);
		$newid = insert_query($table,$aryUpdateData);

		$aryUpdateData = Array("txtSetting"=>"txtGlobalFTPMode","txtValue"=>$intGlobalFTPMode);
		$newid = insert_query($table,$aryUpdateData);

		$aryUpdateData = Array("txtSetting"=>"txtTrialWord","txtValue"=>$strTrialWord);
		$newid = insert_query($table,$aryUpdateData);
	}

	// Run SQL Updates for V1.06 to V1.07
	if ($version < 1.07) {
		$table = "mod_sitebuilder_settings";

		$aryUpdateData = Array("txtSetting"=>"txtUpgradeFunctionToRun","txtValue"=>"");
		$newid = insert_query($table,$aryUpdateData);
	}
}

function sitebuilder_output($vars) {
	$modulelink = $vars['modulelink'];
	$version = $vars['version'];
	$strPartnerAuthKey = $vars['PartnerAuthKey'];
	$strPartnerBrand = $vars['BrandID'];
	$LANG = $vars['_lang'];

	if($_POST['action'] == "")
		$strAction = $_GET['action'];
	else
		$strAction = $_POST['action'];

	if($strAction == "")
	{
		$blnSidebarDisabled = false;
		if(isset($_COOKIE['WHMCSMinSidebar']))
			if($_COOKIE['WHMCSMinSidebar'] == "1")
				$blnSidebarDisabled = true;
		if($blnSidebarDisabled == true)
		{
			$smarty = new Smarty;
			$smarty->assign('modulelink', $modulelink);
			$smarty->assign('version', $version);
			$smarty->assign('sidebardisabled', "1");
			$blnUpgradePossible = Topline_CheckForModuleUpdate($version);
			if($blnUpgradePossible == true)
				$smarty->assign('updatemessage', "1");
			$smarty->caching = false;
			$smarty->compile_dir = $GLOBALS['templates_compiledir'];
			$smarty->display(dirname(__FILE__) . '/templates/sidebarmenu.tpl');
		}
		else
			print "Select an option from the side bar on the left";
	}
	elseif($strAction == "manageservers")
		sitebuilder_manageservers($vars);
	elseif($strAction == "manageproducts")
		sitebuilder_manageproducts($vars);
	elseif($strAction == "managemodules")
		sitebuilder_managemodules($vars);
	elseif($strAction == "globalsettings")
		sitebuilder_globalsettings($vars);
	//elseif($strAction == "hooktest")
	//	sitebuilder_after_module_create(array("pid"=>28,"username"=>"metest","serverid"=>16,"moduletype"=>"Cpaneltest"));
}

function sitebuilder_sidebar($vars) {
	$modulelink = $vars['modulelink'];
	$version = $vars['version'];
	$LANG = $vars['_lang'];

	$sidebar = '<span class="header"><img src="images/icons/addonmodules.png" class="absmiddle" width="16" height="16" /> Topline Sitebuilder</span>';
	$smarty = new Smarty;
	$smarty->assign('modulelink', $modulelink);
	$smarty->assign('version', $version);
	$blnUpgradePossible = Topline_CheckForModuleUpdate($version);
	if($blnUpgradePossible == true)
		$smarty->assign('updatemessage', "1");
	$smarty->caching = false;
	$smarty->compile_dir = $GLOBALS['templates_compiledir'];
	$sidebar .= $smarty->fetch(dirname(__FILE__) . '/templates/sidebarmenu.tpl');
	return $sidebar;
}

function sitebuilder_clientarea($vars) {

	// Example Link for Service/Product ID 1234: index.php?m=sitebuilder&a=login&id=1234

	$modulelink = $vars['modulelink'];
    	$version = $vars['version'];
	$strPartnerAuthKey = $vars['PartnerAuthKey'];
	$strPartnerBrand = $vars['BrandID'];
	$LANG = $vars['_lang'];

	if(isset($GLOBALS['clientsdetails']))
		$intClientRID = $GLOBALS['clientsdetails']['userid'];
 	elseif(isset($_SESSION['uid']))
		$intClientRID = $_SESSION['uid'];

	$strAction = $_GET['a'];
	if(strlen($strAction) < 1)
		$strAction = $_POST['a'];

	$intServiceRID = $_GET['id'];
	if(strlen($intServiceRID) < 1)
		$intServiceRID = $_POST['id'];
	if(is_numeric($intServiceRID))
		$intServiceRID = (int)$intServiceRID;
	else
		$intServiceRID = 0;

	if($strAction == "login" && $intServiceRID > 0)
	{
		return Topline_DisplayProductDetailsLoginLink($intClientRID,$intServiceRID);
	}else
		return "";
}

function sitebuilder_manageservers($vars) {
	global $mstrModuleLink;
	$modulelink = $vars['modulelink'];
    	$version = $vars['version'];
	$strPartnerAuthKey = $vars['PartnerAuthKey'];
	$strPartnerBrand = $vars['BrandID'];
	$LANG = $vars['_lang'];

	$strAction2 = $_POST['action2'];
	if(strlen($strAction2) < 1)
		$strAction2 = $_GET['action2'];

	$intServerID = $_POST["server_id"];
	if(strlen($intServerID) < 1)
		$intServerID = $_GET["server_id"];
	if(is_numeric($intServerID))
		$intServerID = (int)$intServerID;
	else
		$intServerID = 0;

	if($strAction2 == "edit" && $intServerID > 0)
	{
		$table = "mod_sitebuilder_servers";
		$fields = "intRecordID,txtFTPHostname,txtFTPHomeDirectory,intFTPPort,intFTPMode";
		$where = array("tblservers_id"=>$intServerID);
		$findserversetting_rows = select_query($table,$fields,$where);
		$serversetting_data = mysql_fetch_array($findserversetting_rows);
		display_template(Array("action2"=>"edit","server_id"=>$intServerID,"strFTPHostname"=>$serversetting_data["txtFTPHostname"],"strFTPHomeDirectory"=>$serversetting_data["txtFTPHomeDirectory"],"strFTPPort"=>$serversetting_data["intFTPPort"],"strFTPMode"=>$serversetting_data["intFTPMode"],"strLinkBack"=>$mstrModuleLink),"manageservers.tpl");
	}
	elseif($strAction2 == "editsave" && $intServerID > 0)
	{
		$aryUpdateData = buildqueryarrayfromform("intRecordID,tblservers_id","mod_sitebuilder_servers");
		$aryUpdateData["tblservers_id"] = $intServerID;
		$table = "mod_sitebuilder_servers";
		$fields = "intRecordID";
		$where = array("tblservers_id"=>$intServerID);
		$server_rows = select_query($table,$fields,$where);
		$server_data = mysql_fetch_array($server_rows);
		if(is_numeric($server_data["intRecordID"]))
		{
			// Do A Record Update
			$table = "mod_sitebuilder_servers";
			$where = array("intRecordID"=>$server_data["intRecordID"]);
			update_query($table,$aryUpdateData,$where);
		}else{
			// Do A Record Insert
			$table = "mod_sitebuilder_servers";
			$newid = insert_query($table,$aryUpdateData);
		}
		display_template(Array("strMessage"=>"Server Settings Saved"),"message.tpl");
	}
	elseif($strAction2 == "delete" && $intServerID > 0)
	{
		display_template(Array("action2"=>"delete","server_id"=>$intServerID,"strLinkBack"=>$mstrModuleLink),"manageservers.tpl");
	}
	elseif($strAction2 == "deleteconfirm" && $intServerID > 0)
	{
		$deleterecordsql = "DELETE FROM mod_sitebuilder_servers WHERE tblservers_id = $intServerID";
		$deleterecord_row = full_query($deleterecordsql);
		display_template(Array("strMessage"=>"Custom FTP Server Settings Deleted"),"message.tpl");
	}
	else
	{
		$aryServerData = "";
		$select_servers = "SELECT * FROM tblservers ORDER BY name";
		$servers_rows = full_query($select_servers);
		$intServerCount = mysql_num_rows($servers_rows);
		if(!is_numeric($intServerCount))
			$intServerCount = 0;
		if ($servers_rows) {
			while ($server = mysql_fetch_assoc( $servers_rows ) ) {
				if((bool)$server["disabled"] == false)
					$intDisabled = "0";
				else
					$intDisabled = "1";
				$table = "mod_sitebuilder_servers";
				$fields = "intRecordID";
				$where = array("tblservers_id"=>$server["id"]);
				$findserversetting_rows = select_query($table,$fields,$where);
				$serversetting_data = mysql_fetch_array($findserversetting_rows);
				if(is_numeric($serversetting_data["intRecordID"]))
				{
					$intHasCustomSettings = "1";
				}else{
					$intHasCustomSettings = "0";
				}
				$aryServerData[] = Array("intid"=>$server["id"],"strname"=>$server["name"],"stripaddress"=>$server["ipaddress"],"intdisabled"=>$intDisabled,"inthascustomsettings"=>$intHasCustomSettings);
			}
		}
		display_template(Array("strLinkBack"=>$mstrModuleLink,"intServerCount"=>$intServerCount,"serverdata" => $aryServerData),"manageservers.tpl");
	}
}

function sitebuilder_managemodules($vars) {
	global $mstrModuleLink;
	$modulelink = $vars['modulelink'];
    	$version = $vars['version'];
	$strPartnerAuthKey = $vars['PartnerAuthKey'];
	$strPartnerBrand = $vars['BrandID'];
	$LANG = $vars['_lang'];

	$strAction2 = $_POST['action2'];
	if(strlen($strAction2) < 1)
		$strAction2 = $_GET['action2'];

	$intRecordID = $_POST["rid"];
	if(strlen($intRecordID) < 1)
		$intRecordID = $_GET["rid"];
	if(is_numeric($intRecordID))
		$intRecordID = (int)$intRecordID;
	else
		$intRecordID = 0;

	if($strAction2 == "edit" || $strAction2 == "create")
	{
		$aryServerModules = Array();
		$strServerModulePath = dirname(dirname(dirname(__FILE__))) . "/servers";
		if ($handle = opendir($strServerModulePath)) {
			while (false !== ($entry = readdir($handle))) {
				if ($entry != "." && $entry != "..") {
					if(is_dir($strServerModulePath . "/$entry"))
						$aryServerModules[] = $entry;
				}
			}
			closedir($handle);
		}
		$recorddata = Array();
		if($intRecordID > 0)
		{
			$table = "mod_sitebuilder_modulexcustomfields";
			$fields = "intRecordID,txtModuleName,txtYolaUserIDProductCustomFieldName,txtYolaFTPUsernameProductCustomFieldName,txtYolaFTPPasswordProductCustomFieldName";
			$where = array("intRecordID"=>$intRecordID);
			$findserversetting_rows = select_query($table,$fields,$where);
			$recorddata = mysql_fetch_array($findserversetting_rows);
		}
		foreach($aryServerModules as $modulename)
		{
			$strModulesHTML .= "<option value='$modulename'";
			if(isset($recorddata["txtModuleName"]))
				if($recorddata["txtModuleName"] == $modulename)
					$strModulesHTML .= " selected";
			$strModulesHTML .= ">$modulename</option>";
		}
		display_template(Array("action2"=>$strAction2,"modules"=>$strModulesHTML,"rid"=>$recordata["intRecordID"],"YolaUserIDProductCustomFieldName"=>$recorddata["txtYolaUserIDProductCustomFieldName"],"YolaFTPUsernameProductCustomFieldName"=>$recorddata["txtYolaFTPUsernameProductCustomFieldName"],"YolaFTPPasswordProductCustomFieldName"=>$recorddata["txtYolaFTPPasswordProductCustomFieldName"]),"managemodules.tpl");
	}
	elseif($strAction2 == "editsave" || $strAction2 == "createsave")
	{
		$aryUpdateData = buildqueryarrayfromform("intRecordID","mod_sitebuilder_modulexcustomfields");
		$table = "mod_sitebuilder_modulexcustomfields";
		$fields = "intRecordID";
		$where = array("intRecordID"=>$intRecordID);
		$module_rows = select_query($table,$fields,$where);
		$module_data = mysql_fetch_array($module_rows);
		if(is_numeric($module_data["intRecordID"]))
		{
			// Do A Record Update
			$table = "mod_sitebuilder_modulexcustomfields";
			$where = array("intRecordID"=>$module_data["intRecordID"]);
			update_query($table,$aryUpdateData,$where);
		}else{
			// Do A Record Insert
			$table = "mod_sitebuilder_modulexcustomfields";
			$newid = insert_query($table,$aryUpdateData);
		}
		display_template(Array("strMessage"=>"Module Custom Field Settings Saved"),"message.tpl");
	}
	elseif($strAction2 == "delete" && $intRecordID > 0)
	{
		display_template(Array("action2"=>"delete","rid"=>$intRecordID,"strLinkBack"=>$mstrModuleLink),"managemodules.tpl");
	}
	elseif($strAction2 == "deleteconfirm" && $intRecordID > 0)
	{
		$deleterecordsql = "DELETE FROM mod_sitebuilder_modulexcustomfields WHERE intRecordID = $intRecordID";
		$deleterecord_row = full_query($deleterecordsql);
		display_template(Array("strMessage"=>"Module Custom Field Settings Deleted"),"message.tpl");
	}
	else
	{
		$aryRecordData = "";
		$table = "mod_sitebuilder_modulexcustomfields";
		$fields = "intRecordID,txtModuleName";
		//$where = array("txtYolaBundleID"=>$strBundleID);
		$servermodulesetting_rows = select_query($table,$fields,$where);
		$intServerCount = mysql_num_rows($servermodulesetting_rows);
		if(!is_numeric($intServerCount))
			$intServerCount = 0;
		if($servermodulesetting_rows) {
			while($servermodulesetting = mysql_fetch_assoc($servermodulesetting_rows)) {
				$aryRecordData[] = Array("rid"=>$servermodulesetting["intRecordID"],"modulename"=>$servermodulesetting["txtModuleName"]);
			}
		}

		display_template(Array("strLinkBack"=>$mstrModuleLink,"intServerCount"=>$intServerCount,"recorddata" => $aryRecordData),"managemodules.tpl");
	}
}

function sitebuilder_manageproducts($vars) {
	$modulelink = $vars['modulelink'];
    	$version = $vars['version'];
	$strPartnerAuthKey = $vars['PartnerAuthKey'];
	$strPartnerBrand = $vars['BrandID'];
	$strAPIStagingMode = $vars['StagingMode'];

	$LANG = $vars['_lang'];

	$strAction2 = $_POST['action2'];
	if(strlen($strAction2) < 1)
		$strAction2 = $_GET['action2'];

	$arybundleProducts = Array();
	$arybundleAddons = Array();

	if($strAction2 == "edit")
	{
		$strBundleID = $_POST["bundle_id"];
		if(strlen($strBundleID) < 1)
			$strBundleID = $_GET["bundle_id"];

		$Topline = new ToplineAPI;
		$Topline->SetPartnerAuthKey($strPartnerAuthKey);
		$Topline->SetPartnerBrand($strPartnerBrand);
		$Topline->TurnOnStatingMode($strAPIStagingMode);
		$strBundleName = $Topline->GetBundleName($strBundleID);

		// Get Products Selected For This Bundle
		$table = "mod_sitebuilder_bundlexproducts";
		$fields = "intRecordID,tblproducts_id";
		$where = array("txtYolaBundleID"=>$strBundleID);
		$bundleproducts_rows = select_query($table,$fields,$where);
		if($bundleproducts_rows) {
			while($bundleproduct = mysql_fetch_assoc($bundleproducts_rows)) {
				$arybundleProducts[] = $bundleproduct['tblproducts_id'];
			}
		}
		// Get Product Add-ons Selected For This Bundle
		$table = "mod_sitebuilder_bundlexproducts";
		$fields = "intRecordID,tbladdons_id";
		$where = array("txtYolaBundleID"=>$strBundleID);
		$bundleproductaddons_rows = select_query($table,$fields,$where);
		if($bundleproductaddons_rows) {
			while($bundleproductaddon = mysql_fetch_assoc($bundleproductaddons_rows)) {
				$arybundleAddons[] = $bundleproductaddon['tbladdons_id'];
			}
		}

		// Check If Proudct Add-on Still Exists In WHMCS DB. If Not Add-on Product Deleted, Delete Topline Add-on Product Record
		foreach($arybundleAddons as $intProductAddonRID)
		{
			$table = "tbladdons";
			$fields = "name";
			$where = array("id"=>$intProductAddonRID);
			$result = select_query($table,$fields,$where);
			$productaddondata = mysql_fetch_array($result);
			$strProductAddonName = $productaddondata["name"];
			if(strlen($strProductAddonName) < 1)
			{
				$deleterecordsql = "DELETE FROM mod_sitebuilder_bundlexproducts WHERE tbladdons_id = $intProductAddonRID";
				$deleterecord_row = full_query($deleterecordsql);	
			}
		}

		// Get Products In WHMCS
		$select_products = "SELECT id,name FROM tblproducts ORDER BY name";
		$products_rows = full_query($select_products);
		if ($products_rows) {
			while ($product = mysql_fetch_assoc( $products_rows ) ) {
				if(in_array($product['id'],$arybundleProducts))
					$strProductsToChooseFrom .= '<option selected="selected" value="'.$product['id'].'">'.$product['name'].'</option>';
				else
					$strProductsToChooseFrom .= '<option value="'.$product['id'].'">'.$product['name'].'</option>';
			}
		}
		// Get Product Add-ons In WHMCS
		$select_productaddons = "SELECT id,name FROM tbladdons ORDER BY name";
		$productaddons_rows = full_query($select_productaddons);
		if ($productaddons_rows) {
			while ($addon = mysql_fetch_assoc( $productaddons_rows ) ) {
				if(in_array($addon['id'],$arybundleAddons))
					$strAddonsToChooseFrom .= '<option selected="selected" value="'.$addon['id'].'">'.$addon['name'].'</option>';
				else
					$strAddonsToChooseFrom .= '<option value="'.$addon['id'].'">'.$addon['name'].'</option>';
			}
		}
		display_template(Array("strLinkBack"=>$mstrModuleLink,"action2"=>"edit","strProductsToChooseFrom"=>$strProductsToChooseFrom,"strAddonsToChooseFrom"=>$strAddonsToChooseFrom,"bundle_id"=>$strBundleID,"bundle_name"=>$strBundleName),"manageproducts.tpl");
	}
	elseif($strAction2 == "editsave")
	{
		$strBundleID = $_POST["bundle_id"];
		if(strlen($strBundleID) < 1)
			$strBundleID = $_GET["bundle_id"];
		$strBundleID = Topline_db_escape($strBundleID);
		$Topline = new ToplineAPI;
		$Topline->SetPartnerAuthKey($strPartnerAuthKey);
		$Topline->SetPartnerBrand($strPartnerBrand);
		$Topline->TurnOnStatingMode($strAPIStagingMode);
		if($Topline->VerifyBundleIDAllowed($strBundleID))
		{
			//-------------------------------------------------
			// Update Linked Products To This Bundle
			//-------------------------------------------------
			if(!is_array($_POST['productstochoosefrom']))
			{
			if(strpos($_POST['productstochoosefrom'],",") !== false)
					$aryProductsToLinkTo = explode(",",$_POST['productstochoosefrom']);
				else
					$aryProductsToLinkTo = Array($_POST['productstochoosefrom']);
			}else
				$aryProductsToLinkTo = $_POST['productstochoosefrom'];
			if(is_array($aryProductsToLinkTo))
			{
				//--------------------------
				// Update Product Links
				//--------------------------
				// Get All Current Product Links
				//------------
				$aryCurrentBundleProducts = Array();

				$table = "mod_sitebuilder_bundlexproducts";
				$fields = "intRecordID,tblproducts_id";
				$where = array("txtYolaBundleID"=>$strBundleID);
				$bundleproducts_rows = select_query($table,$fields,$where);
				if($bundleproducts_rows) {
					while($bundleproduct = mysql_fetch_assoc($bundleproducts_rows)) {
						$aryCurrentBundleProducts[] = $bundleproduct['tblproducts_id'];
					}
				}
				$aryBundleProductsToAdd = $aryProductsToLinkTo;
				$aryBundleProductsToDelete = $aryCurrentBundleProducts;
				//------------
				// Now Get Each Product Link Submitted And See If It Exists In Current DB Records
				// If The DB Record Exists And Do Does Submission, Delete Both From New Arrays And Move On
				//------------
				foreach($aryProductsToLinkTo As $intProductToLinkTo)
				{
					if(in_array($intProductToLinkTo,$aryCurrentBundleProducts))
					{
						$aryBundleProductsToAdd = array_merge(array_diff($aryBundleProductsToAdd, array($intProductToLinkTo)));
						$aryBundleProductsToDelete = array_merge(array_diff($aryBundleProductsToDelete, array($intProductToLinkTo)));		
					}
				}
				//print_r($aryBundleProductsToAdd);
				//print "<br/>\n";
				//print_r($aryBundleProductsToDelete);
				//exit;
				if(count($aryBundleProductsToAdd) > 0)
				{
					foreach($aryBundleProductsToAdd As $intProductToLinkAdd)
					{
						if(is_numeric($intProductToLinkAdd))
						{
							$table = "mod_sitebuilder_bundlexproducts";
							$values = array("txtYolaBundleID"=>$strBundleID,"tblproducts_id"=>$intProductToLinkAdd);
							$newid = insert_query($table,$values);
						}
					}
				}
				if(count($aryBundleProductsToDelete) > 0)
				{
					foreach($aryBundleProductsToDelete As $intProductToLinkDelete)
					{
						if(is_numeric($intProductToLinkDelete))
						{
							$delete = "DELETE FROM mod_sitebuilder_bundlexproducts WHERE txtYolaBundleID = '$strBundleID' AND tblproducts_id = $intProductToLinkDelete";
							$strResults = full_query($delete);
						}
					}
				}
			}
			//-------------------------------------------------
			// Update Linked Product Add-ons To This Bundle
			//-------------------------------------------------
			if(!is_array($_POST['productaddonstochoosefrom']))
			{
				if(strpos($_POST['productaddonstochoosefrom'],",") !== false)
					$aryProductAddonsToLinkTo = explode(",",$_POST['productaddonstochoosefrom']);
				else
					$aryProductAddonsToLinkTo = Array($_POST['productaddonstochoosefrom']);
			}else
				$aryProductAddonsToLinkTo = $_POST['productaddonstochoosefrom'];
			if(is_array($aryProductAddonsToLinkTo))
			{
				//--------------------------
				// Update Product Addon Links
				//--------------------------
				// Get All Current Product Addon Links
				//------------
				$aryCurrentBundleProductAddons = Array();

				$table = "mod_sitebuilder_bundlexproducts";
				$fields = "intRecordID,tbladdons_id";
				$where = array("txtYolaBundleID"=>$strBundleID);
				$bundleproductaddons_rows = select_query($table,$fields,$where);
				if($bundleproductaddons_rows) {
					while($bundleproductaddon = mysql_fetch_assoc($bundleproductaddons_rows)) {
						$aryCurrentBundleProductAddons[] = $bundleproductaddon['tbladdons_id'];
					}
				}
				$aryBundleProductAddonsToAdd = $aryProductAddonsToLinkTo;
				$aryBundleProductAddonsToDelete = $aryCurrentBundleProductAddons;
				//------------
				// Now Get Each Product Link Submitted And See If It Exists In Current DB Records
				// If The DB Record Exists And Do Does Submission, Delete Both From New Arrays And Move On
				//------------
				foreach($aryProductAddonsToLinkTo As $intProductAddonToLinkTo)
				{
					if(in_array($intProductAddonToLinkTo,$aryCurrentBundleProductAddons))
					{
						$aryBundleProductAddonsToAdd = array_merge(array_diff($aryBundleProductAddonsToAdd, array($intProductAddonToLinkTo)));
						$aryBundleProductAddonsToDelete = array_merge(array_diff($aryBundleProductAddonsToDelete, array($intProductAddonToLinkTo)));		
					}
				}
				//print_r($aryBundleProductAddonsToAdd);
				//print "<br/>\n";
				//print_r($aryBundleProductAddonsToDelete);
				//exit;
				if(count($aryBundleProductAddonsToAdd) > 0)
				{
					foreach($aryBundleProductAddonsToAdd As $intProductAddonToLinkAdd)
					{
						if(is_numeric($intProductAddonToLinkAdd))
						{
							$table = "mod_sitebuilder_bundlexproducts";
							$values = array("txtYolaBundleID"=>$strBundleID,"tbladdons_id"=>$intProductAddonToLinkAdd);
							$newid = insert_query($table,$values);
						}
					}
				}
				if(count($aryBundleProductAddonsToDelete) > 0)
				{
					foreach($aryBundleProductAddonsToDelete As $intProductAddonToLinkDelete)
					{
						if(is_numeric($intProductAddonToLinkDelete))
						{
							$delete = "DELETE FROM mod_sitebuilder_bundlexproducts WHERE txtYolaBundleID = '$strBundleID' AND tbladdons_id = $intProductAddonToLinkDelete";
							$strResults = full_query($delete);
						}
					}
				}
			}

			display_template(Array("strMessage"=>"Bundle To WHMCS Product & Product Add-on Links Saved Successfully."),"message.tpl");
		}else{
			display_template(Array("strMessage"=>"This bundle is not authorized on your account or is an invalid bundle."),"message.tpl");
		}
	}
	elseif($strAction2 == "delete")
	{

	}
	else
	{
		$aryBundleData = "";
		// Get all the product bundles and list them
		$Topline = new ToplineAPI;
		$Topline->SetPartnerAuthKey($strPartnerAuthKey);
		$Topline->SetPartnerBrand($strPartnerBrand);
		$Topline->TurnOnStatingMode($strAPIStagingMode);
		$aryBundles = $Topline->GetBundles();
		foreach($aryBundles as $aryBundle)
		{
			$blnIsCorePlan = false;
			if(is_numeric($aryBundle["isCore"]))
				if((int)$aryBundle["isCore"] == 1)
					$blnIsCorePlan = true;
			if($blnIsCorePlan == true) {
				$strDiskSpace = "0";
				if(isset($aryBundle["space"])) {
					if(is_numeric(trim($aryBundle["space"])))
						$strDiskSpace = Topline_human_filesize((int)$aryBundle["space"],2,true);
				}else{
					$strDiskSpace = "N/A";
				}
				$strYolaBundleID = $aryBundle["planID"];
				$select_countassignedproducts = "SELECT intRecordID FROM mod_sitebuilder_bundlexproducts WHERE txtYolaBundleID = '$strYolaBundleID'";
				$countassignedproducts_rows = full_query($select_countassignedproducts);
				$intNumberOfWHMCSProductsAssigned = mysql_num_rows($countassignedproducts_rows);
				if(isset($aryBundle["pages"]))
					$strPages = $aryBundle["pages"];
				else
					$strPages = "";
				$currencyData = getCurrency($_SESSION["adminid"]);
				$strMonhtlyFee = formatCurrency($aryBundle["spCost"], $currencyData);
				$aryBundleData[] = Array("id"=>$strYolaBundleID,"name"=>$aryBundle["planName"],"diskspace"=>$strDiskSpace,"monthlyfee"=>$strMonhtlyFee,"pages"=>$strPages,"numofproductsassigned"=>$intNumberOfWHMCSProductsAssigned);
			}
		}
		display_template(Array("strLinkBack"=>$mstrModuleLink,"bundledata" => $aryBundleData),"manageproducts.tpl");
	}
}

function sitebuilder_globalsettings($vars) {
	$modulelink = $vars['modulelink'];
    	$version = $vars['version'];
	$strPartnerAuthKey = $vars['PartnerAuthKey'];
	$strPartnerBrand = $vars['BrandID'];
	$LANG = $vars['_lang'];
	$strAction2 = $_POST['action2'];

	if($strAction2 == "save")
	{
		$table = "mod_sitebuilder_settings";
		foreach($_POST as $strSettingName => $strSettingValue)
		{
			if(substr(strtolower($strSettingName),0,3) == "str")
			{
				$strFormFieldName = "txt".substr($strSettingName,3);
				$strFormValue = $strSettingValue;
				if(strlen($strFormValue) > 0)
				{
					$blnDoRecordUpdate = False;
					$fields = "txtSetting";
					$where = array("txtSetting"=>$strFormFieldName);
					$settings_rows = select_query($table,$fields,$where);
					$settings_data = mysql_fetch_array($settings_rows);
					if(isset($settings_data["txtSetting"]))
						if(strtolower($settings_data["txtSetting"]) == strtolower($strFormFieldName))
							$blnDoRecordUpdate = True;
					if($blnDoRecordUpdate == True)
					{
						// Do A Record Update
						$aryUpdateData = Array("txtValue"=>$strFormValue);
						$where = array("txtSetting"=>$strFormFieldName);
						update_query($table,$aryUpdateData,$where);
					}else{
						// Do A Record Insert
						$aryUpdateData = Array("txtSetting"=>$strFormFieldName,"txtValue"=>$strFormValue);
						$newid = insert_query($table,$aryUpdateData);
					}
				}
			}
		}
		display_template(Array("strMessage"=>"Settings Saved"),"message.tpl");
	}else{
		$aryValesToPass["action2"] = "save";
		$table = "mod_sitebuilder_settings";
		$fields = "txtSetting,txtValue";
		$result = select_query($table,$fields);
		while ($data = mysql_fetch_array($result))
		{
			$settingname = $data["txtSetting"];
			$settingvalue = $data["txtValue"];
			if(!is_numeric($settingname))
			{
				$strSettingName = "str".substr($settingname,3);
				$aryValesToPass[$strSettingName] = $settingvalue;
			}
		}
		display_template($aryValesToPass,"globalsettings.tpl");
	}
}

function buildqueryarrayfromform($strDBFieldsToExclude,$strTableName,$aryFieldValuesToFindAndSubstitute = Array())
{
	$aryUpdateData = Array();
	$aryFieldsToExclude = explode(",",$strDBFieldsToExclude);
	// Build Update Query Array From Form Fields
	$select_getfieldnames = "SHOW COLUMNS FROM $strTableName";
	$fieldname_rows = full_query($select_getfieldnames);
	$aryUpdateData = Array();
	while ($fieldinfo = mysql_fetch_assoc( $fieldname_rows ) ) {
		$strFormFieldName = "str".substr($fieldinfo['Field'],3);
		$strFormValue = $_POST[$strFormFieldName];
		if(!isset($_POST[$strFormFieldName]))
			if(isset($_POST[substr($fieldinfo['Field'],3)]))
				$strFormValue = $_POST[substr($fieldinfo['Field'],3)];
		if(count($aryFieldValuesToFindAndSubstitute) > 0)
		{
			foreach($aryFieldValuesToFindAndSubstitute as $strDataToFind => $strValueToReplaceItWith)
			{
				if(strpos($strFormValue,$strDataToFind) !== false)
				{
					$strFormValue = str_replace($strDataToFind,$strValueToReplaceItWith,$strFormValue);
					break;
				}
			}
		}
		if(!in_array($fieldinfo['Field'],$aryFieldsToExclude))
			$aryUpdateData[$fieldinfo['Field']] = Topline_db_escape($strFormValue);
	}
	return $aryUpdateData;
}

function display_template($aryVariables,$strTemplateFile) {
		global $mstrModuleLink;

		// create object
		$smarty = new Smarty;

		$smarty->assign('strModuleLink', $mstrModuleLink);
		if(is_array($aryVariables))
		{
			foreach($aryVariables as $strVariableName => $strVariableValue)
			{
				$smarty->assign($strVariableName, $strVariableValue);
			}
		}

		$smarty->caching = false;

		// display it
		$smarty->compile_dir = $GLOBALS['templates_compiledir'];

		$smarty->display(dirname(__FILE__) . '/templates/'.$strTemplateFile);
}
?>