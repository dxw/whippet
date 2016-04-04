<?php

namespace Dxw\Whippet\Dependencies;

class Migration
{
    public function __construct(
        \Dxw\Whippet\Factory $factory,
        /* string */ $dir
    ) {
        $this->factory = $factory;
        $this->dir = $dir;
    }

    public function migrate()
    {
        $result = $this->parsePluginsFile(file_get_contents($this->dir.'/plugins'));

        if ($result->isErr()) {
            return $result;
        }

        $pluginsFile = $result->unwrap();
        $whippetJson = [
            'src' => [
                'plugins' => $pluginsFile['source'],
            ],
            'plugins' => [
            ],
        ];

        foreach ($pluginsFile['plugins'] as $name => $data) {
            $newPlugin = [
                'name' => $name,
            ];

            if ($data->revision !== 'master') {
                $newPlugin['ref'] = $data->revision;
            }

            if ($data->repository !== '') {
                $newPlugin['src'] = $data->repository;
            }

            $whippetJson['plugins'][] = $newPlugin;
        }

        file_put_contents($this->dir.'/whippet.json', json_encode($whippetJson));

        return \Result\Result::ok();
    }

    // Copied/pasted from ManifestIo because that class doesn't expose the source line
    public function parsePluginsFile($raw_file)
    {
        // Check for #-comments
        $lines = explode("\n", $raw_file);
        foreach ($lines as $line) {
            if (preg_match('/^\s*#/', $line)) {
                return \Result\Result::err('Comments beginning with # are not permitted');
            }
        }

        $plugins = parse_ini_string($raw_file);

        if (!is_array($plugins)) {
            return \Result\Result::err('Unable to parse Plugins file');
        }

        // Got plugins - turn names to sources
        $source = $append = '';
        $plugins_manifest = new \stdClass();

        foreach ($plugins as $plugin => $data) {
            //
            // Special lines
            //

            if ($plugin == 'source') {
                if (empty($data)) {
                    return \Result\Result::err("Source is empty. It should just specify a repo root:\n\n  source = 'git@git.dxw.net:wordpress-plugins/'\n\nWhippet will attempt to find a source for your plugins by appending the plugin name to this URL.");
                }

                $source = $data;
                continue;
            }

            if ($plugin == 'append') {
                $append = $data;
                continue;
            }

            $repository = $revision = '';

            //
            // Everything else should be a plugin
            //

            // First see if there is data.
            if (!empty($data)) {
                // Format: LABEL[, REPO]
                if (strpos($data, ',') !== false) {
                    list($revision, $repository) = explode(',', $data);
                } else {
                    $revision = $data;
                }
            }

            // if (empty($repository)) {
            //     $repository = "{$source}{$plugin}{$append}";
            // }

            if (empty($revision)) {
                $revision = 'master';
            }

            // We should now have repo and revision
            $plugins_manifest->$plugin = new \stdClass();
            $plugins_manifest->$plugin->repository = $repository;
            $plugins_manifest->$plugin->revision = $revision;
        }

        return \Result\Result::ok([
            'source' => $source,
            'plugins' => $plugins_manifest,
        ]);
    }
}
