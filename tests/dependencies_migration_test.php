<?php

class DependenciesMigration_Test extends PHPUnit_Framework_TestCase
{
    use Helpers;

    public function testMigrate()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

        file_put_contents($dir.'/plugins', implode("\n", [
            'source = "git@git.dxw.net:wordpress-plugins/"',
            'twitget=',
            'acf-options-page=',
            'new-members-only=',
            'wordpress-importer=',
            'page-links-to=',
            'akismet=',
            'acf-repeater=',
            'advanced-custom-fields=',
            'theme-my-login=',
            'breadcrumb-navxt=',
            'contact-form-7=',
            'wp-realtime-sitemap=',
            'tinymce-advanced=',
            'relevanssi-premium=',
            'jw-player-plugin-for-wordpress=',
            'gravityforms=',
            'unconfirmed=',
            'oauth2-server = ,git@git.dxw.net:dxw-wp-plugins/oauth2-server',
            'network-approve-users = v1.1.1,git@git.dxw.net:dxw-wp-plugins/network-approve-users',
        ]));

        file_put_contents($dir.'/plugins.lock', json_encode([
            'acf-options-page' => [
                'repository' => 'git@git.dxw.net:wordpress-plugins/acf-options-page',
                'revision' => 'master',
                'commit' => '34ea02cb73960e3f52dcceea23e039cc82efe5ab',
            ],
            'acf-repeater' => [
                'repository' => 'git@git.dxw.net:wordpress-plugins/acf-repeater',
                'revision' => 'master',
                'commit' => '4e3bc8818b85cbcdafd8a412a0dcb8a844b255a7',
            ],
            'advanced-custom-fields' => [
                'repository' => 'git@git.dxw.net:wordpress-plugins/advanced-custom-fields',
                'revision' => 'master',
                'commit' => '17bc8194bbe6ad2b1607bc62ec770532d9aaa55f',
            ],
            'akismet' => [
                'repository' => 'git@git.dxw.net:wordpress-plugins/akismet',
                'revision' => 'master',
                'commit' => '7243cc0051d943e15844b95617999b237569b99e',
            ],
            'breadcrumb-navxt' => [
                'repository' => 'git@git.dxw.net:wordpress-plugins/breadcrumb-navxt',
                'revision' => 'master',
                'commit' => 'f9b4e9d7bcfa3693180d8522dfebeefee0be7dc3',
            ],
            'contact-form-7' => [
                'repository' => 'git@git.dxw.net:wordpress-plugins/contact-form-7',
                'revision' => 'master',
                'commit' => '637c4ff0b09bd28e2f635d7a42151c5b8e967e36',
            ],
            'gravityforms' => [
                'repository' => 'git@git.dxw.net:wordpress-plugins/gravityforms',
                'revision' => 'master',
                'commit' => '9de1b599e1f0fc08a8e3fbde9d82b37988f4e12f',
            ],
            'jw-player-plugin-for-wordpress' => [
                'repository' => 'git@git.dxw.net:wordpress-plugins/jw-player-plugin-for-wordpress',
                'revision' => 'master',
                'commit' => '86d04055c02f6411d3689bbe4a659a5ab35cc0eb',
            ],
            'network-approve-users' => [
                'repository' => 'git@git.dxw.net:dxw-wp-plugins/network-approve-users',
                'revision' => 'v1.1.1',
                'commit' => '0fb98b966037eca51771912054fd4ee31eb012a7',
            ],
            'new-members-only' => [
                'repository' => 'git@git.dxw.net:wordpress-plugins/new-members-only',
                'revision' => 'master',
                'commit' => '84f07a9d2576e19ff280fe02dd42d57691e7ca6e',
            ],
            'oauth2-server' => [
                'repository' => 'git@git.dxw.net:dxw-wp-plugins/oauth2-server',
                'revision' => 'master',
                'commit' => '6e2fbe580b6823f7ea39a0fe134d4d7e72585f2c',
            ],
            'page-links-to' => [
                'repository' => 'git@git.dxw.net:wordpress-plugins/page-links-to',
                'revision' => 'master',
                'commit' => '9eaf4e4fcc74f05a2199a1f88f1cb6b2a1d6b067',
            ],
            'relevanssi-premium' => [
                'repository' => 'git@git.dxw.net:wordpress-plugins/relevanssi-premium',
                'revision' => 'master',
                'commit' => '7b521de0caa6bfd62c256fac3e86f44caa6a5c34',
            ],
            'theme-my-login' => [
                'repository' => 'git@git.dxw.net:wordpress-plugins/theme-my-login',
                'revision' => 'master',
                'commit' => '9bd066362fccce9a83a6eabd01582755a8166de5',
            ],
            'tinymce-advanced' => [
                'repository' => 'git@git.dxw.net:wordpress-plugins/tinymce-advanced',
                'revision' => 'master',
                'commit' => 'd5f0737cbf4d20a6bbdf93f8e6485da8a3b90b3f',
            ],
            'twitget' => [
                'repository' => 'git@git.dxw.net:wordpress-plugins/twitget',
                'revision' => 'master',
                'commit' => '694cdd97ba2b33cdd855cec20b6ed97bd3ea8e30',
            ],
            'unconfirmed' => [
                'repository' => 'git@git.dxw.net:wordpress-plugins/unconfirmed',
                'revision' => 'master',
                'commit' => 'e7fd03bcef879416d2c24275768d9e38174d13ff',
            ],
            'wordpress-importer' => [
                'repository' => 'git@git.dxw.net:wordpress-plugins/wordpress-importer',
                'revision' => 'master',
                'commit' => 'bfa13f36321ca8f3e3d46795609ca9bfd6631727',
            ],
            'wp-realtime-sitemap' => [
                'repository' => 'git@git.dxw.net:wordpress-plugins/wp-realtime-sitemap',
                'revision' => 'master',
                'commit' => 'c656546a8e1b2413879951c09ee658757f7dce9b',
            ],
        ]));

        $fileLocator = $this->getFileLocator(\Result\Result::ok($dir));

        $factory = $this->getFactory([
        ], [
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906')],
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-plugins/my-plugin', 'v1.6', \Result\Result::ok('d961c3d')],
        ]);

        $migration = new \Dxw\Whippet\DependenciesMigration($factory, $fileLocator);

        ob_start();
        $result = $migration->migrate();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals('', $output);

        $this->assertTrue(file_exists($dir.'/whippet.json'));
        $this->assertEquals([
            'src' => [
                'plugins' => 'git@git.dxw.net:wordpress-plugins/',
            ],
            'plugins' => [
                ['name' => 'twitget'],
                ['name' => 'acf-options-page'],
                ['name' => 'new-members-only'],
                ['name' => 'wordpress-importer'],
                ['name' => 'page-links-to'],
                ['name' => 'akismet'],
                ['name' => 'acf-repeater'],
                ['name' => 'advanced-custom-fields'],
                ['name' => 'theme-my-login'],
                ['name' => 'breadcrumb-navxt'],
                ['name' => 'contact-form-7'],
                ['name' => 'wp-realtime-sitemap'],
                ['name' => 'tinymce-advanced'],
                ['name' => 'relevanssi-premium'],
                ['name' => 'jw-player-plugin-for-wordpress'],
                ['name' => 'gravityforms'],
                ['name' => 'unconfirmed'],
                [
                    'name' => 'oauth2-server',
                    'src' => 'git@git.dxw.net:dxw-wp-plugins/oauth2-server',
                ],
                [
                    'name' => 'network-approve-users',
                    'ref' => 'v1.1.1',
                    'src' => 'git@git.dxw.net:dxw-wp-plugins/network-approve-users',
                ],
            ],
        ], json_decode(file_get_contents($dir.'/whippet.json'), true));
    }
}
