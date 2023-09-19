<?php

describe(\Dxw\Whippet\Dependencies\DependencyTypes::class, function () {
	beforeAll(function () {
		$this->plugins = \Dxw\Whippet\Dependencies\DependencyTypes::PLUGINS;
		$this->themes = \Dxw\Whippet\Dependencies\DependencyTypes::THEMES;
		$this->languages = \Dxw\Whippet\Dependencies\DependencyTypes::LANGUAGES;
		$this->random_text = "lorem ipsum";
	});

	context("getDependencyTypes()", function () {
		it('returns a list of dependencies as strings', function () {
			expect(\Dxw\Whippet\Dependencies\DependencyTypes::getDependencyTypes())->toBe(['themes', 'plugins', 'languages']);
		});
	});

	context("getThemeAndPluginTypes()", function () {
		it('returns a list of dependencies as strings without languages', function () {
			expect(\Dxw\Whippet\Dependencies\DependencyTypes::getThemeAndPluginTypes())->toBe(['themes', 'plugins']);
		});
	});

	context("isLanguageType()", function () {
		it('only returns true for language types', function () {
			expect(\Dxw\Whippet\Dependencies\DependencyTypes::isLanguageType($this->plugins))->toBe(false);
			expect(\Dxw\Whippet\Dependencies\DependencyTypes::isLanguageType($this->themes))->toBe(false);
			expect(\Dxw\Whippet\Dependencies\DependencyTypes::isLanguageType($this->languages))->toBe(true);
			expect(\Dxw\Whippet\Dependencies\DependencyTypes::isLanguageType($this->random_text))->toBe(false);
			expect(\Dxw\Whippet\Dependencies\DependencyTypes::isLanguageType(""))->toBe(false);
			expect(\Dxw\Whippet\Dependencies\DependencyTypes::isLanguageType(null))->toBe(false);
		});
	});

	context("isNotLanguageType()", function () {
		it('only returns false for language types', function () {
			expect(\Dxw\Whippet\Dependencies\DependencyTypes::isNotLanguageType($this->plugins))->toBe(true);
			expect(\Dxw\Whippet\Dependencies\DependencyTypes::isNotLanguageType($this->themes))->toBe(true);
			expect(\Dxw\Whippet\Dependencies\DependencyTypes::isNotLanguageType($this->languages))->toBe(false);
			expect(\Dxw\Whippet\Dependencies\DependencyTypes::isNotLanguageType($this->random_text))->toBe(true);
			expect(\Dxw\Whippet\Dependencies\DependencyTypes::isNotLanguageType(""))->toBe(true);
			expect(\Dxw\Whippet\Dependencies\DependencyTypes::isNotLanguageType(null))->toBe(true);
		});
	});
});
