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
    public $maxUsers = 1000;

    /**
     * Debug (true or false)
     */
    public $debug = true;
    
    /**
     * Name of class to use when creating a new user
     */
    public $userClass = 'WSBaseUser';
    
    /**
     * If this value is an array, only connections having an origin that's present
     * in the array are allowed. Any other connections will be refused.
     * To allow all origins, set this value to FALSE
     * If you're unable to connect when using an array, please check the debug message
     * sent to the terminal. This will state the origin that tried to connect, and a
     * list of allowed origins.
     */
     public $uniqueOrigin = FALSE;
}