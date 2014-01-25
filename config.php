<?php

static $sessionLabel = "DATABUILDER";
static $formPrefix="db_";
static $usersTable="users";

static $sharedSecret = "secret";

/* DATABASE */

static $dbDatabase="databuilder";
static $dbUsername="root";
static $dbPassword="shopdust";
static $dbHost="localhost";

// Cache the project root directory because calling absolute paths is
// faster than relative 
define( 'ROOTDIR', dirname(__FILE__) . '/' );

// Image directory
define( 'LOCALIMAGEDIR', "/netwirth_portal/images/");
define( 'IMAGEDIR', ROOTDIR . "images/");

?>