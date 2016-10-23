<?php
/* Copyright 2013 Diego Zanella (support@pathtoenlightenment.net)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 3, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
GPL3: http://www.gnu.org/licenses/gpl-3.0.txt
 * @license GNU GPLv3
*/

// Define the plugin:
$PluginInfo['galexsteamsignin'] = array(
    'Name' => 'Steam Sign In',
    'Description' => 'Allows users to sign in with their Steam accounts. <strong>Please click Settings, after enabling the plugin, to enable displaying the "Sign in through Steam" button</b>.',
    'Version' => '16.11.23',
    'RequiredApplications' => array(
        'Vanilla' => '2.1'
    ),
    'RequiredPlugins' => array('OpenID' => '0.1a'),
    'RequiredTheme' => false,
    'MobileFriendly' => true,
    'SettingsUrl' => '/dashboard/plugin/galexsteamsignin',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'RegisterPermissions' => false,
    'Author' => 'Diego Zanella',
    'AuthorEmail' => 'diego@pathtoenlightenment.net',
    'AuthorUrl' => 'http://pathtoenlightenment.net',
    'License' => 'GPLv3',
);

/**
 * Allows users to sign in with their Steam accounts.
 */
class galexsteamsigninPlugin extends Gdn_Plugin
{
    const STEAM_ICON_IMG = 'plugins/galexsteamsignin/design/images/steam-icon.png';
    const STEAM_SIGNIN_IMG = 'plugins/galexsteamsignin/design/images/steam-signin.png';

    /**
     * Builds the URL that will be used to authorise User to log in.
     *
     * @param bool Popup Indicates if the Login window should be displayed as a
     * popup.
     * @return string The URL to use for authentication.
     */
    protected function BuildAuthorisationURL($Popup = FALSE)
    {
        $Url      = Url('/entry/openid', TRUE);
        $UrlParts = explode('?', $Url);
        parse_str(GetValue(1, $UrlParts, ''), $Query);
        
        $Query['url'] = 'https://steamcommunity.com/openid';
        
        $Path            = '/' . Gdn::Request()->Path();
        $Query['Target'] = GetValue('Target', $_GET, $Path);
        
        if ($Popup) {
            $Query['display'] = 'popup';
        }
        
        // Build the final URL
        $Result = $UrlParts[0] . '?' . http_build_query($Query);
        return $Result;
    }
    
    /**
     * Generates the HTML required to display the Steam Sign In button.
     *
     * @param string Image The full path and name of the image file to use to
     * render the login button.
     * @return string The HTML containing the anchor and image for the login
     * button.
     */
    private function GenerateSignInButton($Image)
    {
        $ButtonTitle    = T('Sign In with Steam');
        $SigninURL      = $this->BuildAuthorisationURL();
        $PopupSigninURL = $this->BuildAuthorisationURL(TRUE);
        
        return Anchor(Img($Image, array(
            'alt' => $ButtonTitle
        )), $SigninURL, 'PopupWindow', array(
            'popupHref' => $PopupSigninURL,
            'popupWidth' => 800,
            'popupHeight' => 550,
            'id' => 'SteamSignIn',
            'title' => $ButtonTitle
        ));
    }
    
    /**
     * Act as a mini dispatcher for API requests to the plugin app
     *
     * @param Gdn_Controller Sender Sender Controller.
     */
    public function PluginController_SteamSignIn_Create($Sender)
    {
        $Sender->Permission('Garden.Settings.Manage');
        
        $Sender->Title($this->GetPluginKey('Name'));
        $Sender->AddSideMenu('dashboard/plugin/galexsteamsignin');
        
        $this->Dispatch($Sender, $Sender->RequestArgs);
    }

    /**
     * Add a link to the dashboard menu
     *
     * By grabbing a reference to the current SideMenu object we gain access to its methods, allowing us
     * to add a menu link to the newly created /plugin/Example method.
     *
     * @param $Sender Sending controller instance
     */
    public function base_getAppSettingsMenuItems_handler($Sender)
    {
        $Menu =& $Sender->EventArguments['SideMenu'];
        $Menu->addLink('Users', 'Steam Sign-In', 'dashboard/plugin/galexsteamsignin', 'Garden.Settings.Manage');
    }
    
    /**
     * Renders the Index page for the plugin.
     *
     * @param Sender Sending controller instance.
     */
    public function controller_index($Sender)
    {
        $Sender->permission('Garden.Settings.Manage');
        $Sender->setData('PluginDescription',$this->getPluginKey('Description'));
        $Sender->AddCssFile($this->GetResource('design/css/steamsignin.css', false, false));
        $Sender->render($this->GetView('steamsignin_settings_view.php'));
    }
    
    
    /**
     * Automatically handle the toggle effect
     *
     * @param Gdn_Controller Sender Sender Controller.
     */
    public function Controller_Toggle($Sender)
    {
        $this->AutoToggle($Sender);
    }
    
    /**
     * Event Handler for rendering of Authentication form.
     *
     * @param Gdn_Controller Sender Sender Controller.
     */
    public function AuthenticationController_Render_Before($Sender)
    {
        if (isset($Sender->ChooserList)) {
            $Sender->ChooserList['galexsteamsignin'] = 'Steam';
        }
        
        if (is_array($Sender->Data('AuthenticationConfigureList'))) {
            $List = $Sender->Data('AuthenticationConfigureList');
            $List['galexsteamsignin'] = '/dashboard/plugin/galexsteamsignin';
            $Sender->SetData('AuthenticationConfigureList', $List);
        }
    }
    
    /**
     * Event Handler for Sign in event.
     *
     * @param Gdn_Controller Sender Sender Controller.
     */
    public function EntryController_SignIn_Handler($Sender)
    {
        if (!$this->IsEnabled()) {
            return;
        }
        
        if (isset($Sender->Data['Methods'])) {
            $ImageAlt = T('Sign In with Steam');
            
            $SigninURL = $this->BuildAuthorisationURL();
            $PopupSigninURL = $this->BuildAuthorisationURL(TRUE);
            
            // Add Steam Sign In method to the Controller
            $Method = array(
                'Name' => 'Steam',
                'SignInHtml' => $this->GenerateSignInButton(self::STEAM_SIGNIN_IMG)
            );
            
            $Sender->Data['Methods'][] = $Method;
        }
    }
    
    /**
     * Displays the "Sign In with Steam" button, if such feature is enabled.
     *
     * @param Gdn_Controller Sender Sender Controller.
     */
    public function Base_BeforeSignInButton_Handler($Sender)
    {
        if (!$this->IsEnabled()) {
            return;
        }
        echo $this->GenerateSignInButton(self::STEAM_ICON_IMG);
    }
    
    /**
     * Displays the "Sign In with Steam" link, if such feature is enabled.
     *
     * @param Gdn_Controller Sender Sender Controller.
     */
    public function Base_BeforeSignInLink_Handler($Sender)
    {
        if (!$this->IsEnabled()) {
            return;
        }
        
        if (!Gdn::Session()->IsValid()) {
            echo Wrap($this->GenerateSignInButton(self::STEAM_ICON_IMG), 'li', array(
                'class' => 'SteamSignIn Connect'
            ));
        }
    }
}
