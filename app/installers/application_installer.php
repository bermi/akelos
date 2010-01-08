<?php

/**
 * Unified migrations for your application
 * 
 * You can call regular migration methods like createTable or call other migrations
 *
 *       function up_2(){
 *          // Will run a migration located in the plugins directory
 *           $this->installMigration('Invitations', 1, AK_APP_DIR.DS.'vendor'.DS.'plugins'.DS.'invitations'.DS.'installer');
 *       }
 *       
 *       function down_2(){
 *           $this->uninstallMigration('Invitations', null, AK_APP_DIR.DS.'vendor'.DS.'plugins'.DS.'invitations'.DS.'installer');
 *       }
 *       
 *       function up_1() {
 *           $this->installMigration('Admin Plugin', 1); // Will install version 1 of the admin plugin
 *       }
 *       
 *       function down_1(){
 *           $this->uninstallMigration('Admin Plugin');
 *       }
 */
class ApplicationInstaller extends AkInstaller {

}