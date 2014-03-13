{if $strOnlyMessage neq ""}
	{$strOnlyMessage}
{elseif $strPOSTBack eq ""}
	<a href="{$strLinkBack}">Go Back</a>
	<br/><br/>
	{$strMessage}
{else}
	<br/>
	{$strMessage}
	<br/><br/>
	<form method="post" action="{$strPOSTBack}">
	<input type="hidden" name="manageipsrefer" value="{$strLinkBack}">
	<input type="submit" value="Go Back">
	</form>
{/if}