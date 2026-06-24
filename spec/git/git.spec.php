<?php

namespace Dxw\Whippet\Git;

class TestGit extends Git
{
	public $commandsRun = [];
	public $commandResults = [];

	protected function run_command(array $cmd, $cd = true)
	{
		$commandString = implode(' ', $cmd);
		$this->commandsRun[] = $cmd;

		foreach ($this->commandResults as $pattern => $result) {
			if ($commandString === $pattern || strpos($commandString, $pattern) !== false) {
				return $result;
			}
		}

		return [['default output'], 0];
	}
}

describe(Git::class, function () {
	describe('->checkout()', function () {
		context('when revision is already present locally', function () {
			it('checks out directly and does not fetch or check remote archive status', function () {
				$git = new TestGit('/path/to/repo');
				// Mock the check for revision existence to return success (exit code 0)
				$git->commandResults = [
					'cat-file' => [[''], 0],
					'checkout' => [['Already on commit'], 0]
				];

				$result = $git->checkout('a1b2c3d4e5f6');

				expect($result)->toBe(true);

				$hasCatFile = false;
				$hasCheckoutOnly = false;
				$hasFetch = false;
				$hasRemoteGetUrl = false;

				foreach ($git->commandsRun as $cmd) {
					$cmdStr = implode(' ', $cmd);
					if (strpos($cmdStr, 'cat-file') !== false) {
						$hasCatFile = true;
					}
					if (strpos($cmdStr, 'checkout') !== false && strpos($cmdStr, 'fetch') === false) {
						$hasCheckoutOnly = true;
					}
					if (strpos($cmdStr, 'fetch') !== false) {
						$hasFetch = true;
					}
					if (strpos($cmdStr, 'remote get-url') !== false) {
						$hasRemoteGetUrl = true;
					}
				}

				expect($hasCatFile)->toBe(true);
				expect($hasCheckoutOnly)->toBe(true);
				expect($hasFetch)->toBe(false);
				expect($hasRemoteGetUrl)->toBe(false);
			});
		});

		context('when revision is not present locally', function () {
			it('checks if the revision exists locally (fails), checks remote URL, fetches, and then checks out', function () {
				$git = new TestGit('/path/to/repo');
				// Mock the check for revision existence to fail (exit code 1)
				$git->commandResults = [
					'cat-file' => [['not found'], 1],
					'remote get-url' => [['https://example.org/repo'], 0],
					'fetch' => [['fetched successfully'], 0]
				];

				$result = $git->checkout('a1b2c3d4e5f6');

				expect($result)->toBe(true);

				$hasCatFile = false;
				$hasFetchAndCheckout = false;
				$hasRemoteGetUrl = false;

				foreach ($git->commandsRun as $cmd) {
					$cmdStr = implode(' ', $cmd);
					if (strpos($cmdStr, 'cat-file') !== false) {
						$hasCatFile = true;
					}
					if (strpos($cmdStr, 'fetch') !== false && strpos($cmdStr, 'checkout') !== false) {
						$hasFetchAndCheckout = true;
					}
					if (strpos($cmdStr, 'remote get-url') !== false) {
						$hasRemoteGetUrl = true;
					}
				}

				expect($hasCatFile)->toBe(true);
				expect($hasRemoteGetUrl)->toBe(true);
				expect($hasFetchAndCheckout)->toBe(true);
			});
		});
	});
});
