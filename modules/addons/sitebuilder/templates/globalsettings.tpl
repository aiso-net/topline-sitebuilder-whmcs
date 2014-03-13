<!----------------------------------------->
<!----------------------------------------->
<!-- DO NOT CHANGE ANYTHING IN THIS FILE -->
<!----------------------------------------->
<!----------------------------------------->
<h2>Edit Global Settings</h2>
<a href="{$strModuleLink}">Go Back</a>
<br/><br/>
Edit the module settings here, Trial FTP Info optional.
<br/><br/>
<form action="{$strModuleLink}&action=globalsettings" method="post">
<input type="hidden" name="action2" value="{$action2}">
Global Topline User ID Product Custom Field Name:&nbsp;<input type="text" size="50" maxlength="100" name="strGlobalYolaUserIDProductCustomFieldName" value="{$strGlobalYolaUserIDProductCustomFieldName}">&nbsp;<i>To grab the value from the field name, enclose the Field Name in Curly Brackets.</i>
<br/><br/>
Global Topline FTP Username Product Custom Field Name:&nbsp;<input type="text" size="50" maxlength="100" name="strGlobalYolaFTPUsernameProductCustomFieldName" value="{$strGlobalYolaFTPUsernameProductCustomFieldName}">&nbsp;<i>To grab the value from the field name, enclose the Field Name in Curly Brackets.</i>
<br/><br/>
Global Topline FTP Password Product Custom Field Name:&nbsp;<input type="text" size="50" maxlength="100" name="strGlobalYolaFTPPasswordProductCustomFieldName" value="{$strGlobalYolaFTPPasswordProductCustomFieldName}">&nbsp;<i>To grab the value from the field name, enclose the Field Name in Curly Brackets.</i>
<br/><br/>
Global FTP Hostname: &nbsp;<input type="text" size="50" maxlength="100" name="strGlobalFTPHostname" value="{$strGlobalFTPHostname}">
<br/><br/>
Global FTP Home Directory: &nbsp;<input type="text" size="50" maxlength="100" name="strGlobalFTPHomeDirectory" value="{$strGlobalFTPHomeDirectory}">&nbsp;<i>Value can have either $domainname or $username (username field in whmcs service) for auto fill in.</i>
<br/><br/>
Global FTP Port: &nbsp;<input type="text" size="7" maxlength="6" name="strGlobalFTPPort" value="{$strGlobalFTPPort}">
<br/><br/>
Global FTP Mode: &nbsp;<select name="strGlobalFTPMode"><option value="1" {if $strGlobalFTPMode eq "1"} selected{/if}>Active</option><option value="0" {if $strGlobalFTPMode eq "" || $strGlobalFTPMode eq "0"} selected{/if}>Passive</option></select>
<br/><br/>
Trial Word: &nbsp;<input type="text" size="50" maxlength="50" name="strTrialWord" value="{$strTrialWord}">
<br/><br/>
Trial FTP Hostname: &nbsp;<input type="text" size="50" maxlength="100" name="strTrialFTPHostname" value="{$strTrialFTPHostname}">&nbsp;<i>Value can have either $domainname or $serverip for auto fill in.</i>
<br/><br/>
Trial FTP Username:&nbsp;<input type="text" size="50" maxlength="100" name="strTrialFTPUsername" value="{$strTrialFTPUsername}">
<br/><br/>
Trial FTP Password:&nbsp;<input type="text" size="50" maxlength="100" name="strTrialFTPPassword" value="{$strTrialFTPPassword}">
<br/><br/>
Trial FTP Home Directory: &nbsp;<input type="text" size="50" maxlength="100" name="strTrialFTPHomeDirectory" value="{$strTrialFTPHomeDirectory}">&nbsp;<i>Value must have either $domainname or $username for auto fill in. Example would be /public_html/$username</i>
<br/><br/>
Trial FTP Port: &nbsp;<input type="text" size="7" maxlength="6" name="strTrialFTPPort" value="{$strTrialFTPPort}">
<br/><br/>
Trial FTP Mode: &nbsp;<select name="strTrialFTPMode"><option value="1" {if $strTrialFTPMode eq "1"} selected{/if}>Active</option><option value="0" {if $strTrialFTPMode eq "" || $strTrialFTPMode eq "0"} selected{/if}>Passive</option></select>
<br/><br/>
WHMCS Upgrade Plan Function: &nbsp;<select name="strUpgradeFunctionToRun"><option value="1" {if $strUpgradeFunctionToRun eq "" || $strUpgradeFunctionToRun eq "1"} selected{/if}>AfterModuleChangePackage</option><option value="2" {if $strUpgradeFunctionToRun eq "2"} selected{/if}>AfterProductUpgrade</option></select>
<br/><br/>

<input type="submit" name="submit" value="Save">
</form>