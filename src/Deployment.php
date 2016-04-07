<?php

namespace Dxw\Whippet;

class Deployment
{
    use Modules\Helpers\WhippetHelpers;

    public function __construct(
        \Dxw\Whippet\Factory $factory,
        \Dxw\Whippet\ProjectDirectory $projectDir,
        /* string */ $deployDir
    ) {
        $this->factory = $factory;
        $this->projectDir = $projectDir;
        $this->deploy_dir = $deployDir;
        $this->releases_dir = "{$this->deploy_dir}/releases";
        $this->shared_dir = "{$this->deploy_dir}/shared";

        // Dirty hack for functions that don't work with vfsStream
        $this->symlink = 'symlink';
        $this->realpath = 'realpath';
    }

    public function deploy(/* bool */ $force, /* int */ $keep)
    {
        try {
            //
            // 1. Make sure the target directory does not exist (or exists and is empty)
            // 2. Load or create the releases manifest
            // 3. Deploy the app into the releases directory
            // 4. Validate the deploy
            // 5. Create or update the "current" symlink
            //

            //
            // Make sure the environment is sane
            //

            // Got all the deploy directories?

            $this->check_and_create_dir($this->deploy_dir);
            $this->check_and_create_dir($this->releases_dir);
            $this->check_and_create_dir($this->shared_dir);

            //
            // Load up the manifest and create the new release
            //

            $this->load_releases_manifest();

            // TODO: add support for a release message
            if (count($this->releases_manifest)) {
                $release_number = $this->releases_manifest[count($this->releases_manifest) - 1]->number + 1;
            } else {
                $release_number = 0;
            }

            $new_release = $this->factory->newInstance('\\Dxw\\Whippet\\Modules\\Release', $this->releases_dir, '', $release_number);

            // Make it.
            $new_release->create($force);

            //
            // Did everything work?
            //

            $checks = [
                //
                // Is WP there?
                //

                'wp-login.php is missing; is WordPress properly deployed?' => !file_exists("{$new_release->release_dir}/wp-login.php"),
                'wp-includes/wp-db.php is missing; is WordPress properly deployed?' => !file_exists("{$new_release->release_dir}/wp-includes/wp-db.php"),
                'wp-admin/edit.php is missing; is WordPress properly deployed?' => !file_exists("{$new_release->release_dir}/wp-admin/edit.php"),

                //
                // Is the app there?
                //

                'wp-content/themes is missing; is the app properly deployed?' => !file_exists("{$new_release->release_dir}/wp-content/themes"),
                'wp-content/plugins is missing; is the app properly deployed?' => !file_exists("{$new_release->release_dir}/wp-content/plugins"),

                // Is there stuff in shared? Does it look right?
                'wp-config.php is not in the shared directory.' => !file_exists("{$new_release->release_dir}/../../shared/wp-config.php"),
                'uploads directory is not in the shared directory.' => (!file_exists("{$new_release->release_dir}/../../shared/uploads") && !file_exists("{$new_release->release_dir}/../../shared/uploads")),
                "wp-config.php doesn't contain DB_NAME; is it valid?" => !strpos(file_get_contents("{$new_release->release_dir}/../../shared/wp-config.php"), 'DB_NAME'),

                //
                // Did the symlinking work?
                //

                'wp-config.php is missing; did the symlinking fail?' => !file_exists("{$new_release->release_dir}/wp-config.php"),
                'wp-content/uploads is missing; did the symlinking fail?' => (!file_exists("{$new_release->release_dir}/wp-content/uploads") && !is_link("{$new_release->release_dir}/../../shared/uploads")),
            ];

            $release_ok = true;
            $messages = [];

            foreach ($checks as $message => $failed) {
                if ($failed) {
                    $release_ok = false;
                    $messages[] = "\t{$message}";
                }
            }

            //
            // If it was all ok:
            //

            if (!$release_ok) {
                $broken_release = $broken_release_prefix = "{$new_release->release_dir}.broken";
                $count = 1;

                while (file_exists($broken_release)) {
                    $broken_release = $broken_release_prefix."_{$count}";
                }

                rename("{$new_release->release_dir}", "{$broken_release}");

                echo "Problems:\n";
                echo implode($messages, "\n");
                echo "\n\nRelease did not validate; it has been moved to: $broken_release";

                return \Result\Result::err('release did not validate');
            } else {
                // If we are forcing, rejig some directories
                if ($force) {
                    rename("{$this->releases_dir}/{$new_release->deployed_commit}", "{$this->releases_dir}/{$new_release->deployed_commit}_".($new_release->number - 1));
                    rename("{$new_release->release_dir}", "{$this->releases_dir}/{$new_release->deployed_commit}");

                    $new_release->release_dir = "{$this->releases_dir}/{$new_release->deployed_commit}";
                }

                $current = "{$new_release->release_dir}/../../current";

                // If we are not forcing, check to see if the release being deployed is the currently deployed release - if so, do nothing
                if (!$force && file_exists($current) && readlink($current) == call_user_func($this->realpath, $new_release->release_dir)) {
                    return;
                }

                if (file_exists($current)) {
                    unlink("{$current}");
                }

                call_user_func($this->symlink, call_user_func($this->realpath, "{$new_release->release_dir}"), "{$current}");

                // Update manifest
                $release = new \stdClass();
                $release->time = $new_release->time;
                $release->number = $new_release->number;
                $release->deployed_commit = $new_release->deployed_commit;

                $this->releases_manifest[] = $release;
                $this->save_releases_manifest();
            }
        } catch (Exception $e) {
            echo $e->getMessage();

            exit(1);
        }

        //
        // Delete old deploys
        //
        // This is a bit hacky. I would like to use the data from the releases manifest for this, but the moving around
        // of directories on -f kinda screws that up. It needs to be made better, and then we can do this properly.
        //

        $releases = glob(call_user_func($this->realpath, "{$this->releases_dir}").'/*', GLOB_ONLYDIR);
        uasort($releases, function ($a, $b) { return filemtime($b) - filemtime($a); });

        foreach (array_slice($releases, $keep) as $dir) {
            $this->recurse_rmdir($dir);
        }

        return \Result\Result::ok();
    }

    protected function load_releases_manifest()
    {
        $releases_manifest_file = "{$this->deploy_dir}/releases/manifest.json";

        if (!file_exists($releases_manifest_file)) {
            $this->releases_manifest = array();
        } else {
            $this->releases_manifest = json_decode(file_get_contents($releases_manifest_file));
        }

        // TODO: handle invalid json properly
        // http://www.php.net/manual/en/function.json-last-error.php
        if (!is_array($this->releases_manifest)) {
            echo 'Unable to parse releases manifest';
            exit(1);
        }
    }

    protected function save_releases_manifest()
    {
        return file_put_contents("{$this->deploy_dir}/releases/manifest.json", json_encode($this->releases_manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
