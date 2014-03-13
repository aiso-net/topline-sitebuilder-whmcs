{if $action2 eq ""}
	<h2>Your Topline Account Bundle List</h2>
	Click the Edit link next to each bundle to assign WHMCS plans to that bundle. During a module create for the assigned plan, a sitebuilder account is created and assigned to the customers' plan.
	<br/><br/>
	<a href="{$strModuleLink}">Go Back</a>
	<br/><br/>
	<div class="tablebg">
		<table width="100%" cellspacing="1" cellpadding="3" border="0" class="datatable">
			<tr>
				<th>Bundle Name</th>
				<th>Disk Space</th>
				<th>Your Price Per Account, Per Month</th>
				<th># of WHMCS Products Assigned</th>
				<th>Actions</th>
			</tr>
			{if $bundledata|is_array}
				{foreach from=$bundledata item=bundle}
					<tr>
						<td>{$bundle.name}</td><td>{$bundle.diskspace}</td><td>{$bundle.monthlyfee}</td><td>{$bundle.numofproductsassigned}<td><a href="{$strModuleLink}&bundle_id={$bundle.id}&action=manageproducts&action2=edit">Edit</a></td>
					</tr>
				{/foreach}
			{/if}
		</table>
	</div>
	<p align="center">&nbsp;</p>
{elseif $action2 eq "edit"}
	<h2>Edit WHMCS Products For Bundle <b>{$bundle_name}</b></h2>
	<a href="{$strModuleLink}&action=manageproducts">Go Back</a>
	<br/><br/>
	<form action="{$strModuleLink}&action=manageproducts" method="post">
	<input type="hidden" name="action2" value="{$action2}save">
	<input type="hidden" name="bundle_id" value="{$bundle_id}">
	<table width="100%" cellspacing="1" cellpadding="2" border="0" class="datatable">
		<tr><td class="fieldlabel">Choose the WHMCS Products to link this bundle to:</td><td align="left" class="fieldarea">
			<select name="productstochoosefrom[]" id="productstochoosefrom" multiple="multiple">
			   {$strProductsToChooseFrom}
			</select>
		</td></tr>
		<tr><td class="" colspan="2">
			&nbsp;
		</td></tr>
		<tr><td class="fieldlabel">Choose the WHMCS Product Add-ons to link this bundle to:</td><td align="left" class="fieldarea">
			<select name="productaddonstochoosefrom[]" id="productaddonstochoosefrom" multiple="multiple">
			   {$strAddonsToChooseFrom}
			</select>
		</td></tr>
	</table>
	<br/><br/>
	<input type="submit" name="submit" value="Save">
	</form>
	<script language="javascript" type="text/javascript" src="../modules/addons/sitebuilder/templates/js/jquery.multiselect.min.js"></script>
	<link rel="stylesheet" type="text/css" href="../modules/addons/sitebuilder/templates/css/jquery.multiselect.css">
	<script language="javascript" type="text/javascript" src="../modules/addons/sitebuilder/templates/js/jquery.multiselect.filter.js"></script>
	<link rel="stylesheet" type="text/css" href="../modules/addons/sitebuilder/templates/css/jquery.multiselect.filter.css">
	<script language="JavaScript" type="text/javascript">
	{literal}
	$("#productstochoosefrom").multiselect({
		noneSelectedText: 'Choose Products To Link To',
		checkAllText: 'Check all',
		uncheckAllText: 'Uncheck all',
		minWidth: 275
	});
	$("#productstochoosefrom").multiselect().multiselectfilter();
	$("#productaddonstochoosefrom").multiselect({
		noneSelectedText: 'Choose Product Add-ons To Link To',
		checkAllText: 'Check all',
		uncheckAllText: 'Uncheck all',
		minWidth: 275
	});
	$("#productaddonstochoosefrom").multiselect().multiselectfilter();
	{/literal}
	</script>
{/if}