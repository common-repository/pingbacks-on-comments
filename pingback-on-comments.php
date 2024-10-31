<?php
/*
Plugin Name: Pingbacks on Comments
Plugin URI: http://twiogle.com/blog/pingbacks-on-comments/
Description: Performs pingbacks on the links within comments
Version: 1.0
Author: Benjamin Adams
Author URI: http://twiogle.com
*/



add_action('admin_menu', 'add_comment_pingback');
register_activation_hook(__FILE__,'pingback_commenter_install');


function add_comment_pingback()
{
add_options_page('Pingbacks on Comments', 'Pingbacks on Comments', 8, 'pingbackcomments', 'pingback_comment_options_page');

}

register_activation_hook(__FILE__, 'pingback_comments_activation');
add_action('find_and_post_pingback_comments', 'doComments');

function pingback_comments_activation() {
	wp_schedule_event(time(), 'hourly', 'find_and_post_pingback_comments');
}



register_deactivation_hook(__FILE__, 'pingback_comments_deactivation');
function pingback_comments_deactivation() {
	wp_clear_scheduled_hook('find_and_post_pingback_comments');
}


function pingback_comment_options_page()
{
global $wpdb;
//testing wordpress querys





    // variables for the field and option names 
    $opt_name = 'mt_posts_per_page';
    $hidden_field_name = 'mt_submit_hidden';
    $data_field_name = 'mt_posts_per_page';




    // Read in existing option value from database
    $maxComments = get_option( $opt_name );
  

    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if( $_POST[ $hidden_field_name ] == 'Y' ) {
        // Read their posted value
        $maxComments = $_POST[ $data_field_name ];







        // Save the posted value in the database
        update_option( $opt_name, $maxComments );


        // Put an options updated message on the screen

?>
<div class="updated"><p><strong><?php _e('Options saved.', 'mt_trans_domain' ); ?></strong></p></div>
<?php

    }

    // Now display the options editing screen

    echo '<div class="wrap">';

    // header

    echo "<h2>" . __( 'Pingbacks on Comments', 'mt_trans_domain' ) . "</h2>";
echo "- Goes through each comment on your blog and checks if the links on them can have a <a href='http://en.wikipedia.org/wiki/Pingback'>pingback</a> performed on them.<br />
- Runs every hour while the plugin is active.<br />
- Webmasters usually accept the pingback if the comments are legit<br />
<br />";

    ?>

<form name="form1" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
<hr />
<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">

<p><?php _e("Run x pingback request per hour:", 'mt_trans_domain' ); ?> 
<input type="text" name="<?php echo $data_field_name; ?>" value="<?php echo $maxComments; ?>" size="10">Default is 50.  Be careful not to set this too high, akismet could label you as a spammer.
</p>






<?php



//echo "testing= $maxComments2";

?>


<p class="submit">
<input type="submit" name="Submit" value="<?php _e('Update Options', 'mt_trans_domain' ) ?>" />
</p>

</form>
</div>
<p>
You can view the next time the pingback comments will run with <a target='_blank' href='http://wordpress.org/extend/plugins/cron-view/'>this</a> plugin
</p>
<p>

This plugin will check for <?php echo $maxComments; ?> comments every hour and perform pings backs on them. <br /> Or you can manually run it here:
</p>
<?php
echo "<form method='POST' action=''>";
echo "<input type='hidden' name='runOnce' value='1' />";
echo "<input type='submit' name='submit' value='Perform pingbacks on comments now' />";

if($_POST['runOnce']=="1")
{
doComments();
}

$foundLinks = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."successfulPingbacks order by timePinged desc");


$linksFound=0;
foreach ($foundLinks as $alink) {

if($linksFound==0)
{
echo "<br /><br /><br /><br />Successful pingback on these pages<br />";
echo "<table><th>Domain Pinged</th><th>Pingback performed</th>";
}

    $linkURL=$alink->URL;
	$timeFound=$alink->timePinged;
	$domain= getDomain($linkURL);

	
	
	echo "<tr><td><a target='_blank' title='$linkURL' href='$linkURL'>$domain</a></td><td>$timeFound</td></tr>";
$linksFound++;
}

if($linksFound >0)
{
//close the table
echo "</table>";

}

?>

<p>Please donate if this helped you get more backlinks.</p>

<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="11255361">
<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>


<?php
 
}

function getDomain ($url) { 
$parsed = parse_url($url); 
$hostname = $parsed['host']; 
return $hostname; 
} 


function doComments()
{
global $wpdb;

//so we can make the scripts run longer
$mem = abs(intval(@ini_get('memory_limit')));
if($mem && $mem < 64) {
@ini_set('memory_limit', '64M');
}

$mem = abs(intval(@ini_get('memory_limit')));
if($mem && $mem < 65) {
@ini_set('memory_limit', '128M');
}

$mem = abs(intval(@ini_get('memory_limit')));
if($mem && $mem < 129) {
@ini_set('memory_limit', '256M');
}
		
//$time = abs(intval(@ini_get("max_execution_time")));
//if($time != 0 && $time < 120) {
//@set_time_limit(120);
//}
@set_time_limit(0);
@ini_set('max_execution_time', '0');


error_reporting(0);

$maxComments = get_option( 'mt_posts_per_page' );

if($maxComments== null || $maxComments== "" || $maxComments== 0)
{
$maxComments=50;
}



$count=0;


$allposts = $wpdb->get_results("SELECT comment_content,comment_post_ID,comment_ID FROM $wpdb->comments where comment_ID not in (select comment_ID from ".$wpdb->prefix ."alreadyPinged) and comment_content LIKE '%http%' group by comment_post_ID order by comment_ID desc limit $maxComments");



foreach ($allposts as $apost) {

    $text=$apost->comment_content;
    $postID=$apost->comment_post_ID; 
	$cmntId=$apost->comment_ID; 
	//$authURL=$apost->comment_author_url;
	
	
	$wpdb->show_errors();
	
  //mysql_query("insert into alreadyPinged (comment_ID) VALUES ('$postID')");
$wpdb->insert( $wpdb->prefix .'alreadyPinged', array( 'comment_ID' => "$cmntId" ) );

	
	$permalink = get_permalink( $postID );
	
  //$link="http://drija.com/?p=$postID#comments";

  //we can't add the #comments anchor because it causes it to crash
  
  echo "<br /><font color='green'>looking for pingbacks to perform in post: $permalink</font><br />";
  
  send_pingback($text, $permalink);
  
$count++;
	
}

if($count==0)
{
echo "<br />Could not find any comments that contained links in them<br />";
}else
{
  echo "<br />done with pingbacks....for now";
}


}



$pingback_commenter_db_version = "1.1";

function pingback_commenter_install() {
   global $wpdb;
   global $pingback_commenter_db_version;

   $table_name = $wpdb->prefix . "successfulPingbacks";
   if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
      
      
	  


$sql="CREATE TABLE `". $wpdb->prefix ."successfulPingbacks` (
  `URL` varchar(233) NOT NULL,
  `timePinged` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`URL`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);

$sql="CREATE TABLE `". $wpdb->prefix ."alreadyPinged` (
  `comment_ID` int(200) NOT NULL,
  PRIMARY KEY (`comment_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
 dbDelta($sql);
 
      add_option("pingback_commenter_db_version", $pingback_commenter_db_version);

   }
}



function do_send_pingback($myarticle, $url, $pdebug = 0) {

        $parts = parse_url($url);

        if (!isset($parts['scheme'])) {
                echo  "<font color='red'>do_send_pingback: failed to get url scheme [".$url."]</font><br />\n";
                return(1);
                }
        if ($parts['scheme'] != 'http') {
                echo  "<font color='red'>do_send_pingback: url scheme is not http [".$url."]</font><br />\n";
                return(1);
                }
        if (!isset($parts['host'])) {
                echo  "<font color='red'>do_send_pingback: could not get host [".$url."]</font><br />\n";               
                return(1);
                }
        $host = $parts['host'];
        $port = 80;
        if (isset($parts['port'])) $port = $parts['port'];
        $path = "/";
        if (isset($parts['path'])) $path = $parts['path'];
        if (isset($parts['query'])) $path .="?".$parts['query'];
        if (isset($parts['fragment'])) $path .="#".$parts['fragment'];

        $fp = fsockopen($host, $port);
        fwrite($fp, "GET $path HTTP/1.0\r\nHost: $host\r\n\r\n");
        $response = "";
        while (is_resource($fp) && $fp && (!feof($fp))) {
                $response .= fread($fp, 1024);
                }
        fclose($fp);
        $lines = explode("\r\n", $response);
        foreach ($lines as $line) {
                if (ereg("X-Pingback: ", $line)) {
                        list($pburl) = sscanf($line, "X-Pingback: %s");
                        echo  "<br /><font color='green'>found a pingback url=$pburl, trying to post pingback now.</font><br />\n";
                        }
                }

        if (empty($pburl)) {
                echo  "<font color='red'>[$url] is not a page that accepts pingbacks.</font><br />\n";
                return(1);
                }
        if (!isset($parts['scheme'])) {
                echo  "<font color='red'>do_send_pingback: failed to get pingback url scheme [".$pburl."]</font><br />\n";
                return(1);
                }
        if ($parts['scheme'] != 'http') {
                echo  "<font color='red'>do_send_pingback: pingback url scheme is not http[".$pburl."]</font><br />\n";
                return(1);
                }
        if (!isset($parts['host'])) {
                echo  "<font color='red'>do_send_pingback: could not get pingback host [".$pburl."]</font><br />\n";
                return(1);
                }
				
				echo "<br /><font color='green'>attempting to post a pingback to <a target='_blank' href='$url'>$url</a></font><br />";
				
newSendPingback($myarticle,$url,$pburl);
				
				
				
				
        }

//checks if it is a valid url		
function valid_url($url) {
if (!ereg('[()"\'<>]', $url)) 
{

return(1);
}
return(0);
}

 function newSendPingback($mypost,$theirpost,$pingbackLink)
 {
   global $wpdb;
  require_once "xmlrpc.inc";
  
  $m = new xmlrpcmsg("pingback.ping", array(new xmlrpcval($mypost, "string"), new xmlrpcval($theirpost, "string")));
  $c = new xmlrpc_client($pingbackLink);
  $c->setRequestCompression(null);
  $c->setAcceptedCompression(null);
  //$c->setDebug(2);
  $r = $c->send($m);
  
  if (!$r->faultCode()) {
   echo "<br /><font color='blue'>Pingback to <a target='_blank' href='$theirpost'>$theirpost</a> succeeded. (usually depends if the blog owner accepts/rejects the pingback)</font><br />";
   
 //    mysql_query("insert into successfulPingbacks (URL) VALUES ('$theirpost')");
	 
	 	$wpdb->show_errors();
	 
	 $wpdb->insert( $wpdb->prefix .'successfulPingbacks', array( 'URL' => "$theirpost" ) );
	 

	 
   
  } else {
   $err = "code " . $r->faultCode() . " message " . $r->faultString();
   echo "<br /><font color='red'>Pingback to <a target='_blank' href='$theirpost'>$theirpost</a> <b>failed with error $err.</b><font size=-2>(these error msgs are not always accurate)</font></font><br /><br />";
  }
  
  
 }


		
# call send_pingback() from your blog after adding a new post,
# $text will be the full text of your post
# $myurl will be the full url of your posting
function send_pingback($text, $myurl,$authURL) {
        $m = array();
        preg_match_all ("/<a[^>]*href=[\"']([^\"']*)[\"'][^>]*>(.*?)<\/a>/i", $text, $m);
        $c = count($m[0]);
        for ($i = 0; $i < $c; $i++) {
                $ret = valid_url($m[1][$i]);
                if ($ret) do_send_pingback($myurl, $m[1][$i]);
                }
				
				//echo "NOW SENDING AUTHOR URL";
				//do_send_pingback($myurl,$authURL);
				
        }


?>
