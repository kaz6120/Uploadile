<?php
/***********************************************************************

  Copyright (C) 2007  BN (bnmaster@la-bnbox.info)

  This software is free software; you can redistribute it and/or modify it
  under the terms of the GNU General Public License as published
  by the Free Software Foundation; either version 2 of the License,
  or (at your option) any later version.

  This software is distributed in the hope that it will be useful, but
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

// Tell profil.php that this is indeed a plugile and that it is loaded
define('PUN_PLUGIN_LOADED', 1);
define('PLUGIN_VERSION', '1.1');
define('PLUGIN_URL', 'profile.php?plugin='.$_GET['plugin'].'&amp;id='.$_GET['id']);
define('PLUGIN_NAME', $_GET['plugin']);
$tabindex = 1;
$boucle_id = 1;

// Load the uploadile.php language file
if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/uploadile.php'))
	require PUN_ROOT.'lang/'.$pun_user['language'].'/uploadile.php';
else
	require PUN_ROOT.'lang/English/uploadile.php';

//
// Parser de nom de fichiers
//
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
$retour = $db->query('SELECT group_id,upload FROM '.$db->prefix.'users WHERE id='.$id) or error('Impossible de retrouver les informations utilisateur', __FILE__, __LINE__, $db->error());
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
	$dir = 'img/members/'.$id.'/';
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
		$db->query('UPDATE '.$db->prefix.'users SET upload=\''.$upload.'\' WHERE id='.$_GET['id']) or error(sprintf($lang_uploadile['err_insert'],$conf_name), __FILE__, __LINE__, $db->error());
	}
	
	if(isset($_GET['type']) AND $_GET['type'] == '2')
		$url = 'gestionnaire.php';
	else
		$url = PLUGIN_URL;
	if($erreur == 0)
		redirect($url, $lang_uploadile['delete_success']);
	else
		redirect($url, $lang_uploadile['err_delete']);
}

// Ajout d'un fichier
elseif(isset($_FILES['fichier']) AND $_FILES['fichier'] != NULL AND $_FILES['fichier']['error'] == 0)
{
	// On vérifie les extansions
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
					$dir = 'img/members/'.$id.'/';
					if(is_file($dir.$fichier))
						$fichier = date('dmY\-Hi', time()).'_'.$fichier;
					if(!is_dir('img/members/'))
						mkdir('img/members', 0755);
					if(!is_dir($dir))
						mkdir('img/members/'.$id, 0755);
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
					$db->query('UPDATE '.$db->prefix.'users SET upload=\''.$upload.'\' WHERE id='.$_GET['id']) or error(sprintf($lang_uploadile['err_insert'],$conf_name), __FILE__, __LINE__, $db->error());
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

else
{
	generate_profile_menu(PLUGIN_NAME);
	?>
	<div id="uploadile" class="blockform">
		<h2><span><?php echo $lang_uploadile['titre_1']; ?></span></h2>
		<div class="box">
			<div class="inbox">
				<p style="margin: 5px"><?php echo $lang_uploadile['info_1']; ?></p>
			</div>
		</div>
		<h2 class="block2"><span><?php echo $lang_uploadile['titre_2']; ?></span></h2>
		<div class="box">
			<form method="post" action="<?php echo htmlentities($_SERVER['REQUEST_URI']) ?>" enctype="multipart/form-data">
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
						</div>
					</fieldset>
				</div>
				<p><input type="submit" name="submit" value="<?php echo $lang_uploadile['submit']; ?>" tabindex="<?php echo $tabindex++; ?>" /></p>
			</form>
		</div>
		<h2 class="block2"><span><?php echo $lang_uploadile['titre_3']; ?></span></h2>
		<div class="box">
			<form method="post" action="<?php echo htmlentities($_SERVER['REQUEST_URI']) ?>" enctype="multipart/form-data">
				<div class="inform">
					<?php 
					if($user_plugile['group_id'] != '1')
						printf($lang_uploadile['info_3'], $pourcentage_utilise, '%', $pourcentage_utilise, '%', file_size($user_plugile['upload']),file_size($limit), $id);
					else
						printf($lang_uploadile['info_3_admi'], $id);
					?>
					<div class="infldset">
						<table class="aligntop" cellspacing="0">
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
							$dir = 'img/members/'.$id.'/';
							if(is_dir($dir)) // On vérifie que la valeur est un fichier (pour écarter les sous dossiers)
							{
								$open = opendir($dir); // On ouvre le répertoire
								while(false !== ($file = readdir($open))) // Tant qu'il y a des fichiers à lire
								{
									if(is_file($dir.$file)) // On vérifie que la valeur est un fichier (pour écarter les sous dossiers)
									{
											$extension = strtolower(substr(strrchr($file,  "." ), 1)); // On prend l'extension du fichier dans la variable $extension avec une sous-chaine
											$extsupport = explode(',', $pun_config['o_uploadile_laws'].','.strtoupper($pun_config['o_uploadile_laws'])); // La liste des extensions possibles pour une image
											if(in_array($extension, $extsupport) and ($file[0] != "#")) // Si l'extension ne figure pas dans la liste on passe le fichier (A noter : Pour cacher une image placez un "#" devant son nom)
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
										$nom_fichier_brut = preg_replace('`img/members/'.$id.'/(.+)`','$1',$fichier); // Nom du fichier avec extension
										$nom_fichier = preg_replace('`(.+)\..*`', '$1', $nom_fichier_brut); // Nom du fichier sans extension
										$type_fichier = preg_replace('`.*\.(.+)`', '$1', $nom_fichier_brut); // Extension du fichier
										$size_fichier = file_size(filesize($fichier)); // Taille du fichier
										$miniature = $dir.'mini_'.$nom_fichier.'.'.$type_fichier; // Adresse de la miniature potentiellement existente
										$mini = explode('_', $nom_fichier); // On vérifie que ce n'est pas une miniature
										if($mini[0] != 'mini')
										{
											?>
											<tr>
												<td class="tc1"><a href="<?php echo $fichier; ?>"><?php echo $nom_fichier; ?></a> [<?php echo $size_fichier; ?>] [<?php echo $type_fichier; ?>]</td>
												<?php
												// Affichage de la miniature
												if($pun_config['o_uploadile_thumb'] == '1' AND is_file($miniature))
													echo '<td class="tc2"><a href="'.$fichier.'"><img src="'.$miniature.'" alt="'.$nom_fichier.'" /></a></td>';
												else
													echo '<td class="tc2">'.$lang_uploadile['no_preview'].'</td>';
												?>
												<td style="width:12px;text-align:center;"><input type="checkbox" name="supprimer_<?php echo $boucle_id++; ?>" value="<?php echo $nom_fichier_brut; ?>" tabindex="<?php echo $tabindex++; ?>" /></td>
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
}