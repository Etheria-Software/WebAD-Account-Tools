<?php
////////////////////////////////////////////////
//              LGPL notice                   //
////////////////////////////////////////////////
/*
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
////////////////////////////////////////////////
//         used libraries/code modules        //
////////////////////////////////////////////////
/*
adLDAP 4.0.4 -- released under GNU LESSER GENERAL PUBLIC LICENSE, Version 2.1 by  http://adldap.sourceforge.net/
etheria config loader (custom build) -- released under GNU LESSER GENERAL PUBLIC LICENSE
meny 1.1 | http://lab.hakim.se/meny | MIT licensed
jquery 1.6.2 | http://jquery.com/ | MIT or GPL Version 2 licenses
HTML5 Shiv 3.7.2 | @afarkas @jdalton @jon_neal @rem | MIT/GPL2 Licensed
Sweet Titles 1.0 | Dustin Diaz http://www.dustindiaz.com | Creative Commons 2005
*/

////////////////////////////////////////////////
// 	       notes/requirements             //
////////////////////////////////////////////////
/*
php5 must be installed
sendmail must be installed (on ubuntu installing mutt usualy drags in all the right bits for you)

mcrypt must be installed / activated, to do this in ubuntu issue the command 'sudo apt-get install php5-mcrypt'
if this does not work ensure that the php mcrypt.ini is linked in /etc/php5/apache2/conf.d/
*/
////////////////////////////////////////////////
//               Version info                 //
////////////////////////////////////////////////
/*
see version.log file
*/

////////////////////////////////////////////////
//               Developer Info               //
////////////////////////////////////////////////
/*
Name : James
Alias : Shadow AKA ShadowGauardian507-IRL

Contact : shadow@shadowguardian507-irl.tk
Alternate contact : shadow@etheria-software.tk

Note as an Anti-spam Measure I run graylisting on my mail servers, so new senders email will be held for some time before it arrives in my mail box,
please ensure that the service you are sending from tolerates graylisting on target address (most normal mail systems are perfectly happy with this)

This software is provided WITHOUT any SUPPORT or WARRANTY but bug reports and feature requests are welcome.
*/


//------------
$ADdelay=2; //delay in seconds to allow for AD to 'catch up' with changes published by the system before it tryes to read them back
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">



<!--[if lt IE 9]>
        <script src="3rdPcomponents/html5shiv/html5shiv.min.js"></script>
<![endif]-->

<script language="javascript" type="text/javascript">
  document.oncontextmenu=RightMouseDown;
  document.onmousedown = mouseDown; 

  function mouseDown(e) {
      if (e.which==3) {//righClick
   }
}
function RightMouseDown() { return false;}



</script>

<?php
//component load
include("./components/adLDAP/adLDAP.php");
include("./components/addtolog.cp.php");
include("./components/secshard.cp.php");

// config module loader
foreach (glob("./config.d/active/*.conf.php") as $conffilename)
{
    include $conffilename;
}


//setup email eng
// To send HTML mail, the Content-type header must be set
$headers  = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
// Additional headers
$headers .= 'From: '.$sysemailadd . "\r\n";
$emailmessage = "<br />";
//--------------------------------------------------------------------



//create LDAP a connection
try {
	//$adldap = new adLDAP();
	$adldap = new adLDAP(array('base_dn'=>$ldap_base_dn,'account_suffix'=>$ldap_account_suffix,'domain_controllers'=>$ldap_domain_controllers,'admin_username'=>$ldap_admin_username,'admin_password'=>$ldap_admin_password,'real_primarygroup'=>$ldap_real_primarygroup,'use_ssl'=>$ldap_use_ssl,'use_tls'=>$ldap_use_tls,'recursive_groups'=>$ldap_recursive_groups));
    }
catch (adLDAPException $e) {
    echo $e; exit();
    }


//set default variables
$pagecode="login";
$usrnamet="";
$authmode="";
//start sec session
session_start();


//enable cypher use
//added peruser cyper mod
$cipher = new Cipher($cipherkey.$_SESSION["username"]);
//print ">>>>>>>>>>>>>>>>>".$_SESSION["username"]."<<<<<<<<<<<<<<<<<<";


//debug
//print "POST";
//print "<pre>"; print_R($_POST); print "</pre>";



//check session logged in and change recovery questions
if (($_POST['reccount']!="")&&($_SESSION["loggedin"]==="true"))
{

	

	 $recprocfields=$ausersfields=array("userAccountControl","secretQuestionAnswerList","secretQuestionList","secretQuestionLockout","sn","givenName");
         $recprocuserinfo=$adldap->user()->info($_SESSION["username"],$recprocfields);
         $recprocsecretquestionlistarray=unserialize($recprocuserinfo[0][secretquestionlist][0]);
         $recprocsecretquestionanswerlistarray=unserialize($recprocuserinfo[0][secretquestionanswerlist][0]);
         $recproclockout=$recprocuserinfo[0][secretquestionlockout][0];
         $accproctenabled=$recprocuserinfo[0][useraccountcontrol][0];

	
	if((($recproclockout!="0")&&($recproclockout!=""))||($accproctenabled==="514")||($accproctenabled==="66050"))
        {
	//no go recovery locked out, how the heck did you even get here
	}
	else
	{
	//no lockouts, so process array
	$recprocarraypointer = $_POST['reccount'];
	$recproca=$_POST['reca'];
	$recprocq=$_POST['recq'];



	if(strlen($recprocq)===0)
        {
        //empty question pull it from array
        unset($recprocsecretquestionlistarray[$recprocarraypointer]);
        }
        else
        {
        //set question
        $recprocsecretquestionlistarray[$recprocarraypointer]=$cipher->encrypt($recprocq);
        }

        if(strlen($recproca)===0)
        {
        //empty answer pull it from array
        unset($recprocsecretquestionanswerlistarray[$recprocarraypointer]);
        }
        else
        {
        $recprocsecretquestionanswerlistarray[$recprocarraypointer]=$cipher->encrypt($recproca);
        }


	//set answer data ok flags to true (will knock to false if bad value shows up) 
	$nounina=1;
	$nolnina=1;
	$nofnina=1;

	if(stripos($recproca,$_SESSION["username"]) !== false)
	{
	//can't have username in answer
	$nounina=0;
	unset($recprocsecretquestionanswerlistarray[$recprocarraypointer]);
	}

	if(stripos($recproca,$recprocuserinfo[0][sn][0]) !== false)
        {
        //can't have last name in answer
	$nolnina=0;
	unset($recprocsecretquestionanswerlistarray[$recprocarraypointer]);
        }

	if(stripos($recproca,$recprocuserinfo[0][givenname][0]) !== false)
        {
        //can't have first name in answer
	$nofnina=0;
	unset($recprocsecretquestionanswerlistarray[$recprocarraypointer]);
        }



	//send question change to AD
        try {
                $result=$adldap->user()->setrecoveyqs($_SESSION["username"],serialize($recprocsecretquestionlistarray));
            }
        catch (adLDAPException $raw) {
		addtolog('./logs/question-set-fail.log',$username." - ".$raw);
            }
	//send answer change to AD
        try {
                $result=$adldap->user()->setrecoveyans($_SESSION["username"],serialize($recprocsecretquestionanswerlistarray));
            }
        catch (adLDAPException $raw) {
		 addtolog('./logs/answer-set-fail.log',$username." - ".$raw);

            }

	}
	//delay page load, quick fix for sleapy AD
	sleep($ADdelay);
}



//check session logged in and change recovery email
if (($_POST['receml']!="")&&($_SESSION["loggedin"]==="true"))
{

	try {
                $result=$adldap->user()->setrecoveyemail($_SESSION["username"],$_POST['receml']);
            }
        catch (adLDAPException $acctulraw) {
            }
	//delay page load, quick fix for sleapy AD
        sleep($ADdelay);
}



//check session logged in and unlock account
if (($_POST['acctunlock']!="")&&($_SESSION["loggedin"]==="true"))
{
//acct unlock required

	try {
            	$result=$adldap->user()->unlock($_SESSION["username"]);
            }
        catch (adLDAPException $acctulraw) {
		addtolog('./logs/account-unlock-fail.log',$_SESSION["username"]." - ".$acctulraw);
            }
addtolog('./logs/account-unlock.log',$_SESSION["username"]);
	 //delay page load, quick fix for sleapy AD
        sleep($ADdelay);

}


// check session logged in and unlock account reset password
if (($_POST['newpassword']!="")&&($_SESSION["loggedin"]==="true")){
//password update requested
	if ($_POST['newpassword']!=$_POST['newpasswordcomf'])
        {
	// password update no match so don't update
	addtolog('./logs/account-passchange-err-nomatch.log',$username." no match");
        }
	else
	{
	//set password ok flag to true (if fails will knock it to false)
	$passwordupdateOK = true;
	//password match and present so update and check for AD refusal erros
		try {
  		$result=$adldap->user()->password($_SESSION["username"],$_POST['newpassword']);
		}
		catch (adLDAPException $passwordupdateerrraw) {
		$passwordupdateerr=substr(strtok($passwordupdateerrraw,'/'),0,-3);
		$passwordupdateOK=false;
		}
	}

	if($passwordupdateOK){
	 $usersfields=$ausersfields=array("mail","accountRecoveryEmail","displayName");
         $userinfo=$adldap->user()->info($_SESSION["username"],$usersfields);
         $displayname=$userinfo[0]['displayname'][0];
	 $receml=$userinfo[0]['accountrecoveryemail'][0];
	 $mainmail=$userinfo[0]['mail'][0];

	 addtolog('./logs/account-passchange-ok.log',$username);
	$message = $message."<title>"."Password Change"."</title>";
	$message = $message."<H1><center><u>"."Password Change"."</u></center></H1>";
        $message = $message."<br /> Hello ".$displayname."<br /> Just to let you know your password has been changed via the self service account managment system. <br /> If you did not make this change please contact the IT service desk for help. ";
	$subject = "Password Change Notification";
		if(strlen($receml)===0)
		{
		
		}
		else
		{
		$mailto = $receml;
		mail($mailto, $subject, $message, $headers);
		}

		if(strlen($mainmail)===0)
                {

                }
                else
                {
                $mailto = $mainmail;
                mail($mailto, $subject, $message, $headers);
                }

	}
	else
	{
	 addtolog('./logs/account-passchange-fail.log',$username."".$passwordupdateerrraw);
	}

	//delay page load, quick fix for sleapy AD
        sleep($ADdelay);

}


//page code as chosen by user
if( isset($_GET["page"])){
$pagecodeuser=$_GET["page"];
$pagecode=$_GET["page"];
}

if(isset($_POST["username"])){
//username variable set so ok to test
$usrnamet=$_POST["username"];
if(strlen($usrnamet)===0){
	$pagecode="login";
	$pageresponder="<center>Error: you did not enter a username</center>";
   }
   else
   {
	$username=$_POST["username"];
   }
}
else
{
// userame not set so back to login page
$pagecode="login";
}



//test values filled in for secret Qs
if($pagecode==="authcheckSQ"){

	// no answer given to sec question 1
	if( strlen($_POST["answer1"])===0 )
	{
	$a1fail="true";
	}

	// no answer given to sec question 2
	if( strlen($_POST["answer2"])===0 )
	{
	$a2fail="true";
	}

	if ($a1fail==="true")
	{//1 fail
	 $pageresponder="<center>Error: you did not enter secret question answer 1</center>";
	 $pagecode="login";
	}
	if ( $a2fail==="true")
	{//2 fail
	 $pageresponder="<center>Error: you did not enter secret question answer 2</center>";
	 $pagecode="login";
	}

	if (($a1fail==="true")&&($a2fail==="true"))
	{//both empty
	$pageresponder="<center>Error: you did not enter secret question answer 1 or 2</center>";
	$pagecode="login";
	}

}

// AD auth section
if($pagecode==="authcheckAD"){

//test if pw filled in for AD auth
// no password entered
        if( strlen($_POST["password"])===0 )
        {
                if( strlen($pageresponder)===0){
                //nothing already in $pageresponder
                        $pageresponder="<center>Error: you did not enter a password</center>";
                        $pagecode="login";
                }
                else
                {
                        $pageresponder=$pageresponder."<center>Error: you did not enter a password</center>";
                        $pagecode="login";
                }
        }
        else
        {
        $password=$_POST["password"];
	//print "---- password detected ------";//debug
        }

}





// logout handler


//log them out
$logout=$_GET["logout"];
if ($logout==="yes"){ //destroy the session
	session_start();
	$_SESSION = array();
	session_destroy();
	$pagecode="login";
	$pageresponder="<center>Logged out</center>";
}

//username harvist from post
$username=strtoupper($_POST["username"]); //remove case sensitivity on the username

if ($pagecode==="authcheckAD"){
//AD check mode so harvist password form post
$password=$_POST["password"];
$authmode="AD";
//print "------ AD login mode detected -------";//debug
}

if ($pagecode==="authcheckSQ"){
//Secret Question mode so harvist answer
$answer1=$_POST["answer1"];
$answer2=$_POST["answer2"];
$linkcode1=$_POST["linkcode1"];
$linkcode2=$_POST["linkcode2"];
$authmode="SQ";
}
//print "----- pre auth mode check -----";//debug
//print "===== auth mode is = ".$authmode." =====";//debug
if ( (strlen($authmode)===0)&&( ($pagecode==="authcheckAD")||($pagecode==="authcheckSQ") ) )
{//auth mode not set, never should happen but let's catch the error anyway
 print "------ auth mode fail ----";
 $pagecode="login";
 $pageresponder="<center>System Error: authentication mode not set </center>";
}
else
{
if ($authmode==="AD"){
//AD auth module
        //authenticate the user
        if ($adldap -> authenticate($username,$password)){
        //user authenticated against AD
	//print "--- user valid login cred ---";//debug
                if(($adldap->user()->inGroup($username,$usersgroup))||($adldap->user()->inGroup($username,$adminsgroup)))
                {
			//print "------- pre session start -----";
                        //establish your session and redirect
                        //session_start();
			$_SESSION["loginmode"]="AD";
                        $_SESSION["loggedin"]="true";
                        $_SESSION["username"]=$username;
                        $_SESSION["ingroup"]=$usersgroup; // basic group access first
                        //--------------------------------------
                        // super user / root groups
                        if ($adldap->user()->inGroup($username,$adminsgroup))
                        {
                                $_SESSION["ingroup"]=$adminsgroup;
				$_SESSION["admin"]="true";
                        }
			//print "----- logged in ok ------ pre log -----";//debug
                        addtolog('./logs/validlogin.log',$username." , ".$_SESSION["ingroup"]);
			session_write_close();

                }
		else
		{
		//group fail
		$pageresponder="<center>Error: Access to this tool is not avalable, you are not in an approved group</center>";
        	$pagecode="login";
		addtolog('./logs/AD-group-fail.log',$username);
		}
        }
        else
        {
        //AD auth faild
        $pageresponder="<center>Error: Active Directory Authentication Error</center>";
        $pagecode="login";
	//print "----- loggin faild ---- pre log ----";//debug
        addtolog('./logs/AD-login-fail.log',$username);
        }



}

if ($authmode==="SQ"){
//SQ auth module

	 $rqusersfields=$ausersfields=array("secretQuestionAnswerList","secretQuestionLockout","accountRecoveryEmail","displayName");
         $recuserinfo=$adldap->user()->info($_POST['recusername'],$rqusersfields);
         $recuserdisplayname=$recuserinfo[0]['displayname'][0];
         $recsecretquestionanswerlistarray=unserialize($recuserinfo[0][secretquestionanswerlist][0]);

	//enable cypher use
	//added peruser cyper mod
	$cipher = new Cipher($cipherkey.strtoupper($_POST['recusername']));

	$ans1ok=0;//set code ok to false
	$ans2ok=0;//set code ok to false

	if( strcasecmp($cipher->decrypt($recsecretquestionanswerlistarray[$linkcode1]),$answer1) == 0)
	{
	//answer matches so OK
	$ans1ok=1;
	}
	else
	{
	//SQ auth faild
	}

	if( strcasecmp($cipher->decrypt($recsecretquestionanswerlistarray[$linkcode2]),$answer2) == 0)
        {
        //answer matches so OK
        $ans2ok=1;
        }
	else
	{
	 //SQ auth faild
	}

	//backup defence wall, got to stop null null match if a user had no SQs and entered no answers too, should be caugth by code further up but cant be too safe

	if( strlen($answer1)<1)
	{
	 $ans1ok=0;//set code ok to false
	}
	 if( strlen($answer2)<1)
        {
         $ans1ok=0;//set code ok to false
        }



	if(!$ans1ok||!$ans2ok)
	{
	 //SQ auth faild
        $pageresponder="<center>Error: Secret Question Authentication Error</center>";
        $pagecode="login";
        addtolog('./logs/SQ-login-fail.log',$_POST['recusername']);
	}
	else
	{
	//codes match so login
		 if(($adldap->user()->inGroup($_POST['recusername'],$usersgroup))||($adldap->user()->inGroup($_POST['recusername'],$adminsgroup)))
                {
                        $_SESSION["loginmode"]="SQ";
                        $_SESSION["loggedin"]="true";
                        $_SESSION["username"]=$_POST['recusername'];
                        $_SESSION["ingroup"]=$usersgroup; // basic group access first
                        //--------------------------------------
                        // super user / root groups
                        if ($adldap->user()->inGroup($username,$adminsgroup))
                        {
                                $_SESSION["ingroup"]=$adminsgroup;
                                $_SESSION["admin"]="true";
                        }
                        //print "----- logged in ok ------ pre log -----";//debug
                        addtolog('./logs/validlogin.log',$username." , ".$_SESSION["ingroup"]);
                        session_write_close();

                }
                else
                {
                //group fail
                $pageresponder="<center>Error: Access to this tool is not avalable, you are not in an approved group</center>";
                $pagecode="login";
                addtolog('./logs/AD-group-fail.log',$username);
                }

	}

}

}




//check if logged in
if ( $_SESSION["loggedin"]==="true")
{
$username=$_SESSION["username"];
$loginmode=$_SESSION["loginmode"];
//print "------==== logged in mode = ".$loginmode." ====-----";//debug
//print "-----+++++ pagecodeuser = ".$pagecodeuser." +++----";//debug

//ldap if not active
        try {
                $adldap = new adLDAP();
        }
        catch (adLDAPException $e) {
            echo $e; exit();
        }

//get user display name etc. from username
$ausersfields=array("*");
$userinfo=$adldap->user()->info($username,$ausersfields);
$userdisplayname=$userinfo[0]['displayname'][0];
$useraccountcontrolvalue=$userinfo[0]['useraccountcontrol'][0];
$userpicstring=$userinfo[0]['thumbnailphoto'][0];


//account state lookup
$useracctlockstate=0;//unlocked
if(isset($userinfo[0][lockouttime][0]))
{
	if($userinfo[0][lockouttime][0]!=0)
	{
	$useracctlockstate=1;//account is locked out
	}
}



//interprit useraccountcontrol values

switch ($useraccountcontrolvalue) {
    case 512:
        $useraccountcontrolintp="Enabled";
        break;
    case 514:
        $useraccountcontrolintp="Disabled";
        break;
    case 66048:
        $useraccountcontrolintp="Enabled (".$useraccountcontrolvalue.")";
        break;
    case 66050:
        $useraccountcontrolintp="Disabled (".$useraccountcontrolvalue.")";
        break;
    case 544:
        $useraccountcontrolintp="Change Password";
        break;
    case 262656:
        $useraccountcontrolintp="Requires Smart Card";
        break;
    case 1:
        $useraccountcontrolintp="Locked Disabled";
        break;
    case 8388608 :
	$useraccountcontrolintp="Password Expired";
        break;
    default:
        $useraccountcontrolintp="Unknown (".$useraccountcontrolvalue.")";
}






//print "userdisplayname = ".$userdisplayname." |||--";//debug
   if ($loginmode==="AD")
   {

	//print "+++++ page code in ad check = ".$pagecode." +++++++";//debug
      if ($pagecode==="authcheckAD")
      {
	//print "-=-=-=- entery page AD detect -=-=-=-";//debug
 	 $pagecode="rec";
      }
      else
      {
	 //print "-=-=-=- page navigation AD detect -=-=-=-";//debug
	 $pagecode=$pagecodeuser;
      }

   }

   if ($loginmode==="SQ")
   {

        //print "+++++ page code in ad check = ".$pagecode." +++++++";//debug
      if ($pagecode==="authcheckSQ")
      {
        //print "-=-=-=- entery page AD detect -=-=-=-";//debug
         $pagecode="res";
      }
      else
      {
         //print "-=-=-=- page navigation AD detect -=-=-=-";//debug
         $pagecode=$pagecodeuser;
      }

   }

}








?>
<!doctype html>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Web AD Account Tools</title>
		<script type="text/javascript" src="js/pagetabs.js"></script>
<?php
 if ($_SESSION["loggedin"]==="true")
        {
	//logged in so lock menu expanded 
	}
	else
	{
	//not logged in so let menu float up (its empty anyway at this point)
	print ' <script type="text/javascript" src="js/meny.js"></script> ';
	}
?>
		<script type="text/javascript" src="js/addEvent.js"></script>
		<script type="text/javascript" src="js/sweetTitles.js"></script>
		<script type="text/javascript" src="js/jquery.min.js"></script>
		<script type="text/javascript"> 
		$(document).ready(function() {

			$('a.login-window').click(function() {

			// Getting the variable's value from a link
			var loginBox = $(this).attr('href');

			//Fade in the Popup and add close button
			$(loginBox).fadeIn(300);

			//Set the center alignment padding + border
			var popMargTop = ($(loginBox).height() + 24) / 2;
			var popMargLeft = ($(loginBox).width() + 24) / 2;

			$(loginBox).css({
			'margin-top' : -popMargTop,
			'margin-left' : -popMargLeft
			});

			// Add the mask to body
			$('body').append('<div id="mask"></div>');
			$("#mask").css('filter', 'alpha(opacity=60)');
			$('#mask').fadeIn(300);

			return false;
			});

			// When clicking on the button close or the mask layer the popup closed
			$('a.close, #mask').live('click', function() {
		  	    $('#mask , .login-popup').fadeOut(300 , function() {
				$('#mask').remove();
			});
			return false;
		     	});
		});
		</script>


	<?php
	require("./components/loadcss.cp.php");
	?>

	</head>
	<body>


	<?php
	 if ($_SESSION["loggedin"]==="true")
	 {
		print'<div id="sidebar">';
			print 'Logged in as <br />'.$userdisplayname.' <br />';
			if($userphotoenable==="yes")
			{//switch to turn off photo option
			include './components/ADuserphoto.cp.php'; print ' <br />';
			}
			print'account is '.$useraccountcontrolintp.' <br />';
			print 'Login mode = '.$_SESSION["loginmode"].' <br />';;
		print'</div>';
		if ($_SESSION["admin"]==="true")
		  {
			print'<div id="sidebaradmin">';
                        print 'Admin Mode Enabled';
	                print'</div>';
		  }
		if ($useracctlockstate===1)
		  {
		  print'<div id="sidebarlockedout">';
                  print 'Account Locked Out';
                  print'</div>';
		  }

	 }
	?>

		<div class="menu">
			<nav>

				<div id="handle"><div class="downarrow"></div></div>
				<ul>
					<?php
					//ie8 hack
                        		print '<!--[if lt IE 9]>';
                        		print '<table style="width:100%;position:absolute;top:0px; "><tr><td style="text-align: left; color:white; font-size:1.5em;">';
                        		print '<![endif]-->';

					if ($_SESSION["loggedin"]==="true")
				        {
					print '<hdl>Web AD Account Tools</hdl>';
					}
					
					//ie8 hack
                        		print '<!--[if lt IE 9]>';
                        		print '</td><td style="text-align: right; color:white; font-size:0.7em;">';
                        		print '<![endif]-->';
                        		?>

					<hdr>
					<?php
					if ($_SESSION["admin"]==="true")
                  			{
					print '<a style="color:white;"  title="<u>Component versions</u> <br><br> adLDAP 4.0.3 <br> Sweet Titles 1.0 <br> meny 1.1 <br> addtolog 1.0 <br> ADuserphoto 2.0 <br>listdirfiles 1.0 <br> loadcss 1.0 <br> spinner 1.0 <br> pagetabs 1.0 <br > jquery 1.6.2 <br> HTML5 Shiv 3.7.2"><version><hdr>VER = '.$masterversion .'</hdr><version></a>';
					}
					else
					{
					print '<version><hdr>VER = '.$masterversion .'</hdr><version>';
					}
					?>
					</hdr>

 					<?php
                        		//ie8 hack
                        		print '<!--[if lt IE 9]>';
                        		print '</td></tr></table>';
                        		print '<![endif]-->';
                        		?>


					<?php
					//if (($pagecode==="rec") || ($pagecode==="ula")||($pagecode==="res")||($pagecode==="logs"))
                                        if ($_SESSION["loggedin"]==="true")
                                        {
                                        //logged in
                                                if ($_SESSION["loginmode"]==="AD")
                                                {
                                                        print '<li><a href="?page=rec"'; if ($pagecode==="rec"){print 'class="active"';} print '>Set Your Recovery Questions</a></li>';
							print '<li><a href="?page=res"'; if ($pagecode==="res"){print 'class="active"';} print ' >Reset AD Password</a></li>';
                                                }
                                                if ($_SESSION["loginmode"]==="SQ")
                                                {
							 print '<li><a href="?page=res"'; if ($pagecode==="res"){print 'class="active"';} print ' >Reset AD Password / Unlock Account</a></li>';
                                                }


                                        ///////////extra pages only for logged in people

					include_once("./components/listdirfiles.cp.php");
                        		//tell it where the extra modules are and run function
                        		$pagesfolder = getDirectoryList("./modules");
                        		//tab render logic
                        		if (count($pagesfolder)!=0)
                        		{
		                          foreach($pagesfolder as $apage)
					  {
					     $fileext=".".end(explode('.',$apage));
					     $fileextnodot=end(explode('.',$apage));
					     print '<li><a href="?page='.$fileextnodot.'"'; 
					     if ($pagecode==="'.$fileextnodot.'"){print 'class="active"';}
					     print ' >';
					     print  str_ireplace($fileext, '', $apage, $count);
					     print '</a></li>';
					  }
					}

                                        //////////end of extra pages
					 if ($_SESSION["admin"]==="true")
                                                {
						        print '<li><a href="?page=logs"'; if ($pagecode==="logs"){print 'class="active"';} print ' >View Logs</a></li>';
						}



						print '<li><a href="?logout=yes"'; if ($pagecode==="login"){print 'class="active"';} print ' >Logout</a></li>';
                                        }
					?>
				</ul>
			</nav>
		</div>
		<div class="container">
			<?php
                        if ($pagecode==="login"){
                        //login UI segment
                        print ' <header>';
                        print ' <br />';
                        print ' <h1>Please Authenticate To Access This Site</h1>';
                        print ' </header>';
                        print '<article>';

			print '<div class="container">';
			print '	<div id="content">';
			print '<center><img width="'.$loginlogosizeWidth.'" src="'.$loginlogopath.'" ></center>';
			print '		<div class="post">';
        		print '		<div class="btn-sign">';
			print '			<a href="#login-box" class="login-window">Click to Proceed</a>';
        		print '		</div>';
			print '		</div>';
			print '	     '.$pageresponder;

			print '	<div id="login-box" class="login-popup">';
			
			
                        //ie8 hack
                        print '<!--[if lt IE 9]>';
                        print '<table style="width:100%"><tr><td style="padding-right: 15px;">';
                        print '<![endif]-->';
                        
			

                        // if you don't know your password
                        print '         <lhf>';
                        print '         <h3>  If you forgot your password <br /> or your account is locked out</h3>';
                        print '         <form Name="PWNK" id="PWNK"  method="post" class="signin" action="?page=sq">';
                        print '                 <fieldset class="textbox">';
                        print '                 <label class="username">';
                        print '                 <span>Username</span>';
                        print '                 <input id="username" name="username" value="" type="text" autocomplete="off" placeholder="username eg. bbobberson">';
                        print '                 </label>';

                        print '                  <button class="submit button" type="button" onclick="$(this).closest('."'form'".').submit()" >Answer Security Questions</button>';
                        print '                 </fieldset>';
                        print '         </form>';
                        print '         </lhf>';

			//ie8 hack
                        print '<!--[if lt IE 9]>';
                        print '</td><td style="">';
                        print '<![endif]-->';


			// if you know your password
			print '         <rhf>';
			print '		<h3>  If you know your password</h3>';
          		print '		<form Name="PWK" id="PWK" method="post" class="signin" action="?page=authcheckAD">';
                	print '			<fieldset class="textbox">';
            		print '			<label class="username">';
                	print '			<span>Username</span>';
                	print '			<input id="username" name="username" value="" type="text" autocomplete="off" placeholder="username eg bbobberson">';
                	print '			</label>';

			print '			<label class="password">';
                	print '			<span>Password</span>';
                	print '			<input id="password" name="password" value="" type="password" placeholder="your password eg. Pa33w0Rd">';
               		print '			</label>';

			print '			<button class="submit button" type="button" onclick="$(this).closest('."'form'".').submit()" >Sign in</button>';
                	print '			</fieldset>';
          		print '		</form>';
			print '         </rhf>';

			//ie8 hack
                        print '<!--[if lt IE 9]>';
                        print '</td></tr></table>';
                        print '<![endif]-->';
			/////////////////////////////////////
			print '	     </div>';
			print '    </div>';
			print '</div>';
			print ' <p class="lastword">&nbsp;</p>';
                        print '</article>';
                        }

			




			if ($pagecode==='sq')
			{

			 //recovery questions segment
                        print ' <header>';
                        print ' <br />';
                        print ' <h1>Please Authenticate To Access This Site</h1>';
                        print ' </header>';
                        print '<article>';

                        print '<div class="container">';
                        print ' <div id="content">';

                        print '         <div class="post">';
                        print '         <div class="btn-sign">';
                        print '                 <a href="#login-box" class="login-window">Click to Proceed</a>';
                        print '         </div>';
                        print '         </div>';

                        print ' <div id="login-box" class="login-popup">';

			//ie8 hack
                        print '<!--[if lt IE 9]>';
                        print '<table style="width:100%"><tr><td style="padding-right: 15px;">';
                        print '<![endif]-->';

			// if you know your password
                        print '         <lhf>';
						//get user display name etc. from username
                                                $rqusersfields=$ausersfields=array("userAccountControl","secretQuestionAnswerList","secretQuestionList","secretQuestionLockout","accountRecoveryEmail","displayName");

                                                $recuserinfo=$adldap->user()->info($_POST['username'],$rqusersfields);
                                                $recuserdisplayname=$recuserinfo[0]['displayname'][0];
                                                $recsecretquestionlistarray=unserialize($recuserinfo[0][secretquestionlist][0]);
                                                $recsecretquestionanswerlistarray=unserialize($recuserinfo[0][secretquestionanswerlist][0]);
                                                $questions=array_rand($recsecretquestionlistarray,2);
						$reclockout=$recuserinfo[0][secretquestionlockout][0];
						$acctenabled=$recuserinfo[0][useraccountcontrol][0];

			//print"<hr><pre>";
			//print_R($recuserinfo);
			//print"</pre><hr>";
			print $acctenabled."-".$reclockout."<br />";
			if((($reclockout!="0")&&($reclockout!=""))||($acctenabled==="514")||($acctenabled==="66050"))
			{
                        print "Sorry, self service recovery function is not currently avalable <br /> This account currently has recovery function locked out.";
			addtolog('./logs/recovery-lockout-fail.log',$username." - ".$acctenabled."-".$reclockout);
                        }
			else
			{
				if((count($recsecretquestionlistarray)>=$minnumberofsq)&&(count($recsecretquestionanswerlistarray)>=$minnumberofsq))
				{
				//enable cypher use
        			//added peruser cyper mod
				//print ">>>>>>>>>>".$_POST['username']."<<<<<<<<<<<<";
        			$cipher = new Cipher($cipherkey.strtoupper($_POST['username']));

				// more than one rec question set so show option
                        	print '         <h3>  If you forgot your password</h3>';
                        	print '         <form Name="PWNK" id="PWNK" method="post" class="signin" action="?page=authcheckSQ">';
				print '                 <fieldset class="textbox">';
				print '                 <label class="textbox">';
                        	print '                 <input id="username" name="username" value="'.$_POST['username'].'" type="text" hidden="true" >';
                        	print '                 </label>';
				print "			Hello ".$recuserdisplayname." please use the<br /> following Question/Prompt <br />";
						print'<input id="recusername" name="recusername" value="'.$_POST['username'].'" type="hidden">';
						print'<input id="linkcode1" name="linkcode1" value="'.$questions[0].'" type="hidden">';
						print'<input id="linkcode2" name="linkcode2" value="'.$questions[1].'" type="hidden">';

				print '			>> '.$cipher->decrypt($recsecretquestionlistarray[$questions[0]]).' <<';
                        	print '                 <label class="password">';
                        	print '                 <span>Answer</span>';
                        	print '                 <input id="answer1" name="answer1" value="" type="password" autocomplete="off" placeholder="Your Answer">';
                        	print '                 </label>';

				print '                 >> '.$cipher->decrypt($recsecretquestionlistarray[$questions[1]]).' <<';
                        	print '                 <label class="password">';
                        	print '                 <span>Answer</span>';
                        	print '                 <input id="answer2" name="answer2" value="" type="password" autocomplete="off" placeholder="Your Answer">';
                        	print '                 </label>';

                        	print '                 <button class="submit button" type="button" onclick="$(this).closest('."'form'".').submit()" >Sign in</button>';
                        	print '                 </fieldset>';
                        	print '         </form>';
				}
				else
				{
				print "Sorry, self service recovery function is not currently avalable <br /> an insufficient number of recovery questions have been set for your account.";
				}
			}
								
                        print '         </lhf>';
                        //ie8 hack
                        print '<!--[if lt IE 9]>';
                        print '</td><td style="">';
                        print '<![endif]-->';

                        // if you know your password
                        print '         <rhf>';
                        print '         <h3>  If you know your password</h3>';
                        print '         <form Name="PWK" id="PWK"  method="post" class="signin" action="?page=login">';
                        print '                 <fieldset class="textbox">';
                        print '                  <button class="submit button" type="button" onclick="$(this).closest('."'form'".').submit()" >Login Using AD</button>';
                        print '                 </fieldset>';
                        print '         </form>';
                        print '         </rhf>';

			//ie8 hack
                        print '<!--[if lt IE 9]>';
                        print '</td></tr></table>';
                        print '<![endif]-->';
                        /////////////////////////////////////
                        print '      </div>';
                        print '    </div>';
                        print '</div>';
                        print ' <p class="lastword">&nbsp;</p>';
                        print '</article>';
                        }






			if ($pagecode==="rec"){
			//query AD for recovery info
			$ausersfields=array("secretQuestionAnswerList","secretQuestionList","secretQuestionLockout","accountRecoveryEmail");
			$recuserinfo=$adldap->user()->info($username,$ausersfields);
			
			//print "<pre>";
			//print_R($recuserinfo);
			//print "</pre>";
			//recovery questions segment
			print '	<header>';
			print '	<br />';
			print '	<h1>Set Your Recovery Questions / information</h1>';
			print '	</header>';
			print '<article>';
			
			if($recuserinfo[0][secretquestionlockout][0]==="1")
			{
			print ' <section class="box0">';
                        print '         <h2>// Section locked out</h2>';
                        print '         <p>Self service recovery is currently disabled for this account</p>';
                        print '         <p>if you think this is in error please contact IT support</p>';
                        print ' </section>';

			}
			else
			{
			
				//recovery not locked out so show info
				//----------------------------------------------------------------------------------------------

				//trigger error section if error thrown
				if($nounina===0){$recseterr=true;}
				if($nolnina===0){$recseterr=true;}
				if($nofnina===0){$recseterr=true;}

				//
				if($recseterr)
				{
				print ' <section class="box-1">';
                                print '         <h2>// Errors</h2>';

				if($nounina===0){print "you can't have your user name in an answer<br/>";}
                                if($nolnina===0){print "you can't have your last name in an answer<br />";}
                                if($nofnina===0){print "you can't have your first name in an answer";}
					

                                print '<br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /> </section>';
				}
				else
				{
				//empty tile
				print ' <section class="box-1">';
				print '<br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br />';
				print ' </section>';
				}
				//--------------------------------------


				print ' <section class="box0">';
                        	print '         <h2>// Recovery Email</h2>';


				print '<form  Name="receml" id="receml"  method="post" action="?page=rec">';
                        	print '<fieldset>';

                        	print '<label for="name">Email</label><br />';
                        	print '<input id="receml" name="receml" type="email"  class="text" size="30"  value="'.$recuserinfo[0][accountrecoveryemail][0].'" autocomplete="off"  required/>';

                       		print '<br /> <br /><passwdcngform>';
                        	print '<button class="submit button" type="button" onclick="$(this).closest('."'form'".').submit()" >Update</button>';
				print '<center><br />The above email address (if you enter one) will be used (in addition to your work email address) to send you notifications of changes to your account made using this system</center>';
                        	print '</passwdcngform>';
                        	print '</fieldset>';
                        	print '</form>';

				//print '<br />';
                        	print ' </section>';


				//--------------------------
				//empty tile
                                print ' <section class="box-2">';
				print '         <h2>// Information</h2>';
				print '<center> Please fill in the below question and answer sections. Each Q&A set will unlock when you fill in the set before it. eg. completing set 1 will unlock set 2</center> <br />';
				
				 //load questions/answeres
                                $secretquestionlistarray=unserialize($recuserinfo[0][secretquestionlist][0]);
                                $secretquestionanswerlistarray=unserialize($recuserinfo[0][secretquestionanswerlist][0]);



				$presentQs=count($secretquestionlistarray);
				$presentAs=count($secretquestionanswerlistarray);
				$presntAQsets=min(array($presentQs,$presentAs));

				if((count($secretquestionlistarray)>=$minnumberofsq)&&(count($secretquestionanswerlistarray)>=$minnumberofsq))
				{//meets reqd number of Q&A sets
				print '<center>you have '.$minnumberofsq.' or more Q&A sets filled in password recovery function enabled</center>';
				}
				else
				{//need more Q&A sets
				print '<center>You need to fill in ';
				print $minnumberofsq-$presntAQsets;
				print ' more Q&A sets to enable the password recovery function for your account</center>';
				}
				
                                print '<br /><br /><br /><br /><br /><br /> </section>';
				//-----------------

			//-----------------
			//print"<hr><pre>";
			//print_R($secretquestionlistarray);
			//print"</pre><hr>";
			//print"<pre>";
                        //print_R($secretquestionanswerlistarray);
                        //print"</pre>";

			//-----------------

				$qsarrycount=1;//counter set
				//foreach ($secretquestionlistarray as $question)
				while ($numberofsq >= $qsarrycount)
				{
				print ' <section class="box'.$qsarrycount.'">';
				print '<form  Name="recq'.$qsarrycount.'" id="recq'.$qsarrycount.'"  method="post" action="?page=rec">';
                                print '<fieldset>';
				
        	                print '         <h2>// Set '.$qsarrycount.'</h2>';

				print '<label for="name">Question</label><br />';
                                print '<input id="recq" name="recq" type="test"  class="text" size="30" ';
				if($secretquestionlistarray[$qsarrycount]!=""){print 'value="'.$cipher->decrypt($secretquestionlistarray[$qsarrycount]).'"';}else{ if($qsarrycount>=2){if($secretquestionanswerlistarray[$qsarrycount-1]){}else{print ' disabled style="background:gray;" ';} } }
				print ' autocomplete="off"  required';
                                print '	/>';
				print '<label for="name">Answer</label><br />';
                                print '<input id="reca" name="reca" type="test"  class="text" size="30"  ';
				if($secretquestionanswerlistarray[$qsarrycount]){print ' value="'.$cipher->decrypt($secretquestionanswerlistarray[$qsarrycount]).'"';}else{  if($qsarrycount>=2){if($secretquestionanswerlistarray[$qsarrycount-1]){}else{print ' disabled  style="background:gray;" ';} } }
				print ' autocomplete="off"  required'; 
				print '/>';
				print '<input id="reccount" name="reccount" type="hidden"  class="text" size="30"  value="'.$qsarrycount.'" autocomplete="off"/>';

				
				print '<br /> <br /><passwdcngform>';
                                print '<button class="submit button" type="button" onclick="$(this).closest('."'form'".').submit()"';
				if($secretquestionanswerlistarray[$qsarrycount]){}else{ if($qsarrycount>=2){if($secretquestionanswerlistarray[$qsarrycount-1]){}else{print ' disabled  style="background:gray;" ';} }}
				print ' >Update</button>';
                                print '</passwdcngform>';
                                print '</fieldset>';
                                print '</form>';
           		        print ' </section>';
				$qsarrycount++;//counter increment
				}

			}
			print '	<p class="lastword">&nbsp;</p>';
			print '</article>';
			}

			 if ($pagecode==="res"){
                        //AD Password Reset segment
                        print ' <header>';
                        print ' <br />';
                        print ' <h1>Reset Your Account Password';
			if ($_SESSION["loginmode"]==="SQ"){print ' or Unlock Your Account';}
			print '</h1>';
                        print ' </header>';
                        print '<article>';

			print '<center>';

			if ($passwordupdateerr!=""){
			print "<b>Password update refused by Active Directory<br />Error provided : ".$passwordupdateerr."</b><br />";
			}
			if ($passwordupdateOK){
                        print "<b>Password Updated</b> <br />";
                        }

			if ($_POST['newpassword']!=""){
				if ($_POST['newpassword']!=$_POST['newpasswordcomf'])
				{
				print "</b>Passwords do not match </b><br />";
				}
			}

			print '<form  Name="PWCNG" id="PWCNG"  method="post" action="?page=res">';
			print '<fieldset>';

			print '<label for="name">New Password </label><br />';
			print '<input id="newpassword" name="newpassword" type="password" class="text" size="40" value="" autocomplete="off" autofocus required />';
			print '<br /><br />';
			print '<label for="name">New Password again </label><br />';
                        print '<input id="newpasswordcomf" name="newpasswordcomf" type="password"  class="text" size="40"  value="" autocomplete="off"  required/>';

			print '<br /> <br /><passwdcngform>';
			print '<button class="submit button" type="button" onclick="$(this).closest('."'form'".').submit()" >Update</button>';

			print '</passwdcngform>';
			print '</fieldset>';
			print '</form>';
			print '</center>';

			//--------------------------------------------
			if ($_SESSION["loginmode"]==="SQ")
                        {
			//display unlock on pw reset page but only if SQ login

			print '<form  Name="AACTUL" id="AACTUL"  method="post" action="?page=res">';
                        print '<input id="acctunlock" name="acctunlock" type="hidden" value="unlock"/>';
                        print '<br /> <br /><passwdcngform><center>';
                         if ($useracctlockstate===1)
                          {
                           print 'Account Currently Locked Out <br />';
                          }
                        else
                          {
                          print 'Account Currently Unlocked <br />';
                          }

                        print '<button class="submit button" type="button" onclick="$(this).closest('."'form'".').submit()" ';
                        if ($useracctlockstate===1){}else{print ' disabled  style="background:gray;" ';}
                        print ' >Unlock Account</button>';
                        print '</center></passwdcngform>';
                        print '</form>';

			}
			//--------------------------------------------


                        print ' <p class="lastword">&nbsp;</p>';
                        print '</article>';
			}


			 if ($pagecode==="logs"){
                        //log viewer segment
                        print ' <header>';
                        print ' <br />';
                        print ' <h1>view logs</h1>';
                        print ' </header>';
                        print '<article>';

			//log render bit
			print'<div id="wrapper">';
			//load lib for folder reading
			include_once("./components/listdirfiles.cp.php");
			//tell it where logs are and run function
			$logsfolder = getDirectoryList("./logs");
			// loop variables setup
			$instanceno = 1;
			$tabinstanceno = 1;
			//log render logic
			if (count($logsfolder)!=0)
			{
			  print'<div id="tabContainer">';
			  print' <div id="tabs">';
			  print'    <ul>';
			  foreach($logsfolder as $alog)
		                {
		                    print  '<li id="tabHeader_'.$tabinstanceno.'">';
		                    $count =0;
		                    $fileext=".".end(explode('.',$alog));
		                    print  str_ireplace($fileext, '', $alog, $count);
		                    print '</li>';
		                    $tabinstanceno = $tabinstanceno +1;
		                }


			  print'    </ul>';
			  print'  </div>';
			  print'  <div id="tabscontent">';

			 foreach($logsfolder as $alog)
               			 {
               		    	 	print '<div class="tabpage" id="tabpage_'.$instanceno.'" >';
                        		// start of log print';

                                	echo '<table border="1" width="600">';
                                	$f = fopen("./logs/".$alog, "r");
                                	while (($line = fgetcsv($f,null,";")) !== false) {
                                      		echo '<tr>';
                                      		foreach ($line as $cell) {
                                      		        echo '<td width="190"><font color="#000000">' . htmlspecialchars($cell) . '</font></td>';
                                      		}
                                      	echo "</tr>";
                                }
                                fclose($f);
                                echo "</table>";
                       		// end of log print
                        	$instanceno = $instanceno + 1;
                        	print '</div>';
                 	}
 			 print'  </div>';
			}
			else
			{
			        print "No log files detected";
			}

			print'</div>';
			// end of log render logic

                        print ' <p class="lastword">&nbsp;</p>';
                        print '</article>';
                        }

			////// extra pages render content
			include_once("./components/listdirfiles.cp.php");
                        //tell it where the extra modules are and run function
                        $pagesfolder = getDirectoryList("./modules");
                        //module include render logic
                        if (count($pagesfolder)!=0)
                        {
                        	foreach($pagesfolder as $apage)
                                {
                                            include("./modules/".$apage);
                                }
                       }
		       ///// end of extra pages render content






			?>
			<footer>

			<?php
                        //ie8 hack
                        print '<!--[if lt IE 9]>';
                        print '<table style="width:100%"><tr><td style="text-align: left; color:#999; font-size:.9em;">';
                        print '<![endif]-->';
			?>
				<fl>Licensed under LGPL</fl>
			 <?php
                        //ie8 hack
                        print '<!--[if lt IE 9]>';
                        print '</td><td style="text-align: right; color:#999; font-size:.6em;">';
                        print '<![endif]-->';
                        ?>

				<fr>Powered by Etheria Software</fr>
			 <?php
                        //ie8 hack
                        print '<!--[if lt IE 9]>';
                        print '</td></tr></table>';
                        print '<![endif]-->';
                        ?>

				<br />  <?php //print "<pre>"; print_R($_SESSION); print "</pre>"; ?>

			</footer>
		</div>
		<script>
		    if(Meny && Meny.create){
			var meny = Meny.create({
				menuElement: document.querySelector( '.menu' ),
				contentsElement: document.querySelector( '.container' ),
				position: 'top',
				height: 50
			});
		     }
		</script>

	<div id="howtoclose">
	To close this interface press Ctrl+W
	</div>
	<div id="devver">
	Development version
	</div>

	</body>
</html>
<?php
session_write_close();
?>
