	{if $sidebardisabled eq "1"}
	<h3> Main Menu </h3>
	{/if}
	<ul class="menu">
		<li><a href="{$modulelink}&action=manageservers">Server Settings</a></li>
		<li><a href="{$modulelink}&action=manageproducts">Product Settings</a></li>
		<li><a href="{$modulelink}&action=managemodules">Module Custom Field Settings</a></li>
		<li><a href="{$modulelink}&action=globalsettings">Global Settings</a></li>
		<li><a href="#">Version: {$version}</a></li>
	</ul>