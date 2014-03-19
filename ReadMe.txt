----------------------------------------------
Requirements
----------------------------------------------
WHMCS 5.0 or later.

WHMCS Server Modules Supported:
	- cPanel 11.27.x and later
	- Custom Server Module. See below for custom server module support.
----------------------------------------------
Install Instructions
----------------------------------------------
1) Extract all files from downloaded .zip archive and upload the modules folder from the zip in the whmcs installation folder

2) Login to WHMCS and activate the addon module under setup -> addon modules

3) Under addon configuration options enter your API details given to you by topline

4) Under each Product that will be a sitebuilder service, click the custom fields tab and create a 
   custom field that the module uses to save the sitebuilder id, sitebuilder ftp username and sitebuilder ftp password.
   For the field name enter in what you want, such as Sitebuilder User ID. For the field type select Text Box. For the 
   description enter what ever you want. For Validation and Select Options you can leave them blank. And Select the 
   Admin Only check box. See Readme-1.jpg file.

5) Go to Addons and scroll down to Topline Sitebuilder module

5) Click global settings and fill in the custom field names that will be used in all server modules along with the global
   ftp settings for all server modules. See Readme-2.jpg file for an example.

5a) 

6) If you have different ftp settings per server, then you can override the global settings you entered by clicking on Manage 
   Server Settings then click edit next to the server.

7) If you have different custom field names per module, then you can override the global settings you entered by clicking on
   Manage Module Custom Field Settings then clicking on Add Module Custom Field Settings or editing an existing one.

8) Now you need to link the Sitebuilder bundle from topline to the WHMCS product by clicking on Manage Product Settings, then 
   click edit next to the Topline bundle and select which WHMCS product/service is assigned to that bundle. Now when ever a
   WHMCS product is ordered that you have selcted it will setup the Topline bundle that you assigned to the respective
   WHMCS products assigned.

9) For a sitebuilder account to be created it has to meet the following requirements:
	- WHMCS Product being ordered by client must be assigned to a Topline bundle via the Topline Sitebuilder module Manage
	  Product Settings area.
	- WHMCS Product being ordered by client must have a supported server module assigned to it.
	- WHMCS Product being ordered by client must have three custom field names setup and those same names must either be entered
	  into the Topline Sitebuilder module Global Settings area or Manage Module Custom Field Settings area.
	- WHMCS Product being ordered by client must have FTP settings setup for the server module assigned to the product in either
	  the Topline Sitebuilder module Global Settings area or Manage Server Settings area.

10) In templates/ACTIVE-TEMPLATE/clientareaproductdetails.tpl do the following to allow a user to one click signin to the site builder:

on around line 101 after:

{if $moduleclientarea}<div class="moduleoutput">{$moduleclientarea|replace:'modulebutton':'btn'}</div>{/if}

add:

		{if $loggedin}
		{php}
			require_once dirname(dirname(__FILE__)) ."/modules/addons/sitebuilder/sitebuilder_functions.php";
			$blnLoginURLValid = Topline_DisplayProductDetailsLoginLink($GLOBALS['smarty']->_tpl_vars['clientsdetails']['userid'],$GLOBALS['smarty']->_tpl_vars['id'],true);
			if($blnLoginURLValid == true)
			{
				$strURLToPrintData = Topline_GetClientAreaProductLoginLinkHTML();
				if(strlen($strURLToPrintData) == 0)
					$strURLToPrintData = '<a target="_blank" href="{loginurl}"><b>Edit Your Site</b></a><br/>';
				$strURLToPrintData = str_replace('{loginurl}','index.php?m=sitebuilder&t=2&a=login&id=' . $GLOBALS['smarty']->_tpl_vars['id'],$strURLToPrintData);
				print $strURLToPrintData;
			}
		{/php}
		{/if}

----------------------------------------------
WHMCS Custom Server Module Support
----------------------------------------------
The WHMCS product that has the custom server module assigned to it must have three custom fields that the server module fills in.
Those feidls are the Sitebuilder ID, Sitebuilder FTP Username and Sitebuilder FTP Password. Then within either the Global Settings
or Manage Module Custom Field Settings you must enter in the three custom field names enclosed in left & right curly brackets ({}).
This module after a server setup will then see the curly brackets and retreive the three custom fields' information from the 
customers' service that the custom module should have filled in. With that information it will setup a sitebuilder account with the
sitebuilder id and ftp information retreived from their service to be used for the customers sitebuilder account in the topline system.
See Readme-3.jpg and Readme-4.jpg for an example.

----------------------------------------------
Topline Trial Support Setup
----------------------------------------------
Follow these steps for setting up a trial plan that does not publish to an FTP server, this is the recommened setup.
1. Create a WHMCS product that you want to be the trial for the sitebuilder. You can create a product for each of the sitebuilder plans Topline offers.
2. Under the Module Settings tab of the product in WHMCS, set the Module Name to Autorelease and leave everything else under this tab set to None. Check the 'Automatically setup the product as soon as an order is placed' radio button.
3. Under the Custom Fields tab of the product in WHMCS, create a custom field that the module uses to save the sitebuilder id, sitebuilder ftp username and sitebuilder ftp password. For the field name enter in what you want, such as 
   Sitebuilder User ID. For the field type select Text Box. For the description enter what ever you want. For Validation and Select Options you can leave them blank. And Select the Admin Only check box. See Readme-1.jpg file.
4. Under the Upgrades tab of the product in WHMCS, in the Package Upgrades box select the site builder plans that are the acutal paid plans the user can upgrade to for paid hosting.
5. Now you need to link the Sitebuilder bundle from topline to the WHMCS trial product you created by clicking on the Addons tab in WHMCS, click Topline Sitebuilder Module, then click Manage Product Settings, then click edit next to 
   the Topline bundle and select which WHMCS product/service trial that you created to assign to that bundle.
6. Click on the Addons tab in WHMCS, click Topline Sitebuilder Module, then click on the Global settings link. In the Trial Word text box enter the word that is common between all your sitebuilder trial plans. Usually this would be 
   the word Trial. This allows the module to setup the sitebuilder trial correctly when it sees a WHMCS plan ordered that has the word you enter in this text box in the WHMCS plan/service name or description.
7. Click on the Addons tab in WHMCS, click Topline Sitebuilder Module, then click on the Global settings link. In the drop down box for WHMCS Upgrade Plan Function, please select AfterProductUpgrade. If clients are upgrading and the
   topline account is not being converted from a trial to a regular plan they purchased, then select AfterModuleChangePackage instead.

Follow these steps for setting up a trial plan that does publish to an FTP server.
1. Follow all the trial steps 1-7 above
2. Click on the Addons tab in WHMCS, click Topline Sitebuilder Module, then click on the Global settings link. In the Trial FTP Hostname, Trial FTP Username, Trial FTP Password, Trial FTP Home Directory, Trial FTP Port and Trial FTP Mode,
   please enter in the trial FTP server info that the trial site should be publisted to.


You can use the following e-mail template variable {$autoterminateday} for use in a WHMCS email template. This variable will display the date the trial will expire in an email, based on the auto terminate days setting in the whmcs product.