<?php

/**
 * Standard security class, modifiable for use in any system that requires user authentication.
 *
 * Open source. Created by Tim Raveling, October 2012.
 *
 */

if (file_exists('includes/facebook.php'))
	include 'includes/facebook.php';

// Global function: checks the global declared in this file.
function isLoggedIn()
{
	if (isset($GLOBALS["loggedin"]))
		if ($GLOBALS["loggedin"]==1)
			return true;
	
	return false;
}

// Global function: gets the user type
function getUserType()
{
	if (isset($_SESSION[$sessionLabel."_LOGGEDIN"]))
	{
		return $_SESSION[$sessionLabel."_LOGGEDIN"];
	}
	
	return 0;
}

class User
{

	const STANDARD 	= 0;
	const ADMIN		= 1;

	// User attributes
	public $userLevel;
	public $userEmail;
	public $firstName;
	public $lastName;
	public $uid;
	
	public function __construct($res = null)
    {
        if ($res!=null) {
        	$this->userEmail=stripslashes($res->email);
			$this->firstName=stripslashes($res->firstname);
			$this->lastName=stripslashes($res->lastname);
			$this->userLevel=intval($res->permissions);
			$this->uid=$res->id;
        }
    }
    
    public function getFullName($normallayout=true)
    {
    	if (!$normallayout)return $this->lastName.", ".$this->firstName;
	    return $this->firstName." ".$this->lastName;
    }
	
	public function registerAsNewUser($password)
	{
		global $DBH,$usersTable;
		
		$stmt = $DBH->prepare("SELECT id FROM ".$usersTable." WHERE email=:email");
		$stmt->bindParam(":email",$this->userEmail,PDO::PARAM_STR);
		$stmt->execute();
		if ($stmt->fetch())
		{
			define ("REGISTER_ERROR","That email's already in use!");
			return;
		}
		
		$passhash = self::getHash($password);
		
		$stmt = $DBH->prepare("INSERT INTO ".$usersTable.
						"(email,firstname,lastname,passhash,permissions) VALUES".
						"(:email,:firstname,:lastname,:passhash,0)");
		
		$stmt->bindParam(":email",$this->userEmail,PDO::PARAM_STR);
		$stmt->bindParam(":firstname",$this->firstName,PDO::PARAM_STR);
		$stmt->bindParam(":lastname",$this->lastName,PDO::PARAM_STR);
		$stmt->bindParam(":passhash",$passhash,PDO::PARAM_STR);
		
		$stmt->execute();
		
		$this->authenticate($this->userEmail,$password);
	}

	public function checkLoggedIn()
    {
    	global $formPrefix,$sessionLabel,$usersTable,$DBH;
    	$GLOBALS["loggedin"]=0;
    	
    	if (isset($_POST[$formPrefix."email"]))
		{
			$this->authenticate($_POST[$formPrefix."email"],$_POST[$formPrefix."password"]);
		}
		
		if(isset($_SESSION[$sessionLabel."_LOGGEDIN"]))
		{
			$GLOBALS["loggedin"]=1;
			$stmt = $DBH->prepare("SELECT * FROM ".$usersTable." WHERE id=:id");
			$stmt->bindParam(":id",$_SESSION[$sessionLabel."_USERID"],PDO::PARAM_INT);
			$stmt->execute();
			$res=$stmt->fetch();
			if ($res)
			{
				$this->userEmail=stripslashes($res->email);
				$this->firstName=stripslashes($res->firstname);
				$this->lastName=stripslashes($res->lastname);
				$this->userLevel=intval($res->permissions);
				$this->uid=$res->id;
			}
		}
		
		if (isset($_GET["logout"]))
		{
			session_destroy();
			
			$GLOBALS["loggedin"]=0;
			
			if (!isset($_GET["noredir"]))
				header("location:login.php");
		}
    }
    
    public function facebookLogin($fbToken)
    {
    	global $DBH,$sessionLabel;
    	// Create our Application instance (replace this with your appId and secret).
		$facebook = new Facebook(array(
		  'appId'  => '366779456741413',
		  'secret' => '7aad8d22a7e09a3deaa50cd5d6338960',
		));
		
		$facebook->setAccessToken($fbToken);
		
		// Get User ID
		$user = $facebook->getUser();
		
		if ($user) {
		  try {
		    // Proceed knowing you have a logged in user who's authenticated.
		    $user_profile = $facebook->api('/me');
		  } catch (FacebookApiException $e) {
		    error_log($e);
		    $user = null;
		  }
		}
		
		if ($user)
		{
			//print_r($user_profile);
			$stmt=$DBH->prepare("SELECT * FROM users WHERE fbtoken=:fbtoken");
		    $stmt->bindParam(":fbtoken",$fbToken,PDO::PARAM_STR);
		    $stmt->execute();
		    if ($res=$stmt->fetch())
		    {
		    	$this->userEmail=$res->email;
			    $this->firstName=$res->firstname;
			    $this->lastName=$res->lastname;
				
				$this->uid=$res->id;
		    }
		    else
		    {
		    	$firstname=$user_profile["first_name"];
		    	$lastname=$user_profile["last_name"];
		    	$email="";
		    	if (isset($user_profile["email"]))
		    		$email=$user_profile["email"];
		    	
		    	$should_insert=true;
		    	$got_id="";
		    	if ($email!="")
		    	{
		    		// Check if this email is already being used and update account if so
			    	$stmt=$DBH->prepare("SELECT id FROM `users` WHERE email=:email");
			    	$stmt->bindParam(":email",$email,PDO::PARAM_STR);
			    	$stmt->execute();
			    	if ($res=$stmt->fetch())
			    	{
				    	$stmt=$DBH->prepare("UPDATE users SET fbtoken=:fbtoken WHERE id=:uid");
				    	$stmt->bindParam(":fbtoken",$fbToken,PDO::PARAM_STR);
				    	$stmt->bindParam(":uid",$res->id,PDO::PARAM_INT);
				    	$stmt->execute();
				    	$should_insert=false;
				    	$got_id=$res->id;
				    }
		    	}
		
		    	if ($should_insert==true)
		    	{
				    $stmt=$DBH->prepare("INSERT INTO users (fbtoken,firstname,lastname,email,permissions) VALUES (:fbtoken,:firstname,:lastname,:email,0)");
				    $stmt->bindParam(":fbtoken",$fbToken,PDO::PARAM_STR);
				    $stmt->bindParam(":firstname",$firstname,PDO::PARAM_STR);
				    $stmt->bindParam(":lastname",$lastname,PDO::PARAM_STR);
				    $stmt->bindParam(":email",$email,PDO::PARAM_STR);
				    $stmt->execute();
				    
				    $stmt=$DBH->prepare("SELECT id FROM users ORDER BY id DESC LIMIT 0,1");
				    $stmt->execute();
				    $res=$stmt->fetch();
				    
				    $got_id=$res->id;
				}
			    
			    $this->userEmail=$email;
			    $this->firstName=$firstname;
			    $this->lastName=$lastname;
			    $this->uid=$got_id;
		    }
		    
		    $logged_in_status=1;
	    	if (isset($result->usertype))$logged_in_status=$result->usertype;
	    	$_SESSION[$sessionLabel."_LOGGEDIN"]=$logged_in_status;
	    	$_SESSION[$sessionLabel."_USERID"]=$result->id;
	        
	        // Set the global
	        $GLOBALS["loggedin"]=1;
	        return true;
        }
        return false;
    }

	// Try authentication
    public function authenticate($email,$password)
    {        
    	global $sessionLabel;
    	global $DBH,$usersTable;
        
        $sql = "SELECT * FROM ".$usersTable." WHERE email=:email AND fbtoken=''";
        
        try
        {
            $stmt = $DBH->prepare($sql);
            $stmt->execute( array( ':email' => $email ));
            $result = $stmt->fetch();
        }
        catch( PDOException $e )
        {
            define( 'LOGIN_ERROR', $e->getMessage() );
            return false;
        }
        
        // The result is false if no matching email was found
        if( $result === false )
        {
            define( 'LOGIN_ERROR', 'We don\'t recognize that email' );
            return false;
        }
        
        // Grab the password hash from the returned result (assumes row in table is always set to 'passhash')
        $passhash=$result->passhash;
        if(crypt( trim($password), $passhash ) == $passhash )
        {
        	// If user types are in use on the server, use that to populate the session var
        	$logged_in_status=1;
        	if (isset($result->usertype))$logged_in_status=$result->usertype;
        	$_SESSION[$sessionLabel."_LOGGEDIN"]=$logged_in_status;
        	$_SESSION[$sessionLabel."_USERID"]=$result->id;
            
            // Set the global
            $GLOBALS["loggedin"]=1;
            
            // Fill out user data
        	$this->userEmail=$result->email;
			$this->firstName=$result->firstname;
			$this->lastName=$result->lastname;
			$this->uid=$result->id;
			
			// The user has successfully logged in
            return true;
        }
        
        // Bad login info, so return an error
        define( 'LOGIN_ERROR', 'Incorrect password' );
        
        return false;
    }
    
    // Generate hash using salt
    public static function getHash( $pass )
    {
        $fullSalt = '$5$rounds=5000$' . self::getSalt();
        return crypt( trim( $pass ), $fullSalt );
    }
    
    // Get the salt
    private static function getSalt()
    {
        if( function_exists('openssl_random_pseudo_bytes') )
        {
            $strong = false;
            
            $bin = openssl_random_pseudo_bytes( 17, $strong );
            
            if( $strong )
                return base64_encode( $bin );
        }

        // Fallback to mt_rand if no openssl available
        $length = 22;
        $salt = '';
        $charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789+/';
        
        do
            $salt .= $charset[ mt_rand() % 64 ];
        while( --$length );            

        return $salt;
    }
}
