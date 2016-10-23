<?php if (!defined('APPLICATION')) { exit(); } ?>
<div class="SteamSignIn">
	<h1>
<?php
echo T($this->Data['Title']) . ' - ' . T('General Settings');
?>
	</h1>
	<div class="Info">
<?php
echo T('Use the button below to enable or disable the "Sign in through Steam" feature.');
?>
	</div>
	<div class="FilterMenu">
<?php
$FormAction = $this->Plugin->AutoTogglePath();
echo $this->Form->Open(array(
    'action' => Url($FormAction),
    'jsaction' => $FormAction
));
echo $this->Form->Errors();
$PluginName = $this->Plugin->GetPluginKey('Name');
$ButtonName = T($this->Plugin->IsEnabled() ? "Disable $PluginName" : "Enable $PluginName");
echo $this->Form->Close($ButtonName, '', array('class' => 'SliceSubmit SliceForm Button'));
?>
	</div>
	<div id="Credits">
		<span>Plugin Icon based on original work by <a href="http://mazenl77.deviantart.com/">Mazenl77</a>.</span>
	</div>
</div>
