<?php

namespace Dxw\Whippet\Models;

use Dxw\Whippet\Dependencies\DependencyTypes;

class TranslationsApi
{
	/**
	 * Fetch language packs for a given language, and type.
	 *
	 * WordPress provides language packs for core, themes and plugins, so we
	 * fetch any that are available for the given type. Core language packs
	 * do not require a slug to be passed in, but all require a version, which
	 * should not start with 'v'.
	 */
	public static function fetchLanguageSrcAndRevision($type, $language, $version, $slug)
	{
		if ($type == DependencyTypes::LANGUAGES) {
			$url = "https://api.wordpress.org/translations/core/1.0/?version={$version}";
		} elseif ($type == DependencyTypes::PLUGINS) {
			$url = "https://api.wordpress.org/translations/plugins/1.0/?slug={$slug}&version={$version}";
		} elseif ($type == DependencyTypes::THEMES) {
			$url = "https://api.wordpress.org/translations/themes/1.0/?slug={$slug}&version={$version}";
		} else {
			return [null, null];
		}

		try {
			$data = file_get_contents($url);
		} catch (\Exception $exn) {
			return \Result\Result::err("got error: {$exn->getMessage()} on downloading: {$url}");
		}
		try {
			$allTranslations = json_decode($data, JSON_OBJECT_AS_ARRAY);
		} catch (\Exception $exn) {
			return \Result\Result::err("got error: {$exn->getMessage()} on decoding JSON from {$url}");
		}
		foreach($allTranslations['translations'] as $translation) {
			if ($translation['language'] == $language) {
				$src = stripslashes($translation['package']);
				$revision = $translation['version'];
				return \Result\Result::ok([$src, $revision]);
			}
		}
		return \Result\Result::ok([null, null]);
	}
}
