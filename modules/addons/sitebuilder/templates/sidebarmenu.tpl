	{if $sidebardisabled eq "1"}
	<h3> Main Menu {$updatemessage}</h3>
		{if $updatemessage eq "1"}
		<p>There is a update for this module, please get the <a href="https://github.com/aiso-net/topline-sitebuilder-whmcs" target="_new">latest version here</a>.</p>
		{/if}
	{/if}
	<ul class="menu">
		<li><a href="{$modulelink}&action=manageservers">Server Settings</a></li>
		<li><a href="{$modulelink}&action=manageproducts">Product Settings</a></li>
		<li><a href="{$modulelink}&action=managemodules">Module Custom Field Settings</a></li>
		<li><a href="{$modulelink}&action=globalsettings">Global Settings</a></li>
		<li><a href="#">Version: {$version}</a></li>
	</ul>