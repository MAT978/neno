<?php

/**
 * Created by PhpStorm.
 * User: victor
 * Date: 17/06/15
 * Time: 16:49
 */
class InstallNenoCest
{
    public function installNeno(AcceptanceTester $I)
    {
        $I->maximizeWindow();
        $I->am('Administrator');
        $I->installJoomla();
        $I->doAdministratorLogin();
        $I->setErrorReportingToDevelopment();
        $I->amOnPage("/administrator/");
        $I->click("Extensions");
        $I->waitForElementVisible('//*[@id="menu"]/li[6]/ul/li[1]/a');
        $I->click('//*[@id="menu"]/li[6]/ul/li[1]/a');
        $I->click("Upload Package File");
        $path = $I->getConfiguration('repo_folder');

        // Installing library
        $I->installExtensionFromDirectory($path . 'lib_neno');

        // Installing Plugin
        $I->installExtensionFromDirectory($path . 'plg_system_neno');

        // Installing Component
        $I->installExtensionFromDirectory($path . 'com_neno');

        // Enabling plugin
        $I->enablePlugin('Neno plugin');

        // Going to Neno
        $I->amOnPage("/administrator/index.php?option=com_neno&view=installation");
        $I->click("Components");
        $I->click("Neno Translate");
        $I->click("//button[@type='button']");
        $I->click("//button[@type='button']");
        $I->click("//button[@type='button']");
        $I->click("#add-languages-button");
        $I->click("#close-button");

        // Get started Screen
        $I->click('Get Started');
        $I->waitForJS('return jQuery.active == 0', 5);
        $I->wait(1);

        // First step - Source language
        $I->see('Next');
        $I->click(['xpath' => "//button[@type=\"button\"]"]);
        $I->wait(1);

        // Second step - Translation methods
        $I->click('Next');
        $I->waitForJS('return jQuery.active == 0', 5);

        // Third step- Install language(s)
        $I->wait(1);
        $I->click("//*[@id=\"add-languages-button\"]");
        $I->waitForJS("return jQuery.active == 0", 5);
        $I->waitForElementVisible(['class' => 'ar-AA'], 5);
        $I->wait(1);
        $I->click(['class' => 'ar-AA']);
        $I->see('Close', ['class' => 'close-button']);
        $I->click(['class' => 'close-button'], ['xpath' => "//*[@id=\"languages-modal\"]"]);
        $I->click(['xpath' => "(//button[@type=\"button\"])[4]"]);

        // Fourth step- Installing Neno
        $I->wait(1);
        $I->click("#backup-created-checkbox");
        $I->click("#proceed-button");

        // Fifth step- Installing Neno has been accomplish successfully
        //$I->waitForJS('return jQuery.installation == 1', 1000);
        $I->waitForElement(".icon-thumbs-up", 300);
        $I->doAdministratorLogout();
    }
}