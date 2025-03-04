<?php

describe(\Dxw\Whippet\Dependencies\DependencyTypes::class, function () {
	context("getDependencyTypes()", function () {
		it('returns a list of dependencies as strings', function () {
			expect(\Dxw\Whippet\Dependencies\DependencyTypes::getDependencyTypes())->toBe(['themes', 'plugins']);
		});
	});
});
