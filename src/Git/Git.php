<?php

namespace Dxw\Whippet\Git;

/**
 * This class is a container for git commands. It would be very nice to replace it with
 * a proper library for interacting with git repos but we couldn't find one.
 *
 * Most of the methods on this class correspond to a git command.
 *
 * Most of these methods will return false on failure, and true (or some data) on success.
 *
 * TODO
 * This is all pretty hacky. This file is a good candidate for some serious refactoring or replacement.
 * In particular, it's very noisy, and the failure mechanisms make it hard to give users good errors.
 **/
class Git
{
    public function __construct($repo_path)
    {
        $this->repo_path = $repo_path;
    }

    public static function init($dir)
    {
        $output = array();
        $return = '';

        exec("git init {$dir}", $output, $return);

        return array($output, $return);
    }

    public function is_repo()
    {
        return file_exists("{$this->repo_path}/.git");
    }

    public function checkout($revision)
    {
        list($output, $return) = $this->run_command("git fetch -a && git checkout {$revision}");

        return $this->check_git_return('Checkout failed', $return, $output);
    }

    public function hard_reset($revision = 'HEAD')
    {
        list($output, $return) = $this->run_command("git reset --hard {$revision}");

        return $this->check_git_return('Reset --hard failed', $return, $output);
    }

    public function mixed_reset($revision = 'HEAD')
    {
        list($output, $return) = $this->run_command("git reset --mixed {$revision}");

        return $this->check_git_return('Reset --mixed failed', $return, $output);
    }

    public function clone_repo($repository)
    {
        list($output, $return) = $this->run_command("git clone {$repository} {$this->repo_path}", false);

        if (!$this->check_git_return('Clone failed', $return, $output)) {
            return false;
        }

        return true;
    }

    public function clone_no_checkout($repository)
    {
        $tmpdir = $this->get_tmpdir();

        list($output, $return) = $this->run_command("git clone --no-checkout {$repository} {$tmpdir}", false);

        if (!$this->check_git_return('No-checkout clone failed', $return, $output)) {
            return false;
        }

        $this->run_command("mv {$tmpdir}/.git {$this->repo_path}");

        return true;
    }

    public function submodule_update()
    {
        list($output, $return) = $this->run_command('git submodule update --init --recursive');

        if (!$this->check_git_return('submodule update failed', $return, $output)) {
            return false;
        }

        return true;
    }

    public function submodule_status()
    {
        list($output, $return) = $this->run_command('git submodule status');

        if (!$this->check_git_return('submodule status failed', $return, $output)) {
            return false;
        }

        $submodules = array();

        foreach ($output as $line) {
            if (preg_match('/(\+?U?-?)([a-z0-9]{40}) ([^\(]+)([^\)]*)/', trim($line), $matches)) {
                $submodule = new \stdClass();
                $submodule->status = trim($matches[1]);
                $submodule->commit = trim($matches[2]);
                $submodule->dir = trim($matches[3]);
                $submodule->description = preg_replace('/^[\s\(]*/', '', $matches[4]);
                $submodule->remotes = (new self("{$this->repo_path}/{$submodule->dir}"))->get_remotes();

                $submodules[$submodule->dir] = $submodule;
            } else {
                echo "Failed to parse: {$line}\n";

                return false;
            }
        }

        return $submodules;
    }

    public function submodule_add($repo, $path)
    {
        list($output, $return) = $this->run_command("git submodule add {$repo} {$path}");

        if (!$this->check_git_return('submodule status failed', $return, $output)) {
            return false;
        }

        return true;
    }

    public function delete_repo()
    {
        $this->run_command("rm -rf {$this->repo_path}", false);
    }

    public function current_commit()
    {
        list($output, $return) = $this->run_command('git rev-parse HEAD');

        if (!$this->check_git_return('Checkout failed', $return, $output)) {
            return false;
        }

        return $output[0];
    }

    public function local_revision_commit($revision)
    {
        list($output, $return) = $this->run_command('git show-ref');

        if (!$this->check_git_return('show-ref failed', $return, $output)) {
            return false;
        }

        foreach ($this->parse_ref_list($output) as $ref) {
            if ($ref->name == $revision) {
                return $ref->commit;
            }
        }

        return false;
    }

    public function get_remotes()
    {
        list($output, $return) = $this->run_command('git remote -v');

        if (!$this->check_git_return('git remote failed', $return, $output)) {
            return false;
        }

        $remotes = array();

        foreach ($output as $line) {
            if (preg_match('/^([^\s]+)\s+([^\s]+)/', trim($line), $matches)) {
                $remotes[$matches[1]] = $matches[2];
            } else {
                echo "Failed to parse: {$line}\n";

                return false;
            }
        }

        return $remotes;
    }

    public function remote_revision_commit($revision)
    {
        list($output, $return) = $this->run_command('git ls-remote');

        if (!$this->check_git_return('ls-remote failed', $return, $output)) {
            return false;
        }

        foreach ($this->parse_ref_list($output) as $ref) {
            if ($ref->name == $revision) {
                return $ref->commit;
            }
        }

        return $this->parse_ref_list($output);
    }

    public function fetch()
    {
        list($output, $return) = $this->run_command('git fetch -a');

        return $this->check_git_return('Checkout failed', $return, $output);
    }

    public function rm($path, $rf = false)
    {
        list($output, $return) = $this->run_command('git rm '.($rf ? '-rf' : '')." {$path}");

        return $this->check_git_return('rm failed', $return, $output);
    }

    public function add($path)
    {
        list($output, $return) = $this->run_command("git add {$path}");

        return $this->check_git_return('Add failed', $return, $output);
    }

    public function commit($message)
    {
        list($output, $return) = $this->run_command("git commit -m '{$message}'");

        return $this->check_git_return('Checkout failed', $return, $output);
    }

    protected function parse_ref_list($reflist)
    {
        $refs = array();
        foreach ($reflist as $line) {
            if (preg_match("/^([a-z0-9]{40})\s+(.+)$/", $line, $matches)) {
                $ref = new \stdClass();

                $ref->commit = $matches[1];

                if (preg_match('#^refs/(tags|heads)/(.+)$#', $matches[2], $matches)) {
                    $ref->tag = $matches[1] == 'tags';
                    $ref->branch = $matches[1] == 'branch';
                    $ref->name = $matches[2];

                    $refs[] = $ref;
                }
            }
        }

        return $refs;
    }

    /**
     * This function checks to see whether git successfully ran. If so,
     * it returns true. If not, it prints the supplied message along with
     * git's output and returns false.
     *
     * $message An error message to display on failure
     * $return Git's return code, as set by exec()
     * $output Git's output code, as set by exec()
     */
    protected function check_git_return($message, $return, $output)
    {
        if ($return !== 0) {
            echo "{$message}:\n\n".implode("\n", $output);

            return false;
        }

        return true;
    }

    /**
     * Runs the specified git command, with some basic sanity checking to
     * ensure that required repos and directories exist.
     *
     * $command The command to be run
     * $cd If true, Whippet will change its working directory to repo_path before executing $command
     *
     * See also: this::__construct.
     */
    protected function run_command($command, $cd = true)
    {
        $output = array();
        $return = 0;

        if ($cd && !file_exists($this->repo_path)) {
            echo "Error: directory does not exist ({$this->repo_path})\n";
            exit(1);
        }

        if ($cd) {
            $cd = "cd {$this->repo_path} && ";
        } else {
            $cd = '';
        }

        exec("{$cd}{$command}", $output, $return);
        // echo ("{$cd}{$command}\n");

        return array($output, $return);
    }

    /**
     * Obtains a valid directory for temporary files on the current system, or in a specified location.
     *
     * $in_dir If supplied, the temporary directory will be created as a subdirectory of this path. If false or missing, the system's default temporary file location will be used.
     */
    public function get_tmpdir($in_dir = false)
    {
        if (!$in_dir) {
            $in_dir = sys_get_temp_dir();
        }

        do {
            $tmp_dir = $in_dir.'/'.md5(microtime());
        } while (file_exists($tmp_dir));

        return $tmp_dir;
    }

    public static function ls_remote($repo, $ref)
    {
        exec(sprintf('git ls-remote %s %s', escapeshellarg($repo), escapeshellarg($ref)), $output, $return);

        if ($return !== 0) {
            return \Result\Result::err('git error');
        }

        if (count($output) === 0) {
            return \Result\Result::err('ref not found');
        }

        return \Result\Result::ok(explode("\t", $output[0])[0]);
    }
};
