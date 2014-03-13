{if $action2 eq ""}
	<h2>WHMCS Server List</h2>
	Click the Edit link next to the server to assign custom FTP settings to that server, overriding the global settings.
	<br/><br/>
	<a href="{$strLinkBack}">Go Back</a>
	<br/><br/>
	<table width="100%" cellspacing="0" cellpadding="3" border="0">
		<tr>
			<td width="50%" align="left">
			{$intServerCount} Record(s) Found
			</td>
			<td width="50%" align="right">
			</td>
		</tr>
	</table>
	<div class="tablebg">
		<table width="100%" cellspacing="1" cellpadding="3" border="0" class="datatable">
			<tr>
				<th>Server Name</th>
				<th>Public IP</th>
				<th>Status</th>
				<th>Has Custom Settings</th>
				<th>Actions</th>
			</tr>
			{if $serverdata|is_array}
				{foreach from=$serverdata item=server}
					<tr>
						<td>{$server.strname}</td><td>{$server.stripaddress}</td><td align="center">{if $server.intdisabled eq "1"}<img src="images/icons/disabled.png" title="Disabled" />{else}<img src="images/icons/tick.png" title="Enabled" />{/if}</td><td>{if $server.inthascustomsettings eq "1"}Yes{else}No{/if}</td><td><a href="{$strModuleLink}&server_id={$server.intid}&action=manageservers&action2=edit">Edit</a>{if $server.inthascustomsettings eq "1"}&nbsp;&nbsp;&nbsp;<a href="{$strModuleLink}&server_id={$server.intid}&action=manageservers&action2=delete">Delete</a>{/if}</td>
					</tr>
				{/foreach}
			{/if}
		</table>
	</div>
	<p align="center">&nbsp;</p>
{elseif $action2 eq "edit"}
	<h2>Edit Server Custom FTP Settings</h2>
	<a href="{$strModuleLink}&action=manageservers">Go Back</a>
	<br/><br/>
	<form action="{$strModuleLink}&action=manageservers" method="post">
	<input type="hidden" name="action2" value="{$action2}save">
	<input type="hidden" name="server_id" value="{$server_id}">
	FTP Hostname: &nbsp;<input type="text" size="50" name="strFTPHostname" value="{$strFTPHostname}">&nbsp;<i>Values can have either $domainname or $serverip for auto fill in.</i>
	<br/><br/>
	FTP Home Directory: &nbsp;<input type="text" size="50" name="strFTPHomeDirectory" value="{$strFTPHomeDirectory}">&nbsp;<i>Values can have either $domainname or $username (username field in whmcs service) for auto fill in.</i>
	<br/><br/>
	FTP Port: &nbsp;<input type="text" size="7" name="strFTPPort" value="{$strFTPPort}">
	<br/><br/>
	FTP Mode: &nbsp;<select name="strFTPMode"><option value="1" {if $strFTPMode eq "1"} selected{/if}>Active</option><option value="0" {if $strFTPMode eq "" || $strFTPMode eq "0"} selected{/if}>Passive</option></select>
	<br/><br/>
	<input type="submit" name="submit" value="Save">
	</form>
{elseif $action2 eq "delete"}
	<h2>Delete Server Custom FTP Settings</h2>
	<a href="{$strModuleLink}&action=manageservers">Go Back</a>
	<br/><br/>
	<br/>
	Are you sure you want to delete these custom settings?
	<br/><br/>
	<form action="{$strModuleLink}&action=manageservers" method="post">
	<input type="hidden" name="action2" value="{$action2}confirm">
	<input type="hidden" name="server_id" value="{$server_id}">
	<input type="submit" value="Yes">
	</form>
{/if}