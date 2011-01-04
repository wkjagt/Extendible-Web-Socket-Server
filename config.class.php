<?php
/**
 * Configurations for Web Socket Server. If you wish to use your own
 * config class, please implement the configClass() method in your server
 * class. Example:
 *
 * function configClass(){
 *     return 'MyConfigClass';
 * }
 *
 */
class WSBaseConfig {
    
    /**
     * Server address
     */
    public $address = 'localhost';

    /**
     * Server port
     */
    public $port = 12345;
    
    /**
     * Maximum number of users connected at the same time
     */
    public $maxUsers = 3;

    /**
     * Debug (true or false)
     */
    public $debug = true;
    
    /**
     * Name of class to use when creating a new user
     */
    public $userClass = 'WSBaseUser';
}