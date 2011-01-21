##
##
##           Mod title:  Uploadile
##
##         Mod version:  1.3
##    Release date 1.3:  17/10/2010
##    Release date 1.2:  27/12/2007
##    Release date 1.1:  09/05/2007
##    Release date 1.0:  09/04/2007
##
##              Author:  BN [http://la-bnbox.fr]
##
##         Description:  With this plugile, member can upload file in the Website
##                       via profile.php page. 
##                       (You must previously install Plugile)
##
##      Affected files:  edit.php, post.php, viewtopic.php
##
##          Affects DB:  Yes
##
##             To note:  Functions move_uploaded_file(), mkdir(), opendir() must 
##                       be enabled in your Website and this one must accept
##                       resizing pictures. (facultative)
##
##          DISCLAIMER:  Please note that "mods" are not officially supported by
##                       PunBB. Installation of this modification is done at your
##                       own risk. Backup your forum database and any and all
##                       applicable files before proceeding.
##


#
#---------[ 0. UPDATE v.1.0 or v.1.1.x ]-------------------------------------
#

You must upload again the files and make the following modifications.


#
#---------[ 1. UPLOAD FILES ]-------------------------------------
#

upload/gestionnaire.php in /
upload/plugiles/UPP_Mes_uploads.php in /plugiles/
upload/plugins/AP_Uploadile.php in /plugins/
upload/langue/LANG/uploadile.php in /lang/LANG/


#
#---------[ 2. OPEN ]-------------------------------------------------------
#

post.php
edit.php
viewtopic.php
and eventually other files in which user can wrote something. (like UP_Biographile.php
for exemple.)


#
#---------[ 3. FIND ]-------------------------------------------------------
#

require PUN_ROOT.'header.php';

?>

OR

require PUN_ROOT.'header.php';

$cur_index = 1;

?>


#
#---------[ 4. AFTER, ADD ]-------------------------------------------------------
#

<script language="javascript">
/* <![CDATA[ */
function PopUp(url, titre, largeur, hauteur, options) 
{
	var top=(screen.height-hauteur)/3;
	var left=(screen.width-largeur)/2;
	window.open(url, titre, "top="+top+", left="+left+", width="+largeur+", height="+hauteur+", "+options);
}
/* ]]> */
</script>


#
#---------[ 5. FIND ]-------------------------------------------------------
#

name="req_message"

N.B: If you have still installed Puntoolbar, skip points 5 and 6.


#
#---------[ 6. AFTER, ADD ]-------------------------------------------------------
#

 id="req_message"


#
#---------[ 7. FIND ]-------------------------------------------------------
#

						<label><textarea name="req_message" rows="7" cols="75" tabindex="1"></textarea></label>
						<ul class="bblinks">
							<li><a href="help.php#bbcode" onclick="window.open(this.href); return false;"><?php echo $lang_common['BBCode'] ?></a>: <?php echo ($pun_config['p_message_bbcode'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></li>
							<li><a href="help.php#img" onclick="window.open(this.href); return false;"><?php echo $lang_common['img tag'] ?></a>: <?php echo ($pun_config['p_message_img_tag'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></li>
							<li><a href="help.php#smilies" onclick="window.open(this.href); return false;"><?php echo $lang_common['Smilies'] ?></a>: <?php echo ($pun_config['o_smilies'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></li>


#
#---------[ 8. AFTER, ADD ]-------------------------------------------------------
#
<?php
// Load the uploadile.php language file
if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/uploadile.php'))
	require PUN_ROOT.'lang/'.$pun_user['language'].'/uploadile.php';
else
	require PUN_ROOT.'lang/English/uploadile.php';
?>
							<li><span><a href="javascript:PopUp('gestionnaire.php','gest','660','430','resizable=yes,location=no,menubar=no,status=no,scrollbars=yes')"><?php echo $lang_uploadile['gestionnaire']; ?></a></span></li>


#
#---------[ 9. INSTALL AND CONFIGURATION ]-------------------------------------------------------
#

Before use this mod, you must install it in the administration plugin.
And you can configurate here your options.
To change plugile name, you must modify UP_Mes_uploads.php by UP_New_name.php :) (and then you must modify
this name on gestionnaire.php)
