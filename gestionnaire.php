<?php
/***********************************************************************

  Copyright (C) 2002-2005  Rickard Andersson (rickard@punbb.org)

  This file is part of PunBB.

  PunBB is free software; you can redistribute it and/or modify it
  under the terms of the GNU General Public License as published
  by the Free Software Foundation; either version 2 of the License,
  or (at your option) any later version.

  PunBB is distributed in the hope that it will be useful, but
  WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston,
  MA  02111-1307  USA

************************************************************************/

define('PUN_ROOT', './');
define('PLUGIN_URL', 'gestionnaire.php');
require PUN_ROOT.'include/common.php';
$boucle_id = 1;
$tabindex = 0;	// Numéro des champs

if ($pun_user['g_read_board'] == '0' OR $pun_user['is_guest'])
	message($lang_common['No view']);

// Load language file
if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/uploadile.php'))
	require PUN_ROOT.'lang/'.$pun_user['language'].'/uploadile.php';
else
	require PUN_ROOT.'lang/English/uploadile.php';

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']),$lang_uploadile['popup_title']);
define('PUN_ALLOW_INDEX', 1);
// --- Header --- //
// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

// Send no-cache headers
header('Expires: Thu, 21 Jul 1977 07:30:00 GMT');	// When yours truly first set eyes on this world! :)
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');		// For HTTP/1.0 compability

// Load the template
$tpl_main = file_get_contents(PUN_ROOT.'include/template/main.tpl');

// START SUBST - <pun_include "*">
while (preg_match('#<pun_include "([^/\\\\]*?)\.(php[45]?|inc|html?|txt)">#', $tpl_main, $cur_include))
{
	if (!file_exists(PUN_ROOT.'include/user/'.$cur_include[1].'.'.$cur_include[2]))
		error('Unable to process user include '.htmlspecialchars($cur_include[0]).' from template main.tpl. There is no such file in folder /include/user/');

	ob_start();
	include PUN_ROOT.'include/user/'.$cur_include[1].'.'.$cur_include[2];
	$tpl_temp = ob_get_contents();
	$tpl_main = str_replace($cur_include[0], $tpl_temp, $tpl_main);
	ob_end_clean();
}
// END SUBST - <pun_include "*">

// START SUBST - <pun_head>
ob_start();

// Is this a page that we want search index spiders to index?
if (!defined('PUN_ALLOW_INDEX'))
	echo '<meta name="ROBOTS" content="NOINDEX, FOLLOW" />'."\n";

?>
<title><?php echo generate_page_title($page_title) ?></title>
<link rel="stylesheet" type="text/css" href="style/<?php echo $pun_user['style'].'.css' ?>" />
<style type="text/css">
/* <![CDATA[ */
#brdtitle {
	background: none;
	height: 100%;
}
/* ]]> */
</style>
<script type="text/javascript">
/* <![CDATA[ */
function FermerPopUp() 
{
	window.close();
}

function image(url, mini_url)
{
	url = '<?php echo $pun_config['o_base_url'].'/' ?>' + url;
	if ( (new String(mini_url)).length > 0)
		mini_url = '<?php echo $pun_config['o_base_url'].'/' ?>' + mini_url;
	var input = window.opener.document.getElementById('req_message');
	input.focus();

	if(typeof document.selection != 'undefined')/* --- Pour IE --- */
	{
		var range = document.selection.createRange();
		var insText = range.text;
		if(mini_url == url)
		{
			input.value += insText + '[img]' + url + '[/img]';
			if (url.length == 0)
			{
				range.move('character', -6);
			}
		}
		else if(mini_url != '' && mini_url != url)
		{
			input.value += insText + '[url=' + url + '][img]' + mini_url + '[/img][/url]';
			if (mini_url.length == 0 && url.length == 0)
			{
				range.move('character', -18);
			}
			else if (mini_url.length == 0 && url.length != 0)
			{
				range.move('character', -18  + url.length);
			}
			else if (mini_url.length != 0 && url.length == 0)
			{
				range.move('character', -17);
			}
		}
		else
		{
			input.value += insText + '[url=' + url + ']<?php echo $lang_uploadile['texte']; ?>[/url]';
			if (url.length == 0)
			{
				range.movestart('character', 5);
			}
			else
			{
				range.movestart('character', 5 + url.length + 1);
			}
		}
		range.select();
	}			
	else if(typeof input.selectionStart != 'undefined') /* --- Navigateurs récents (FF) --- */
	{
		var start = input.selectionStart;
		var end = input.selectionEnd;
		var selText = input.value.substring(start, end);
		var pos;
		
		if(mini_url == url)
		{		
			input.value = input.value.substr(0, start) + selText + '[img]' + url + '[/img]' + input.value.substr(end);
			if (url.length == 0)
			{
				pos = start + 5;
			}
			else
			{
				pos = start + 5 + url.length + 6;
			}
		}
		else if(mini_url != '' && mini_url != url)
		{
			input.value = input.value.substr(0, start) + selText + '[url=' + url + '][img]' + mini_url + '[/img][/url]' + input.value.substr(end);
			if (mini_url.length == 0 && url.length == 0)
			{
				pos = start + 5;
			}
			else if (mini_url.length == 0 && url.length != 0)
			{
				pos = start + 5;
			}
			else if (mini_url.length != 0 && url.length == 0)
			{
				pos = start + 5 + mini_url.length + 6;
			}
			else
			{
				pos = start + 5 + mini_url.length + 6 + url.length + 12;
			}
		}
		else
		{
			input.value = input.value.substr(0, start) + selText + '[url=' + url + ']<?php echo $lang_uploadile['texte']; ?>[/url]' + input.value.substr(end);
			if (url.length == 0)
			{
				pos = start + 5;
			}
			else
			{
				pos = start + 5 + url.length + 1;
			}
		}

		input.selectionStart = pos;
		input.selectionEnd = pos;
	}
	else /* --- Autres navigateurs --- */
	{
		var pos;
		var re = new RegExp('^[0-9]{0,3}$');
		while(!re.test(pos))
		{
			pos = prompt("insertion (0.." + input.value.length + "):", "0");
		}
		if(pos > input.value.length)
		{
			pos = input.value.length;
		}
		var insText = prompt("<?php echo $lang_uploadile['texte']; ?>");
		input.value = input.value.substr(0, pos) + insText + '[img]' + url + '[/img]' + input.value.substr(pos);
	}
}
/* ]]> */
</script>
<?php

$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
if (strpos($user_agent, 'msie') !== false && strpos($user_agent, 'windows') !== false && strpos($user_agent, 'opera') === false)
	echo '<script type="text/javascript" src="style/imports/minmax.js"></script>';

$tpl_temp = trim(ob_get_contents());
$tpl_main = str_replace('<pun_head>', $tpl_temp, $tpl_main);
ob_end_clean();

// END SUBST - <pun_head>

// START SUBST - <body>
if (isset($focus_element))
{
	$tpl_main = str_replace('<body onload="', '<body onload="document.getElementById(\''.$focus_element[0].'\').'.$focus_element[1].'.focus();', $tpl_main);
	$tpl_main = str_replace('<body>', '<body onload="document.getElementById(\''.$focus_element[0].'\').'.$focus_element[1].'.focus()">', $tpl_main);
}
// END SUBST - <body>

// START SUBST - <pun_language>
$tpl_main = str_replace('<pun_language>', $lang_common['lang_identifier'], $tpl_main);
// END SUBST - <pun_language>

// START SUBST - <pun_page>
$tpl_main = str_replace('<pun_page>', htmlspecialchars(basename($_SERVER['PHP_SELF'], '.php')), $tpl_main);
// END SUBST - <pun_title>

// START SUBST - <pun_content_direction>
$tpl_main = str_replace('<pun_content_direction>', $lang_common['lang_direction'], $tpl_main);
// END SUBST - <pun_content_direction>

// START SUBST - <pun_title>
$tpl_main = str_replace('<pun_title>', '', $tpl_main);
// END SUBST - <pun_title>

// START SUBST - <pun_desc>
$tpl_main = str_replace('<pun_desc>', '<p><span><a href="javascript:FermerPopUp()">'.$lang_uploadile['close'].'</a></span></p>', $tpl_main);
// END SUBST - <pun_desc>

// START SUBST - <pun_status>
$tpl_main = str_replace('<pun_status>', '', $tpl_main);
// END SUBST - <pun_status>

// START SUBST - <pun_announcement>
$tpl_main = str_replace('<pun_announcement>', '', $tpl_main);
// END SUBST - <pun_announcement>

// START SUBST - <pun_navlinks>
$tpl_main = str_replace('<pun_navlinks>', '', $tpl_main);
// END SUBST - <pun_navlinks>

// START SUBST - <pun_main>
ob_start();

define('PUN_HEADER', 1);
require PUN_ROOT.'include/parser.php';

// #######################################################################################################
function parse_file($file_name) 
{
	$file_name = preg_replace('![_@=\' ]!i', '-', $file_name);
	$file_name = preg_replace('![&\$ \?\!\.,;:\*\+\/\\\^\(\)%"~\[\]\{\}]!i', '', $file_name);
	$file_name = preg_replace('!([àâä])!i', 'a', $file_name);
	$file_name = preg_replace('!([éèêë])!i', 'e', $file_name);
	$file_name = preg_replace('!([îï])!i', 'i', $file_name);
	$file_name = preg_replace('!([ôö])!i', 'o', $file_name);
	$file_name = preg_replace('!([ùüû])!i', 'u', $file_name);
	$file_name = preg_replace('!ÿ!i', 'y', $file_name);
	$file_name = preg_replace('!ç!i', 'c', $file_name);
	return $file_name;
}

// Calculs espace alloué et espace actuellement utilisé et taille maximale uploadable
$retour = $db->query('SELECT group_id,upload FROM '.$db->prefix.'users WHERE id='.$pun_user['id']) or error('Impossible de retrouver les informations utilisateur', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($retour))
	message($lang_uploadile_common['Bad request']);
$user_plugile = $db->fetch_assoc($retour);
if($user_plugile['group_id'] == '1')
{
	$limit = '100000000000';
	$maxsize = '100000000000';
}
elseif($user_plugile['group_id'] == '2')
{
	$limit = $pun_config['o_uploadile_limit_modo'];
	$maxsize = $pun_config['o_uploadile_maxsize_modo'];
}
else
{
	$limit = $pun_config['o_uploadile_limit_member'];
	$maxsize = $pun_config['o_uploadile_maxsize_member'];
}
$pourcentage_utilise = ceil(($user_plugile['upload']*100)/$limit);

// Suppression des fichiers
if(isset($_POST['supprimer']) AND $_POST['supprimer'] != NULL)
{
	$dir = 'img/members/'.$pun_user['id'].'/';
	$erreur = 0;
	$upload = 0;
	if(is_dir($dir)) // On vérifie que la valeur est un fichier (pour écarter les sous dossiers)
	{
		// On supprime les images
		for($u = 1 ; $u <= $_POST['boucle_id'] ; $u++)
		{
			if(isset($_POST['supprimer_'.$u]) AND is_file($dir.$_POST['supprimer_'.$u]))
			{
				$delete = unlink($dir.$_POST['supprimer_'.$u]);
				if($pun_config['o_uploadile_thumb'] == '1')
					@unlink($dir.'mini_'.$_POST['supprimer_'.$u]);
				if($delete == false)
					$erreur++;
			}
		}

		// On cherche la taille du dossier du membre 
		$open = opendir($dir); // On ouvre le répertoire
		while(false !== ($file = readdir($open))) // Tant qu'il y a des fichiers à lire
		{
			if(is_file($dir.$file)) // On vérifie que la valeur est un fichier (pour écarter les sous dossiers)
			{
				$extension = strtolower(substr(strrchr($file,  "." ), 1)); // On prend l'extension du fichier dans la variable $extension avec une sous-chaine
				$extsupport = explode(',', $pun_config['o_uploadile_laws'].','.strtoupper($pun_config['o_uploadile_laws'])); // La liste des extensions possibles pour une image
				if(in_array($extension, $extsupport) and ($file[0] != "#")) // Si l'extension ne figure pas dans la liste on passe le fichier (A noter: Pour cacher une image placez un "#" devant son nom)
					$files[] = $dir.$file; // Si elle y figure on ajoute le fichier à l'array $files
			}
		}
		closedir($open); // Et enfin on ferme le dossier
		if(isset($files))
		{
			foreach($files as $image)
				$upload = $upload + filesize($image);
		}
		$db->query('UPDATE '.$db->prefix.'users SET upload=\''.$upload.'\' WHERE id='.$pun_user['id']) or error(sprintf($lang_uploadile['err_insert'],$conf_name), __FILE__, __LINE__, $db->error());
	}

		$url = PLUGIN_URL;
	if($erreur == 1)
		redirect($url, $lang_uploadile['delete_success']);
	else
		redirect($url, $lang_uploadile['err_delete']);
}

elseif(isset($_FILES['fichier']) AND $_FILES['fichier'] != NULL AND $_FILES['fichier']['error'] == 0)
{
	// On vérifie les extensions
	$extension_multiple = explode('.', $_FILES['fichier']['name']); 
	if(count($extension_multiple) == '2') // Pour empêcher les extensions du type exemple.php.jpg
	{
		$extensions_valides = explode(',', $pun_config['o_uploadile_laws'].','.strtoupper($pun_config['o_uploadile_laws']));
		$extension_upload = substr(strrchr($_FILES['fichier']['name'], '.'), 1);
		if(in_array($extension_upload,$extensions_valides))
		{
			// On vérifie la taille maximale
			if($_FILES['fichier']['size'] <= $maxsize)
			{
				// On vérifie l'espace alloué
				if($_FILES['fichier']['size']+$user_plugile['upload'] <= $limit)
				{
					$upload = $user_plugile['upload']+$_FILES['fichier']['size'];
					$fichier = explode('.', $_FILES['fichier']['name']);
					$fichier = parse_file($fichier[0]).'.'.$fichier[1];
					$fichier_name_temp = $_FILES['fichier']['tmp_name'];
					$dir = 'img/members/'.$pun_user['id'].'/';
					if(is_file($dir.$fichier))
						$fichier = date('dmY\-Hi', time()).'_'.$fichier;

					if(!is_dir('img/members/'))
						mkdir('img/members', 0755);
					if(!is_dir($dir))
						mkdir('img/members/'.$pun_user['id'], 0755);
					move_uploaded_file($fichier_name_temp,$dir.$fichier);

					// Miniaturisation des images si demandées
					if($pun_config['o_uploadile_thumb'] == '1') //  On vérifie que la miniaturisation est activée.
					{
						$hauteur_destination = $pun_config['o_uploadile_thumb_size'];
						$type = $_FILES['fichier']['type'];
						switch ($type)
						{
							case 'image/pjpeg':
							case 'image/jpeg':
								$image = imagecreatefromjpeg($dir.$fichier);
								$type = 'image/jpeg';
								break;
							case 'image/x-png':
							case 'image/png':
								$image = imagecreatefrompng($dir.$fichier);
								$type = 'image/png';
								break;
							case 'image/gif':
								$image = imagecreatefromgif($dir.$fichier);
								break;
						}
						if($type == 'image/png' OR $type == 'image/jpeg' OR $type == 'image/gif')
						{
							$largeur = imagesx($image);
							$hauteur = imagesy($image);
							if($hauteur >= $hauteur_destination)
							{
								$pourcentage = $hauteur_destination/$hauteur;
								$largeur_destination = $largeur*$pourcentage;
								$destination = imagecreatetruecolor($largeur_destination, $hauteur_destination);
								imagecopyresampled($destination, $image, 0, 0, 0, 0, $largeur_destination, $hauteur_destination, $largeur, $hauteur);
								imagepng($destination, $dir.'mini_'.$fichier);
							}
							else
								imagepng($image, $dir.'mini_'.$fichier);
							if(isset($image))
								imagedestroy($image);
							if(isset($destination))
								imagedestroy($destination);
						}
					}
					$db->query('UPDATE '.$db->prefix.'users SET upload=\''.$upload.'\' WHERE id='.$pun_user['id']) or error(sprintf($lang_uploadile['err_insert'],$conf_name), __FILE__, __LINE__, $db->error());
					redirect(PLUGIN_URL, $lang_uploadile['modif_success']);
				}
				else
					redirect(PLUGIN_URL, $lang_uploadile['err_espace']);
			}
			else
				redirect(PLUGIN_URL, $lang_uploadile['err_size']);
		}
		else
			redirect(PLUGIN_URL, $lang_uploadile['err_extension']);
	}
	else
	{
		if(count($extension_multiple) == '0')
			redirect(PLUGIN_URL, $lang_uploadile['err_noExtension']);
		else
			redirect(PLUGIN_URL, $lang_uploadile['err_extension_multiple']);
	}
}

// S'il y a une erreur
elseif(isset($_FILES['fichier']) AND $_FILES['fichier'] != NULL AND !isset($_POST['formerFichierName']) AND $_FILES['fichier']['error'] != 0)
{
	if($_FILES['fichier']['error'] == '1')
		$s_erreur = $_FILES['fichier']['name'].': '.$lang_uploadile['err_size'].' ( '.get_cfg_var('upload_max_filesize').' )';
	if($_FILES['fichier']['error'] == '2')
		$s_erreur = $lang_uploadile['err_size'];
	if($_FILES['fichier']['error'] == '3')
		$s_erreur = $lang_uploadile['err_4'];
	if($_FILES['fichier']['error'] == '4')
		$s_erreur = $lang_uploadile['err_1'];
	else
		$s_erreur = $lang_uploadile['err_4'];
	redirect(PLUGIN_URL, $s_erreur);
}

// #######################################################################################################

?>
	<div id="uploadile" class="blockform">
		<h2 class="block2"><span><?php echo $lang_uploadile['titre_2']; ?></span></h2>
		<div class="box">
			<form method="post" action="gestionnaire.php" enctype="multipart/form-data">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_uploadile['legend']; ?></legend>
						<div class="infldset">
							<label for="fichier"><?php echo $lang_uploadile['fichier']; ?></label> <input type="file" id="fichier" name="fichier" tabindex="<?php echo $tabindex++; ?>" />
							<p>
								<?php
								if($pun_user['g_id'] == '1')
									printf($lang_uploadile['info_2_admi'],str_replace(',', ', ', $pun_config['o_uploadile_laws']));
								else
									printf($lang_uploadile['info_2'],file_size($maxsize),str_replace(',', ', ', $pun_config['o_uploadile_laws']));
								?>
							</p>
							<p><input type="submit" name="submit" value="<?php echo $lang_uploadile['submit']; ?>" tabindex="<?php echo $tabindex++; ?>" /></p>
						</div>
					</fieldset>
				</div>
			</form>
		</div>
		<h2 class="block2"><span><?php echo $lang_uploadile['titre_4']; ?></span></h2>
		<div class="box">
			<form method="post" action="gestionnaire.php" enctype="multipart/form-data">
				<div class="inform">
					<?php 
					if($pun_user['g_id'] != '1')
						printf($lang_uploadile['info_4'], $pourcentage_utilise, '%', $pourcentage_utilise, '%', file_size($user_plugile['upload']),file_size($limit), $pun_user['id']);
					?>
					<div class="infldset" style="height:385px; overflow: auto; padding: 0;">
						<table>
							<thead>
								<tr>
									<th scope="row"><?php echo $lang_uploadile['th']; ?></th>
									<th scope="row"><?php echo $lang_uploadile['th2']; ?></th>
									<th><input type="submit" value="<?php echo $lang_uploadile['delete']; ?>" name="supprimer" tabindex="<?php echo $tabindex++; ?>" /></th>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<th class="tc1" scope="row"><?php echo $lang_uploadile['th']; ?></th>
									<th class="tc1" scope="row"><?php echo $lang_uploadile['th2']; ?></th>
									<th><input type="submit" value="<?php echo $lang_uploadile['delete']; ?>" name="supprimer" tabindex="<?php echo $tabindex++; ?>" /></th>
								</tr>
							</tfoot>
							<tbody>
							<?php
							$dir = 'img/members/'.$pun_user['id'].'/';
							if(is_dir($dir)) // On vérifie que la valeur est un fichier (pour écarter les sous dossiers)
							{
								$open = opendir($dir); // On ouvre le répertoire
								while(false !== ($file = readdir($open))) // Tant qu'il y a des fichiers à lire
								{
									if(is_file($dir.$file)) // On vérifie que la valeur est un fichier (pour écarter les sous dossiers)
									{
											$extension = strtolower(substr(strrchr($file,  "." ), 1)); // On prend l'extension du fichier dans la variable $extension avec une sous-chaine
											$extsupport = explode(',', $pun_config['o_uploadile_laws'].','.strtoupper($pun_config['o_uploadile_laws'])); // La liste des extensions possibles pour une image
											if(in_array($extension, $extsupport) and ($file[0] != "#")) // Si l'extension ne figure pas dans la liste on passe le fichier (A noter: Pour cacher une image placez un "#" devant son nom)
											{
												$time = filemtime($dir.$file).$file;
												$filesvar[$time] = $dir.$file; // Si elle y figure on ajoute le fichier à l'array $files
											}
									}
								}
								closedir($open); // Et enfin on ferme le dossier
								if(isset($filesvar))
								{
									krsort($filesvar);
									foreach($filesvar as $time => $file)
									{
										$files[] = $file;
									}
								}
								if(isset($files))
								{
									
									foreach($files as $fichier)
									{
										$nom_fichier_brut = preg_replace('`img/members/'.$pun_user['id'].'/(.+)`','$1',$fichier); // Nom du fichier avec extension
										$nom_fichier = preg_replace('`(.+)\..*`', '$1', $nom_fichier_brut); // Nom du fichier sans extension
										$type_fichier = preg_replace('`.*\.(.+)`', '$1', $nom_fichier_brut); // Extension du fichier
										$size_fichier = file_size(filesize($fichier)); // Taille du fichier
										$miniature = $dir.'mini_'.$nom_fichier.'.'.$type_fichier; // Adresse de la miniature potentiellement existente
										$mini = explode('_', $nom_fichier); // On vérifie que ce n'est pas une miniature
										if($mini[0] != 'mini')
										{
											?>
											<tr>
												<td class="tc1">
													<input type="text" size="20" tabindex="<?php echo $tabindex++; ?>" value="<?php echo $pun_config['o_base_url'].'/'.$fichier; ?>" />
													<input type="button" value="<?php echo $lang_uploadile['insert']; ?>" onclick="javascript:image(<?php echo '\''.$fichier.'\', \''.(is_file($miniature) ? $fichier : '').'\''; ?>);return(false)" />
													<?php 
													if(is_file($miniature))
													{
													?>
														<br />
														<input type="text" size="20" tabindex="<?php echo $tabindex++; ?>" value="<?php echo $pun_config['o_base_url'].'/'.$miniature; ?>" />
														<input type="button" value="<?php echo $lang_uploadile['insert_thumbnail']; ?>" onclick="javascript:image('<?php echo $fichier; ?>','<?php echo $miniature; ?>');return(false)" />
													<?php 
													}
													?>
												</td>
												<?php
												// Affichage de la miniature
												if($pun_config['o_uploadile_thumb'] == '1' AND is_file($miniature))
													echo '<td class="tc2"><a href="'.$dir.$nom_fichier.'.'.$type_fichier.'" onclick="javascript:image(\''.$fichier.'\', \''.(is_file($miniature) ? $fichier : '').'\');return(false)" title="'.$nom_fichier.' - '.$size_fichier.'"><img src="'.$dir.'mini_'.$nom_fichier.'.'.$type_fichier.'" alt="'.$nom_fichier.'" /></a></td>';
												else
													echo '<td class="tc2">'.$lang_uploadile['no_preview'].'</td>';
												?>
												<td style="text-align:center;"><input type="checkbox" name="supprimer_<?php echo $boucle_id++; ?>" value="<?php echo $nom_fichier_brut; ?>" tabindex="<?php echo $tabindex++; ?>" /></td>
											</tr>
											<?php
										}
									}
								}
								else
									echo '<tr><td colspan="2">'.$lang_uploadile['err_1'].'</td></tr>';
							}
							else
								echo '<tr><td colspan="2">'.$lang_uploadile['err_2'].'</td></tr>';
							?>
							</tbody>
						</table>
						<input type="hidden" name="boucle_id" value="<?php echo $boucle_id; ?>" />
					</div>
				</div>
			</form>
		</div>
	</div>
<?php
require PUN_ROOT.'footer.php';
?>
