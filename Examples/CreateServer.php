<?php
/**
 * Sample code for creating a new server on Rackspace Cloud
 *
 * @author Aleksey Korzun <al.ko@webfoundation.net>
 * @link http://github.com/AlekseyKorzun/php-cloudservers/
 * @link http://www.schematic.com
 */

include '../Cloud/Exception.php';

// Provide your API ID (username) and API KEY (generated by Rackspace)
DEFINE('API_ID', '');
DEFINE('API_KEY', '');

try {
    // Initialize connection
    $cloud = new Cloud_Server(API_ID, API_KEY);
    // Add custom MOTD file to our server
    $cloud->addServerFile('/etc/motd', 'This is a custom MOTD user(s) will see upon login');
    // Create a new server
    $server = $cloud->createServer('Server Name', 2, 1);
    // If server was successfully created we should now have an array
    // of server details that you can use to populate local database, etc
    if (is_array($server) && !empty($server)) {
        print_r($server);
    }
} catch (Cloud_Exception $e) {
    print $e->getMessage();
}