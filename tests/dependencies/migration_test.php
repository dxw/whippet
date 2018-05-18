<?php

class Dependencies_Migration_Test extends \PHPUnit\Framework\TestCase
{
    use \Helpers;

    private function getWhippetJsonExpectSavePath($path)
    {
        $whippetJson = $this->getMockBuilder('\\Dxw\\Whippet\\Files\\WhippetJson')
        ->disableOriginalConstructor()
        ->getMock();

        $whippetJson->expects($this->exactly(1))
        ->method('saveToPath')
        ->with($path);

        return $whippetJson;
    }

    public function testMigrate()
    {
        $dir = $this->getDir();

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

        $whippetJson = $this->getWhippetJsonExpectSavePath($dir.'/whippet.json');

        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Files\\WhippetJson', [
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
        ], $whippetJson);

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906'));
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-plugins/my-plugin', 'v1.6', \Result\Result::ok('d961c3d'));

        $migration = new \Dxw\Whippet\Dependencies\Migration(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $migration->migrate();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals('', $output);
    }

    public function testMigrateDeprecatedComment()
    {
        $dir = $this->getDir();

        file_put_contents($dir.'/plugins', implode("\n", [
            '#bad comment',
            'source = "git@git.dxw.net:wordpress-plugins/"',
            'twitget=',
        ]));

        $migration = new \Dxw\Whippet\Dependencies\Migration(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

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
        $dir = $this->getDir();

        file_put_contents($dir.'/plugins', implode("\n", [
            'source=',
        ]));

        $migration = new \Dxw\Whippet\Dependencies\Migration(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $migration->migrate();
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals("Source is empty. It should just specify a repo root:\n\n  source = 'git@git.dxw.net:wordpress-plugins/'\n\nWhippet will attempt to find a source for your plugins by appending the plugin name to this URL.", $result->getErr());
        $this->assertEquals('', $output);

        $this->assertFalse(file_exists($dir.'/whippet.json'));
    }

    public function testMigrateMissingPluginsFile()
    {
        $dir = $this->getDir();

        $migration = new \Dxw\Whippet\Dependencies\Migration(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $migration->migrate();
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals('plugins file not found in current working directory', $result->getErr());
        $this->assertEquals('', $output);

        $this->assertFalse(file_exists($dir.'/whippet.json'));
    }

    public function testMigratePreExistingWhippetJson()
    {
        $dir = $this->getDir();
        touch($dir.'/whippet.json');

        file_put_contents($dir.'/plugins', implode("\n", [
            'source = "git@git.dxw.net:wordpress-plugins/"',
            'twitget=',
        ]));

        $migration = new \Dxw\Whippet\Dependencies\Migration(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $migration->migrate();
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals('will not overwrite existing whippet.json', $result->getErr());
        $this->assertEquals('', $output);
    }
}
