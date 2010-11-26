<?php
/***********************************************************************

  Copyright (C) 2007  BN (bnmaster@la-bnbox.info)

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

// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

// Tell admin_loader.php that this is indeed a plugin and that it is loaded
define('PUN_PLUGIN_LOADED', 1);
define('PLUGIN_VERSION', '1.3');
define('PLUGIN_NAME', 'Uploadile');
define('PLUGIN_URL', 'admin_loader.php?plugin='.$_GET['plugin']);
$tabindex = 1;
$boucle_id = 1;

/***********************************************************************\
 Languages definitions
\***********************************************************************/
// Load language file
if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/uploadile.php'))
       require PUN_ROOT.'lang/'.$pun_user['language'].'/uploadile.php';
else
       require PUN_ROOT.'lang/English/uploadile.php';

// Installation
if (isset($_POST['installation']))
{
	switch($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$sql = 'ALTER TABLE '.$db->prefix.'users '.
			'ADD upload INT(15) NOT NULL DEFAULT 0';
			break;
		
		case 'pgsql':
			$sql = 'ALTER TABLE '.$db->prefix.'users '.
			'ADD upload INT(15) NOT NULL DEFAULT 0';
			break;
		
		case 'sqlite':
			$sql = 'ALTER TABLE '.$db->prefix.'users '.
			'ADD upload INT(15) NOT NULL DEFAULT 0';
			break;
	}
	$db->query($sql) or error(sprintf($lang_uploadile['err_3'], $db->prefix.'users'), __FILE__, __LINE__, $db->error());

	// delete everything in the cache since we messed with some stuff
	$d = dir(PUN_ROOT.'cache');
	while (($entry = $d->read()) !== false)
	{
		if (substr($entry, strlen($entry)-4) == '.php')
			@unlink(PUN_ROOT.'cache/'.$entry);
	}
	$d->close();
	
	$delete_config = array('o_uploadile_laws', 'o_uploadile_thumb', 'o_uploadile_thumb_size', 'o_uploadile_limit_member', 'o_uploadile_limit_modo', 'o_uploadile_maxsize_member', 'o_uploadile_maxsize_modo');
		
	foreach($delete_config as $conf_name)
		$db->query('DELETE FROM '.$db->prefix.'config WHERE conf_name = \''.$conf_name.'\' LIMIT 1;') or error(sprintf($lang_uploadile_ptb_admin['err_delete'],$conf_name), __FILE__, __LINE__, $db->error());
	
	$setup_config = array(
		'o_uploadile_laws'				=> 'jpg,jpeg,png,gif,swf,odt,ods,doc,xls,ppt,odp,mp3,pdf',
		'o_uploadile_thumb'				=> 1,
		'o_uploadile_thumb_size'		=> 100,
		'o_uploadile_limit_member'		=> 2097152, // 2 MB
		'o_uploadile_limit_modo'		=> 5242880, // 5 MB
		'o_uploadile_maxsize_member'	=> 1258291, // 1.2 MB
		'o_uploadile_maxsize_modo'		=> 1258291 // 1.2 MB
		);
		
	while (list($conf_name, $conf_value) = @each($setup_config))
		$db->query('INSERT INTO '.$db->prefix."config (conf_name, conf_value) VALUES('$conf_name', '$conf_value')") or error(sprintf($lang_uploadile_ptb_admin['err_insert'],$conf_name), __FILE__, __LINE__, $db->error());
	
	// Regenerate the config cache
	require_once PUN_ROOT.'include/cache.php';
	generate_config_cache();

	redirect(PLUGIN_URL, $lang_uploadile['installation_success']);
	exit;
}
// MAJ
if(isset($_POST['update']))
{
	$delete_config = array('o_uploadile_laws', 'o_uploadile_thumb', 'o_uploadile_thumb_size', 'o_uploadile_limit_member', 'o_uploadile_limit_modo', 'o_uploadile_maxsize_member', 'o_uploadile_maxsize_modo');
	foreach($delete_config as $conf_name)
		$db->query('DELETE FROM '.$db->prefix.'config WHERE conf_name = \''.$conf_name.'\' LIMIT 1;') or error(sprintf($lang_uploadile_ptb_admin['err_delete'],$conf_name), __FILE__, __LINE__, $db->error());
	
	if(isset($_POST['laws']))
		$laws = addslashes(htmlentities(str_replace(' ', '', $_POST['laws'])));
	else
		$laws = 'jpg,jpeg,png,gif,swf,odt,ods,doc,xls,ppt,odp,mp3,pdf';
	if(isset($_POST['thumb']))
		$thumb = addslashes(htmlentities($_POST['thumb']));
	else
		$thumb = 1;
	if(isset($_POST['thumb_size']) AND $_POST['thumb_size'] > 0)
		$thumb_size = intval($_POST['thumb_size']);
	else
		$thumb_size = 100;
	if(isset($_POST['limit_member']) AND $_POST['limit_member'] > 0)
		$limit_member = intval($_POST['limit_member']);
	else
		$limit_member = 2097152;
	if(isset($_POST['limit_modo']) AND $_POST['limit_modo'] > 0)
		$limit_modo = intval($_POST['limit_modo']);
	else
		$limit_modo = 5242880;
	if(isset($_POST['maxsize_member']) AND $_POST['maxsize_member'] > 0)
		$maxsize_member = intval($_POST['maxsize_member']);
	else
		$maxsize_member = 1258291;
	if(isset($_POST['maxsize_modo']) AND $_POST['maxsize_modo'] > 0)
		$maxsize_modo = intval($_POST['maxsize_modo']);
	else
		$maxsize_modo = 1258291;
	$setup_config = array(
	'o_uploadile_laws'				=> $laws,
	'o_uploadile_thumb'				=> $thumb,
	'o_uploadile_thumb_size'		=> $thumb_size,
	'o_uploadile_limit_member'		=> $limit_member,
	'o_uploadile_limit_modo'		=> $limit_modo,
	'o_uploadile_maxsize_member'	=> $maxsize_member,
	'o_uploadile_maxsize_modo'		=> $maxsize_modo
	);
	while (list($conf_name, $conf_value) = @each($setup_config))
		$db->query('INSERT INTO '.$db->prefix."config (conf_name, conf_value) VALUES('$conf_name', '$conf_value')") or error(sprintf($lang_uploadile_ptb_admin['err_insert'],$conf_name), __FILE__, __LINE__, $db->error());
	
	// Regenerate the config cache
	require_once PUN_ROOT.'include/cache.php';
	generate_config_cache();

	redirect(PLUGIN_URL, $lang_uploadile['update_success']);
	exit;
}
// Désinstallation
if (isset($_POST['restore']))
{
	// Cache
	$delete_config = array('o_uploadile_laws', 'o_uploadile_thumb', 'o_uploadile_thumb_size', 'o_uploadile_limit_member', 'o_uploadile_limit_modo', 'o_uploadile_maxsize_member', 'o_uploadile_maxsize_modo');
	foreach($delete_config as $conf_name)
		$db->query('DELETE FROM '.$db->prefix.'config WHERE conf_name = \''.$conf_name.'\' LIMIT 1;') or error(sprintf($lang_uploadile_ptb_admin['err_delete'],$conf_name), __FILE__, __LINE__, $db->error());
	// Regenerate the config cache
	require_once PUN_ROOT.'include/cache.php';
	generate_config_cache();
	
	// BDD
	$errors = array();
	if (!$db->query('ALTER TABLE '.$db->prefix.'users DROP upload;'))
		$errors[] = sprintf($lang_uploadile['err_5'], $db->prefix.'users =&gt; upload');

	// delete everything in the cache since we messed with some stuff
	$d = dir(PUN_ROOT.'cache');
	while (($entry = $d->read()) !== false)
	{
		if (substr($entry, strlen($entry)-4) == '.php')
			@unlink(PUN_ROOT.'cache/'.$entry);
	}
	$d->close();	
	
	if (!empty($errors))
		error('<ul><li>'.implode('</li><li>',$errors).'</li></ul>', '', '');

	redirect(PLUGIN_URL, $lang_uploadile['restore_success']);
	exit;
}
// Suppression des fichiers
if(isset($_POST['supprimer']) AND $_POST['supprimer'] != NULL)
{
	$retour = $db->query('SELECT id,username FROM '.$db->prefix.'users ORDER BY id') or error('Impossible de retrouver les informations utilisateur', __FILE__, __LINE__, $db->error());
	while($donnees = $db->fetch_assoc($retour))
	{
		$dir = 'img/members/'.$donnees['id'].'/';
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
			$db->query('UPDATE '.$db->prefix.'users SET upload=\''.$upload.'\' WHERE id='.$donnees['id']) or error(sprintf($lang_uploadile['err_insert'],$conf_name), __FILE__, __LINE__, $db->error());
		}
	}
	
	$url = 'admin_loader.php?plugin=AP_Uploadile.php';
	if($erreur == '1')
		redirect($url, $lang_uploadile['delete_success']);
	else
		redirect($url, $lang_uploadile['err_delete']);
}

	// Display the admin navigation menu
	generate_admin_menu($plugin);
	?>
	<div id="uploadile" class="plugin blockform">
		<h2><span><?php echo PLUGIN_NAME; ?> v.<?php echo PLUGIN_VERSION; ?></span></h2>
		<div class="box">
			<div class="inbox">
				<p><?php echo $lang_uploadile['plugin_desc'] ?></p>
				<form action="<?php echo PLUGIN_URL; ?>" method="post">
					<p>
						<input type="submit" name="installation" value="<?php echo $lang_uploadile['installation'] ?>" />&nbsp;<?php echo $lang_uploadile['installation_info'] ?><br />
						<input type="submit" name="update" value="<?php echo $lang_uploadile['update'] ?>" />&nbsp;<?php echo $lang_uploadile['update_info'] ?><br />
						<input type="submit" name="restore" value="<?php echo $lang_uploadile['restore'] ?>" />&nbsp;<?php echo $lang_uploadile['restore_info'] ?><br /><br />
					</p>
				</form>
			</div>
		</div>
		<h2 class="block2"><span><?php echo $lang_uploadile['configuration'] ?></span></h2>
		<div class="box">
			<form method="post" action="<?php echo PLUGIN_URL; ?>">
				<p class="submitend"><input type="submit" name="update" value="<?php echo $lang_uploadile['update']; ?>" /></p>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_uploadile['legend_2'] ?></legend>
						<div class="infldset">
						<table cellspacing="0">
							<tr>
								<th scope="row"><label for="laws"><?php echo $lang_uploadile['laws'] ?></label></th>
								<td>
									<input type="text" name="laws"  id="laws" size="50" tabindex="<?php echo $tabindex++; ?>" value="<?php echo @$pun_config['o_uploadile_laws'] ?>" />
									<?php echo $lang_uploadile['laws_info'] ?>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="thumb"><?php echo $lang_uploadile['thumb'] ?></label></th>
								<td>
									<input type="radio" name="thumb" id="thumb" value="1"<?php if (@$pun_config['o_uploadile_thumb'] == '1') echo ' checked="checked"' ?> /> <strong><?php echo $lang_uploadile['oui'] ?></strong>
									&#160;&#160;&#160;
									<input type="radio" name="thumb" value="0"<?php if (@$pun_config['o_uploadile_thumb'] == '0') echo ' checked="checked"' ?> /> <strong><?php echo $lang_uploadile['non'] ?></strong>
									<?php echo $lang_uploadile['thumb_info'] ?>
									<br />
									<?php echo $lang_uploadile['thumb_size'] ?>
									<input type="text" name="thumb_size"  id="thumb_size" size="4" tabindex="<?php echo $tabindex++; ?>" value="<?php echo @$pun_config['o_uploadile_thumb_size'] ?>" />&nbsp;<?php echo $lang_uploadile['px']; ?>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="maxsize_member"><?php echo $lang_uploadile['maxsize_member'] ?></label></th>
								<td>
									<input type="text" name="maxsize_member" id="maxsize_member" size="15" tabindex="<?php echo $tabindex++; ?>" value="<?php echo @$pun_config['o_uploadile_maxsize_member'] ?>" />&nbsp;<?php echo $lang_uploadile['bytes']; ?>
									<?php echo $lang_uploadile['maxsize_info'] ?>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="limit_member"><?php echo $lang_uploadile['limit_member'] ?></label></th>
								<td>
									<input type="text" name="limit_member" id="limit_member" size="15" tabindex="<?php echo $tabindex++; ?>" value="<?php echo @$pun_config['o_uploadile_limit_member'] ?>" />&nbsp;<?php echo $lang_uploadile['bytes']; ?>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="maxsize_modo"><?php echo $lang_uploadile['maxsize_modo'] ?></label></th>
								<td>
									<input type="text" name="maxsize_modo" id="maxsize_modo" size="15" tabindex="<?php echo $tabindex++; ?>" value="<?php echo @$pun_config['o_uploadile_maxsize_modo'] ?>" />&nbsp;<?php echo $lang_uploadile['bytes']; ?>
									<?php echo $lang_uploadile['maxsize_info'] ?>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="limit_modo"><?php echo $lang_uploadile['limit_modo'] ?></label></th>
								<td>
									<input type="text" name="limit_modo" id="limit_modo" size="15" tabindex="<?php echo $tabindex++; ?>" value="<?php echo @$pun_config['o_uploadile_limit_modo'] ?>" />&nbsp;<?php echo $lang_uploadile['bytes']; ?>
								</td>
							</tr>
						</table>
						</div>
					</fieldset>
					<p class="submitend"><input type="submit" name="update" value="<?php echo $lang_uploadile['update']; ?>" /></p>
					<div class="inform">
					<fieldset>
						<legend><?php echo $lang_uploadile['legend_1'] ?></legend>
						<label for="mo"><?php echo $lang_uploadile['mo'] ?></label> <input type="text" name="mo" id="mo" size="15" tabindex="<?php echo $tabindex++; ?>" /> <input type="button" value="<?php echo $lang_uploadile['convert']; ?>" tabindex="<?php echo $tabindex++; ?>" onclick="javascript:document.getElementById('ko').value=document.getElementById('mo').value*1024; document.getElementById('o').value=document.getElementById('mo').value*1048576;" />
						<label for="ko"><?php echo $lang_uploadile['ko'] ?></label> <input type="text" name="ko" id="ko" size="15" tabindex="<?php echo $tabindex++; ?>" /> <input type="button" value="<?php echo $lang_uploadile['convert']; ?>" tabindex="<?php echo $tabindex++; ?>" onclick="javascript:document.getElementById('mo').value=document.getElementById('ko').value/1024; document.getElementById('o').value=document.getElementById('ko').value*1024;"/>
						<label for="o"><?php echo $lang_uploadile['o'] ?></label> <input type="text" name="o" id="o" size="15" tabindex="<?php echo $tabindex++; ?>" /> <input type="button" value="<?php echo $lang_uploadile['convert']; ?>" tabindex="<?php echo $tabindex++; ?>" onclick="javascript:document.getElementById('mo').value=document.getElementById('o').value/1048576; document.getElementById('ko').value=(document.getElementById('o').value*1024)/1048576;"/>
					</fieldset>
				</div>
				</div>
			</form>
		</div>
		<h2 class="block2"><span><?php echo $lang_uploadile['fichier_membre'] ?></span></h2>
		<div class="box">
			<form method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>" enctype="multipart/form-data">
				<div class="inform">
					<div class="infldset">
						<p class="submitend"><input type="submit" name="update_thumb" value="<?php echo $lang_uploadile['update_thumb']; ?>" /></p>
						<table class="aligntop" cellspacing="0">
							<thead>
								<tr>
									<th scope="row"><?php echo $lang_uploadile['th0']; ?></th>
									<th scope="row"><?php echo $lang_uploadile['th']; ?></th>
									<th scope="row"><?php echo $lang_uploadile['th2']; ?></th>
									<th><input type="submit" value="<?php echo $lang_uploadile['delete']; ?>" name="supprimer" tabindex="<?php echo $tabindex++; ?>" /></th>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<th class="tc1" scope="row"><?php echo $lang_uploadile['th0']; ?></th>
									<th class="tc1" scope="row"><?php echo $lang_uploadile['th']; ?></th>
									<th class="tc1" scope="row"><?php echo $lang_uploadile['th2']; ?></th>
									<th><input type="submit" value="<?php echo $lang_uploadile['delete']; ?>" name="supprimer" tabindex="<?php echo $tabindex++; ?>" /></th>
								</tr>
							</tfoot>
							<tbody>
							<?php
							$retour = $db->query('SELECT id,username FROM '.$db->prefix.'users ORDER BY id') or error('Impossible de retrouver les informations utilisateur', __FILE__, __LINE__, $db->error());
							while($donnees = $db->fetch_assoc($retour))
							{
								$dir = 'img/members/'.$donnees['id'].'/';
								if(is_dir($dir)) // On vérifie que la valeur est un fichier (pour écarter les sous dossiers)
								{
									$open = opendir($dir); // On ouvre le répertoire
									$file = '';
									$files = array();
									while(false !== ($file = readdir($open))) // Tant qu'il y a des fichiers à lire
									{
										if(is_file($dir.$file)) // On vérifie que la valeur est un fichier (pour écarter les sous dossiers)
										{
												$extension = strtolower(substr(strrchr($file,  "." ), 1)); // On prend l'extension du fichier dans la variable $extension avec une sous-chaine
												$extsupport = explode(',', $pun_config['o_uploadile_laws'].','.strtoupper($pun_config['o_uploadile_laws'])); // La liste des extensions possibles pour une image
												if(in_array($extension, $extsupport) and ($file[0] != "#")) // Si l'extension ne figure pas dans la liste on passe le fichier (A noter : Pour cacher une image placez un "#" devant son nom)
													$files[] = $dir.$file; // Si elle y figure on ajoute le fichier à l'array $files
										}
									}
									closedir($open); // Et enfin on ferme le dossier
									if(isset($files))
									{
										foreach($files as $fichier)
										{
											$nom_fichier_brut = preg_replace('`img/members/'.$donnees['id'].'/(.+)`','$1',$fichier); // Nom du fichier avec extension
											$nom_fichier = preg_replace('`(.+)\..*`', '$1', $nom_fichier_brut); // Nom du fichier sans extension
											$type_fichier = preg_replace('`.*\.(.+)`', '$1', $nom_fichier_brut); // Extension du fichier
											$size_fichier = file_size(filesize($fichier)); // Taille du fichier
											$miniature = $dir.'mini_'.$nom_fichier.'.'.$type_fichier; // Adresse de la miniature potentiellement existente
											$mini = explode('_', $nom_fichier); // On vérifie que ce n'est pas une miniature
											if(isset($_POST['update_thumb']) AND $_POST['update_thumb'] != NULL AND $pun_config['o_uploadile_thumb'] == '1' AND $mini[0] != 'mini' AND ($type_fichier == 'png' OR $type_fichier == 'PNG' OR $type_fichier == 'jpeg' OR $type_fichier == 'jpg' OR $type_fichier == 'pjpeg' OR $type_fichier == 'JPEG' OR $type_fichier == 'JPG' OR $type_fichier == 'PJPEG' OR $type_fichier == 'gif' OR $type_fichier == 'GIF'))
											{
												$hauteur_destination = $pun_config['o_uploadile_thumb_size'];
												if($type_fichier == 'png' OR $type_fichier == 'PNG' OR $type_fichier == 'x-png' OR $type_fichier == 'X-PNG')
													$image_thumb = imagecreatefrompng($dir.$nom_fichier_brut);
												if($type_fichier == 'jpeg' OR $type_fichier == 'jpg' OR $type_fichier == 'pjpeg' OR $type_fichier == 'JPEG' OR $type_fichier == 'JPG' OR $type_fichier == 'PJPEG')
													$image_thumb = imagecreatefromjpeg($dir.$nom_fichier_brut);
												if($type_fichier == 'gif' OR $type_fichier == 'GIF')
													$image_thumb = imagecreatefromgif($dir.$nom_fichier_brut);
												$largeur = imagesx($image_thumb);
												$hauteur = imagesy($image_thumb);
												if($hauteur >= $hauteur_destination)
												{
													$pourcentage = $hauteur_destination/$hauteur;
													$largeur_destination = $largeur*$pourcentage;
													$destination = imagecreatetruecolor($largeur_destination, $hauteur_destination); 	
													imagecopyresampled($destination, $image_thumb, 0, 0, 0, 0, $largeur_destination, $hauteur_destination, $largeur, $hauteur);
													imagepng($destination, $dir.'mini_'.$nom_fichier_brut);
												}
												else
													imagepng($image_thumb, $dir.'mini_'.$nom_fichier_brut);
												if(isset($image_thumb))
													imagedestroy($image_thumb);
												if(isset($destination))
													imagedestroy($destination);	
											}
											if($mini[0] != 'mini')
											{
												?>
								<tr>
									<td class="tc2"><?php echo pun_htmlspecialchars($donnees['username']); ?></td>
									<td class="tc1"><a href="<?php echo $fichier; ?>"><?php echo $nom_fichier; ?></a> [<?php echo $size_fichier; ?>][<?php echo $type_fichier; ?>]</td>
									<?php
									// Affichage de la miniature
									if($pun_config['o_uploadile_thumb'] == '1' AND is_file($miniature))
										echo '<td class="tc2"><a href="'.$fichier.'"><img src="'.$miniature.'" alt="'.$nom_fichier.'" /></a></td>'."\n";
									else
										echo '<td class="tc2">'.$lang_uploadile['no_preview'].'</td>'."\n";
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
							}
							?>
							</tbody>
						</table>
						<input type="hidden" name="boucle_id" value="<?php echo $boucle_id; ?>" />
					</div>
				</div>
			</form>
		</div>
	</div>