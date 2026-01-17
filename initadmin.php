<?php

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

global $prefilter;

add_event_handler('get_batch_manager_prefilters', 'BMPN_add_batch_manager_prefilters');
add_event_handler('perform_batch_manager_prefilters', 'BMPN_perform_batch_manager_prefilters', EVENT_HANDLER_PRIORITY_NEUTRAL, 2);

function BMPN_add_batch_manager_prefilters($prefilters)
{
	array_push($prefilters, array(
		'ID' => 'BMPN',
		'NAME' => l10n('Without_name'),
	));
	return $prefilters;
}

function BMPN_perform_batch_manager_prefilters($filter_sets, $prefilter)
{
	if ($prefilter == 'BMPN') {
		// Récupère toutes les images avec leur nom et nom de fichier
		$query = 'SELECT id, name, file FROM ' . IMAGES_TABLE . ';';
		$result = pwg_query($query);

		$matching_ids = array();
		while ($row = pwg_db_fetch_assoc($result)) {
			// Extrait le nom du fichier sans extension
			$file_without_ext = pathinfo($row['file'], PATHINFO_FILENAME);
			$name = $row['name'];

			// Si le nom est vide ou null, considéré comme "sans nom"
			if (empty($name)) {
				$matching_ids[] = $row['id'];
				continue;
			}

			// Normalise les deux chaînes pour la comparaison
			$normalized_file = BMPN_normalize_string($file_without_ext);
			$normalized_name = BMPN_normalize_string($name);

			// Calcule le pourcentage de similarité
			similar_text($normalized_file, $normalized_name, $percent);

			// Si similarité > 80%, la photo n'a pas de "vrai" nom
			if ($percent > 80) {
				$matching_ids[] = $row['id'];
			}
		}

		$filter_sets[] = $matching_ids;
	}
	return $filter_sets;
}

/**
 * Normalise une chaîne pour la comparaison :
 * - Convertit en minuscules
 * - Supprime les accents
 * - Supprime les caractères spéciaux (garde uniquement alphanumériques)
 */
function BMPN_normalize_string($str)
{
	// Convertit en minuscules
	$str = mb_strtolower($str, 'UTF-8');

	// Supprime les accents
	$str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);

	// Remplace les underscores et tirets par des espaces
	$str = str_replace(array('_', '-'), ' ', $str);

	// Supprime tous les caractères non alphanumériques sauf espaces
	$str = preg_replace('/[^a-z0-9 ]/', '', $str);

	// Supprime les espaces multiples
	$str = preg_replace('/\s+/', ' ', $str);

	return trim($str);
}

add_event_handler('get_batch_manager_prefilters', 'BMPN2_add_batch_manager_prefilters');
add_event_handler('perform_batch_manager_prefilters', 'BMPN2_perform_batch_manager_prefilters', EVENT_HANDLER_PRIORITY_NEUTRAL, 2);

function BMPN2_add_batch_manager_prefilters($prefilters)
{
	array_push($prefilters, array(
		'ID' => 'BMPN2',
		'NAME' => l10n('With_name'),
	));
	return $prefilters;
}

function BMPN2_perform_batch_manager_prefilters($filter_sets, $prefilter)
{
	if ($prefilter == 'BMPN2') {
		// Récupère toutes les images avec leur nom et nom de fichier
		$query = 'SELECT id, name, file FROM ' . IMAGES_TABLE . ';';
		$result = pwg_query($query);

		$matching_ids = array();
		while ($row = pwg_db_fetch_assoc($result)) {
			// Si le nom est vide ou null, ce n'est pas un "vrai" nom
			if (empty($row['name'])) {
				continue;
			}

			// Extrait le nom du fichier sans extension
			$file_without_ext = pathinfo($row['file'], PATHINFO_FILENAME);

			// Normalise les deux chaînes pour la comparaison
			$normalized_file = BMPN_normalize_string($file_without_ext);
			$normalized_name = BMPN_normalize_string($row['name']);

			// Calcule le pourcentage de similarité
			similar_text($normalized_file, $normalized_name, $percent);

			// Si similarité <= 80%, la photo a un "vrai" nom personnalisé
			if ($percent <= 80) {
				$matching_ids[] = $row['id'];
			}
		}

		$filter_sets[] = $matching_ids;
	}
	return $filter_sets;
}

add_event_handler('loc_end_element_set_global', 'BMPN_loc_end_element_set_global');
add_event_handler('element_set_global_action', 'BMPN_element_set_global_action', EVENT_HANDLER_PRIORITY_NEUTRAL, 2);

function BMPN_loc_end_element_set_global()
{
	global $template, $pwg_loaded_plugins;
	if (isset($pwg_loaded_plugins['ExtendedDescription'])) {
		$templateBMPN = '
		<input type="checkbox" name="check_BMPN4" id="input_BMPN4"> ' . l10n('remove_name') . '<br>
		<textarea rows="5" cols="50" placeholder="' . l10n('Type_here_the_name') . "  /  " . l10n('Use_Extended_Description_tags...') . '" class="description" name="BMPN3" id="BMPN3"></textarea><br>
	  ';
	} else {
		$templateBMPN = '
		<input type="checkbox" name="check_BMPN4" id="input_BMPN4"> ' . l10n('remove_name') . '<br>
		<textarea rows="5" cols="50" placeholder="' . l10n('Type_here_the_name') . '" class="description" name="BMPN3" id="BMPN3"></textarea><br>
	  ';
	}
	$template->func_combine_script(array(
		"id" => "bmp_common",
		"load" => "footer",
		"path" => BMPN_PATH.'/bmp.js'
	));
	$template->append('element_set_global_plugins_actions', array(
		'ID' => 'BMPN3',
		'NAME' => l10n('Set_name'),
		'CONTENT' => $templateBMPN,
	));
}

function BMPN_element_set_global_action($action, $collection)
{
	if ($action == 'BMPN3')
	{
		global $page;

		if (isset($_POST['check_BMPN4']))
		{
			$_POST['BMPN3'] = null;
		}
		$datas = array();
		foreach ($collection as $image_id)
		{
			array_push(
				$datas,
				array(
					'id' => $image_id,
					'comment' => $_POST['BMPN3']
				)
			);
		}
		mass_updates(
			IMAGES_TABLE,
			array('primary' => array('id'), 'update' => array('name')),
			$datas
		);
	}
}
