<?php

class Dependencies_Migration_Test extends PHPUnit_Framework_TestCase
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

        $factory = $this->getFactory([
        ], [
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906')],
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-plugins/my-plugin', 'v1.6', \Result\Result::ok('d961c3d')],
        ]);

        $migration = new \Dxw\Whippet\Dependencies\Migration($factory, $dir);

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

    public function testMigrateDeprecatedComment()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

        file_put_contents($dir.'/plugins', implode("\n", [
            '#bad comment',
            'source = "git@git.dxw.net:wordpress-plugins/"',
            'twitget=',
        ]));

        $factory = $this->getFactory([], []);

        $migration = new \Dxw\Whippet\Dependencies\Migration($factory, $dir);

        ob_start();
        $result = $migration->migrate();
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals('Comments beginning with # are not permitted', $result->getErr());
        $this->assertEquals('', $output);

        $this->assertFalse(file_exists($dir.'/whippet.json'));
    }

    public function testMigrateMissingSource()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

        file_put_contents($dir.'/plugins', implode("\n", [
            'source=',
        ]));

        $factory = $this->getFactory([], []);

        $migration = new \Dxw\Whippet\Dependencies\Migration($factory, $dir);

        ob_start();
        $result = $migration->migrate();
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals("Source is empty. It should just specify a repo root:\n\n  source = 'git@git.dxw.net:wordpress-plugins/'\n\nWhippet will attempt to find a source for your plugins by appending the plugin name to this URL.", $result->getErr());
        $this->assertEquals('', $output);

        $this->assertFalse(file_exists($dir.'/whippet.json'));
    }

    public function testMigratePrettyPrint()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

        file_put_contents($dir.'/plugins', implode("\n", [
            'source = "git@git.dxw.net:wordpress-plugins/"',
            'twitget=',
        ]));

        $factory = $this->getFactory([
        ], [
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-plugins/twitget', 'master', \Result\Result::ok('27ba906')],
        ]);

        $migration = new \Dxw\Whippet\Dependencies\Migration($factory, $dir);

        ob_start();
        $result = $migration->migrate();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals('', $output);

        $this->assertTrue(file_exists($dir.'/whippet.json'));
        $this->assertEquals(implode("\n", [
            '{',
            '    "src": {',
            '        "plugins": "git@git.dxw.net:wordpress-plugins/"',
            '    },',
            '    "plugins": [',
            '        {',
            '            "name": "twitget"',
            '        }',
            '    ]',
            '}',
            '', // Trailing newline
        ]), file_get_contents($dir.'/whippet.json'));
    }

    public function testMigrateMissingPluginsFile()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

        $factory = $this->getFactory([], []);

        $migration = new \Dxw\Whippet\Dependencies\Migration($factory, $dir);

        ob_start();
        $result = $migration->migrate();
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals('plugins file not found in current working directory', $result->getErr());
        $this->assertEquals('', $output);

        $this->assertFalse(file_exists($dir.'/whippet.json'));
    }
}
