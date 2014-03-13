{if $action2 eq ""}
	<h2>WHMCS Module List</h2>
	Click the Edit link next to the module to edit the custom fields, or click add to add a new modules' custom fields.
	<br/><br/>
	<a href="{$strLinkBack}">Go Back</a>
	<br/><br/>
	<table width="100%" cellspacing="0" cellpadding="3" border="0">
		<tr>
			<td width="50%" align="left">
			{$intServerCount} Record(s) Found
			</td>
			<td width="50%" align="right"><a href="{$strModuleLink}&action=managemodules&action2=create">Add Module Custom Field Setting</a>
			</td>
		</tr>
	</table>
	<div class="tablebg">
		<table width="100%" cellspacing="1" cellpadding="3" border="0" class="datatable">
			<tr>
				<th>Module Name</th>
				<th>Actions</th>
			</tr>
			{if $recorddata|is_array}
				{foreach from=$recorddata item=record}
					<tr>
						<td>{$record.modulename}</td><td><a href="{$strModuleLink}&rid={$record.rid}&action=managemodules&action2=edit">Edit</a>&nbsp;&nbsp;&nbsp;<a href="{$strModuleLink}&rid={$record.rid}&action=managemodules&action2=delete">Delete</a></td>
					</tr>
				{/foreach}
			{/if}
		</table>
	</div>
	<p align="center">&nbsp;</p>
{elseif $action2 eq "edit" || $action2 eq "create"}
	{if $action2 eq "edit"}
		<h2>Edit Module Custom Field Settings</h2>
	{else}
		<h2>Create Module Custom Field Settings</h2>
	{/if}
	<a href="{$strModuleLink}&action=managemodules">Go Back</a>
	<br/><br/>
	<form action="{$strModuleLink}&action=managemodules" method="post">
	<input type="hidden" name="action2" value="{$action2}save">
	<input type="hidden" name="rid" value="{$rid}">
	WHMCS Module:&nbsp;<select name="ModuleName">{$modules}</select>
	<br/><br/>
	Yola User ID Product Custom Field Name:&nbsp;<input type="text" size="50" name="YolaUserIDProductCustomFieldName" value="{$YolaUserIDProductCustomFieldName}">&nbsp;<i>To grab the value from the field name, enclose the Field Name in Curly Brackets.</i>
	<br/><br/>
	Yola FTP Username Product Custom Field Name:&nbsp;<input type="text" size="50" name="YolaFTPUsernameProductCustomFieldName" value="{$YolaFTPUsernameProductCustomFieldName}">&nbsp;<i>To grab the value from the field name, enclose the Field Name in Curly Brackets.</i>
	<br/><br/>
	Yola FTP Password Product Custom Field Name:&nbsp;<input type="text" size="50" name="YolaFTPPasswordProductCustomFieldName" value="{$YolaFTPPasswordProductCustomFieldName}">&nbsp;<i>To grab the value from the field name, enclose the Field Name in Curly Brackets.</i>
	<br/><br/>
	<input type="submit" name="submit" value="Save">
	</form>
{elseif $action2 eq "delete"}
	<h2>Delete Module Custom Field Settings</h2>
	<a href="{$strModuleLink}&action=managemodules">Go Back</a>
	<br/><br/>
	<br/>
	Are you sure you want to delete these custom settings?
	<br/><br/>
	<form action="{$strModuleLink}&action=managemodules" method="post">
	<input type="hidden" name="action2" value="{$action2}confirm">
	<input type="hidden" name="rid" value="{$rid}">
	<input type="submit" value="Yes">
	</form>
{/if}