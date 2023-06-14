<?php

use Kahlan\Plugin\Double;

describe(\Dxw\Whippet\Dependencies\Validator::class, function () {
	beforeEach(function () {
		$this->factory = Double::instance(['extends' => '\Dxw\Whippet\Factory']);
		$this->projectDirectory = Double::instance([
			'extends' => 'Dxw\Whippet\ProjectDirectory',
			'magicMethods' => true
		]);
		$this->validator = new \Dxw\Whippet\Dependencies\Validator(
			$this->factory,
			$this->projectDirectory
		);
	});

	it('exists', function () {
		$this->validator;
	});

	describe('->validate()', function () {
		context('whippet.lock cannot be found, or is invalid JSON', function () {
			it('returns an error', function () {
				allow($this->factory)->toReceive('callStatic')->andReturn(\Result\Result::err('An error'));
				$result = $this->validator->validate();
				expect($result)->toBeAnInstanceOf(\Result\Result::class);
				expect($result->getErr())->toEqual('whippet.lock error: An error');
			});
		});

		context('whippet.json cannot be found, or is invalid JSON', function () {
			it('returns an error', function () {
				$this->whippetLock = Double::instance();
				allow($this->whippetLock)->toReceive('isErr')->andReturn(false);
				allow($this->whippetLock)->toReceive('unwrap')->andReturn($this->whippetLock);
				allow($this->factory)->toReceive('callStatic')->andReturn(
					$this->whippetLock,
					\Result\Result::err('Whippet Json error')
				);
				$result = $this->validator->validate();
				expect($result)->toBeAnInstanceOf(\Result\Result::class);
				expect($result->getErr())->toEqual('whippet.json error: Whippet Json error');
			});
		});

		context('both whippet.json and whippet.lock are present and valid', function () {
			beforeEach(function () {
				$this->whippetLock = Double::instance();
				allow($this->whippetLock)->toReceive('isErr')->andReturn(false);
				allow($this->whippetLock)->toReceive('unwrap')->andReturn($this->whippetLock);
				$this->whippetJson = Double::instance();
				allow($this->whippetJson)->toReceive('isErr')->andReturn(false);
				allow($this->whippetJson)->toReceive('unwrap')->andReturn($this->whippetJson);
				allow($this->factory)->toReceive('callStatic')->andReturn(
					$this->whippetLock,
					$this->whippetJson
				);
			});
			context('but the hash is mismatched', function () {
				it('returns an error', function () {
					$whippetContents = '{
                        "src": {
                            "plugins": "git@git.govpress.com:wordpress-plugins/"
                        },
                        "plugins": [
                            {"name": "akismet"},
                            {"name": "advanced-custom-fields-pro"}
                        ]
                    }';
					allow('file_get_contents')->toBeCalled()->andReturn($whippetContents);
					allow('sha1')->toBeCalled()->andReturn('a_sha');
					expect('sha1')->toBeCalled()->once()->with($whippetContents);
					allow($this->whippetLock)->toReceive('getHash')->andReturn('a_different_sha');
					$result = $this->validator->validate();
					expect($result)->toBeAnInstanceOf(\Result\Result::class);
					expect($result->getErr())->toEqual('hash mismatch between whippet.json and whippet.lock');
				});
			});
			context('but there are different numbers of dependencies in the .json and .lock', function () {
				it('returns an error', function () {
					$whippetContents = '{
                            "src": {
                                "plugins": "git@git.govpress.com:wordpress-plugins/"
                            },
                            "plugins": [
                                {"name": "akismet"},
                                {"name": "advanced-custom-fields-pro"}
                            ]
                        }';
					allow('file_get_contents')->toBeCalled()->andReturn($whippetContents);
					allow('sha1')->toBeCalled()->andReturn('a_matching_sha');
					expect('sha1')->toBeCalled()->once()->with($whippetContents);
					allow($this->whippetLock)->toReceive('getHash')->andReturn('a_matching_sha');
					allow($this->whippetJson)->toReceive('getDependencies')->andReturn([], [
							[
								'name' => 'akismet'
							],
							[
								'name' => 'advanced-custom-fields-pro'
							]
						]);
					allow($this->whippetLock)->toReceive('getDependencies')->andReturn([], [
							[
								'name' => 'akismet'
							]
						]);
					$result = $this->validator->validate();
					expect($result)->toBeAnInstanceOf(\Result\Result::class);
					expect($result->getErr())->toEqual('Mismatched dependencies count for type plugins');
				});
			});
			context('but entries in .json and .lock do not match', function () {
				it('returns an error', function () {
					$whippetContents = '{
                            "src": {
                                "plugins": "git@git.govpress.com:wordpress-plugins/"
                            },
                            "plugins": [
                                {"name": "akismet"},
                                {"name": "advanced-custom-fields-pro"}
                            ]
                        }';
					allow('file_get_contents')->toBeCalled()->andReturn($whippetContents);
					allow('sha1')->toBeCalled()->andReturn('a_matching_sha');
					expect('sha1')->toBeCalled()->once()->with($whippetContents);
					allow($this->whippetLock)->toReceive('getHash')->andReturn('a_matching_sha');
					allow($this->whippetJson)->toReceive('getDependencies')->andReturn([], [
							[
								'name' => 'akismet'
							],
							[
								'name' => 'advanced-custom-fields-pro'
							]
						]);
					allow($this->whippetLock)->toReceive('getDependencies')->andReturn([], [
							[
								'name' => 'akismet'
							],
							[
								'name' => 'some-other-plugin'
							]
						]);
					$result = $this->validator->validate();
					expect($result)->toBeAnInstanceOf(\Result\Result::class);
					expect($result->getErr())->toEqual('No entry found in whippet.lock for plugins: advanced-custom-fields-pro');
				});
			});
			context('but an entry in .lock is malformed', function () {
				it('returns an error', function () {
					$whippetContents = '{
                            "src": {
                                "plugins": "git@git.govpress.com:wordpress-plugins/"
                            },
                            "plugins": [
                                {"name": "akismet"},
                                {"name": "advanced-custom-fields-pro"}
                            ]
                        }';
					allow('file_get_contents')->toBeCalled()->andReturn($whippetContents);
					allow('sha1')->toBeCalled()->andReturn('a_matching_sha');
					expect('sha1')->toBeCalled()->once()->with($whippetContents);
					allow($this->whippetLock)->toReceive('getHash')->andReturn('a_matching_sha');
					allow($this->whippetJson)->toReceive('getDependencies')->andReturn([], [
							[
								'name' => 'akismet'
							],
							[
								'name' => 'advanced-custom-fields-pro'
							]
						]);
					allow($this->whippetLock)->toReceive('getDependencies')->andReturn([], [
							[
								'name' => 'akismet',
								'src' => 'a_src_1',
								'revision' => 'a_revision_1'
							],
							[
								'name' => 'advanced-custom-fields-pro',
								'src' => 'a_src_2',
							]
						]);
					$result = $this->validator->validate();
					expect($result)->toBeAnInstanceOf(\Result\Result::class);
					expect($result->getErr())->toEqual('Missing revision property in whippet.lock for plugins: advanced-custom-fields-pro');
				});
			});
			context('and everything is good', function () {
				it('returns an ok result', function () {
					$whippetContents = '{
                            "src": {
                                "plugins": "git@git.govpress.com:wordpress-plugins/"
                            },
                            "plugins": [
                                {"name": "akismet"},
                                {"name": "advanced-custom-fields-pro"}
                            ]
                        }';
					allow('file_get_contents')->toBeCalled()->andReturn($whippetContents);
					allow('sha1')->toBeCalled()->andReturn('a_matching_sha');
					expect('sha1')->toBeCalled()->once()->with($whippetContents);
					allow($this->whippetLock)->toReceive('getHash')->andReturn('a_matching_sha');
					allow($this->whippetJson)->toReceive('getDependencies')->andReturn([], [
							[
								'name' => 'akismet'
							],
							[
								'name' => 'advanced-custom-fields-pro'
							]
						]);
					allow($this->whippetLock)->toReceive('getDependencies')->andReturn([], [
							[
								'name' => 'akismet',
								'src' => 'a_src_1',
								'revision' => 'a_revision_1'
							],
							[
								'name' => 'advanced-custom-fields-pro',
								'src' => 'a_src_2',
								'revision' => 'a_revision_2'
							]
						]);
					ob_start();
					$result = $this->validator->validate();
					$output = ob_get_clean();
					expect($output)->toEqual("Valid whippet.json and whippet.lock \n");
					expect($result)->toBeAnInstanceOf(\Result\Result::class);
					expect($result->isErr())->toEqual(false);
				});
			});
		});
	});
});
