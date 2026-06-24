<?php

namespace Dxw\Whippet\Git;

use Kahlan\Plugin\Double;

describe(Git::class, function () {
	describe('->checkout()', function () {
		context('when revision is already present locally', function () {
			it('checks remote URL and checks out directly without fetching', function () {
				$git = Double::instance(['extends' => Git::class, 'args' => ['/path/to/repo']]);
				$commandsRun = [];

				allow($git)->toReceive('run_command')->andRun(function ($cmd, $cd = true) use (&$commandsRun) {
					$commandsRun[] = $cmd;
					$cmdStr = implode(' ', $cmd);

					if (strpos($cmdStr, 'remote get-url') !== false) {
						return [['https://example.org/repo'], 0];
					} elseif (strpos($cmdStr, 'cat-file') !== false) {
						return [[], 0];
					} elseif (strpos($cmdStr, 'checkout') !== false) {
						return [['Already on commit'], 0];
					}

					return [[], 0];
				});

				$result = $git->checkout('a1b2c3d4e5f6');

				expect($result)->toBe(true);
				expect($commandsRun)->toBe([
					['git', 'remote', 'get-url', 'origin'],
					['git', 'cat-file', '-e', 'a1b2c3d4e5f6^{commit}', '2>/dev/null'],
					['git', 'checkout', 'a1b2c3d4e5f6']
				]);
			});
		});

		context('when revision is not present locally', function () {
			it('checks remote URL, checks if the revision exists locally (fails), fetches, and then checks out', function () {
				$git = Double::instance(['extends' => Git::class, 'args' => ['/path/to/repo']]);
				$commandsRun = [];

				allow($git)->toReceive('run_command')->andRun(function ($cmd, $cd = true) use (&$commandsRun) {
					$commandsRun[] = $cmd;
					$cmdStr = implode(' ', $cmd);

					if (strpos($cmdStr, 'cat-file') !== false) {
						return [['not found'], 1];
					} elseif (strpos($cmdStr, 'remote get-url') !== false) {
						return [['https://example.org/repo'], 0];
					} elseif (strpos($cmdStr, 'fetch') !== false) {
						return [['fetched successfully'], 0];
					}

					return [[], 0];
				});

				$result = $git->checkout('a1b2c3d4e5f6');

				expect($result)->toBe(true);
				expect($commandsRun)->toBe([
					['git', 'remote', 'get-url', 'origin'],
					['git', 'cat-file', '-e', 'a1b2c3d4e5f6^{commit}', '2>/dev/null'],
					['git', 'fetch', '-a', '--force', '&&', 'git', 'checkout', 'a1b2c3d4e5f6']
				]);
			});
		});
	});
});
