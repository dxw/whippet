<?php

namespace Dxw\Whippet\Modules;

class Release
{
    use Helpers\WhippetHelpers;
    use Helpers\ManifestIo;

    public $number = 0;
    public $time = 0;

    public function __construct($releases_dir, $message, $number)
    {
        $this->whippet_init();
        $this->load_plugins_lock();

        $git = new \Dxw\Whippet\Git\Git($this->project_dir);

        $this->number = $number;
        $this->time = date('r');
        $this->deployed_commit = $git->current_commit();
        $this->release_dir = "{$releases_dir}/{$this->deployed_commit}";
    }

    public function create(&$force)
    {
        //
        // Does this commit have a release directory already? If so, do nothing
        //

        if (!$force && file_exists($this->release_dir)) {
            return false;
        }

        // there's no point in forcing a non-existant release
        if ($force && !file_exists($this->release_dir)) {
            $force = false;
        }

        // Got whippet.{json,lock} or plugins.lock?
        if (is_file($this->project_dir.'/whippet.json') && is_file($this->project_dir.'/whippet.lock')) {
            $factory = new \Dxw\Whippet\Factory();
            $installer = new \Dxw\Whippet\Dependencies\Installer(
                $factory,
                new \Dxw\Whippet\ProjectDirectory($this->project_dir)
            );
        } elseif ($this->plugins_lock_file && file_exists($this->plugins_lock_file)) {
            $installer = new Plugin();
        } else {
            echo "Couldn't find plugins.lock in the project directory. (Did you run whippet plugins install?)\n";
            die(1);
        }

        //
        // If we're here, we must deploy
        //

        //    1. Clone WP
        //    2. Delete wp-content etc
        //    3. Make sure wp-content is up to date
        //    4. Copy our wp-content, omitting gitfoo
        //    5. ?? Theme/plugin build steps ?? (Makefile-esque thing?)
        //    6. Symlink required files from shared dir

        // Assuming we're not forcing, create a new directory for this release, or use only an empty existing dir
        if (!$force) {
            $this->check_and_create_dir($this->release_dir, true);
        } else {
            $this->release_dir = dirname($this->release_dir).'/forced_release_tmp_'.sha1(microtime());
        }

        // Clone WP and remove things we don't want
        $wp = new \Dxw\Whippet\Git\Git($this->release_dir);
        $wp->clone_repo($this->application_config->wordpress->repository);
        $wp->checkout($this->application_config->wordpress->revision);

        foreach (['wp-content', '.git', 'readme.html', 'wp-config-sample.php'] as $delete) {
            if (is_dir("{$this->release_dir}/$delete")) {
                $this->recurse_rmdir("{$this->release_dir}/$delete");
            } else {
                unlink("{$this->release_dir}/$delete");
            }
        }

        // Make sure wp-content is up to date
        $result = $installer->installAll();
        if ($result->isErr()) {
            echo sprintf("ERROR: %s\n", $result->getErr());
            exit(1);
        }

        // Copy over wp-content
        $this->recurse_copy("{$this->project_dir}/wp-content", "{$this->release_dir}/wp-content");

        if (file_exists("{$this->release_dir}/wp-content/uploads")) {
            $this->recurse_rm("{$this->release_dir}/wp-content/uploads");
        }

        //
        // Remove unwanted git/test foo
        //

        $plugins = scandir("{$this->release_dir}/wp-content/plugins");
        foreach ($plugins as $dir) {
            $path = "{$this->release_dir}/wp-content/plugins/{$dir}";
            if ($dir === '.' || $dir === '..' || !is_dir($path)) {
                continue;
            }

            // Remove git files from all plugins

            foreach (['.git', '.gitmodules', '.gitignore'] as $delete) {
                $this->recurse_rm("{$this->release_dir}/wp-content/plugins/$dir/{$delete}");
            }

            // Remove test files from whippet plugins

            if ($this->is_whippet_plugin($path)) {
                foreach (['tests', 'Makefile', '.drone.yml'] as $delete) {
                    $this->recurse_rm("{$this->release_dir}/wp-content/plugins/$dir/{$delete}");
                }
            }
        }

        //
        // Copy public assets
        //
        if (is_dir("{$this->project_dir}/public")) {
            $this->recurse_copy("{$this->project_dir}/public", "{$this->release_dir}");
        }

        //
        // TODO: theme and plugin build steps
        //

        // Symlinkery
        symlink(realpath("{$this->release_dir}/../../shared/wp-config.php"), "{$this->release_dir}/wp-config.php");
        symlink(realpath("{$this->release_dir}/../../shared/uploads"), "{$this->release_dir}/wp-content/uploads");

        // FIN
    }

    public function is_whippet_plugin($path)
    {
        $files = glob($path.'/*.php');
        foreach ($files as $file) {
            if (is_file($file)) {
                //TODO: This is probably okay in most cases but if we come across a 1GB .php file PHP might run out of memory
                $f = file_get_contents($file);
                if (strpos($f, 'Whippet: yes') !== false) {
                    return true;
                }
            }
        }

        return false;
    }
};
