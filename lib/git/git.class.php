<?php

class Git {
  function __construct($repo_path) {
    $this->repo_path = $repo_path;
  }

  function is_repo() {
    return file_exists("{$this->repo_path}/.git");
  }

  function checkout($revision) {
    list($output, $return) = $this->run_command("git fetch -a && git checkout {$revision}");

    return $this->check_git_return("Checkout failed", $return, $output);
  }

  function clone_repo($repository) {
    list($output, $return) = $this->run_command("git clone {$repository} {$this->repo_path}", false);

    if(!$this->check_git_return("Clone failed", $return, $output)) {
      return false;
    }

    return true;
  }

  function delete_repo() {
    $this->run_command("rm -rf {$this->repo_path}", false);
  }

  function current_commit() {
    list($output, $return) = $this->run_command("git rev-parse HEAD");

    if(!$this->check_git_return("Checkout failed", $return, $output)) {
      return false;
    }

    return $output[0];
  }

  function local_revision_commit($revision) {
    list($output, $return) = $this->run_command("git show-ref");

    foreach($this->parse_ref_list($output) as $ref) {
      if($ref->name == $revision) {
        return $ref->commit;
      }
    }

    return false;
  }

  function remote_revision_commit($revision) {
    list($output, $return) = $this->run_command("git ls-remote");

    foreach($this->parse_ref_list($output) as $ref) {
      if($ref->name == $revision) {
        return $ref->commit;
      }
    }

    return $this->parse_ref_list($output);
  }

  protected function parse_ref_list($reflist) {
    $refs = array();
    foreach($reflist as $line) {
      if(preg_match("/^([a-z0-9]{40})\s+(.+)$/", $line, $matches)) {
        $ref = new stdClass();

        $ref->commit = $matches[1];

        if(preg_match("#^refs/(tags|heads)/(.+)$#", $matches[2], $matches)) {
          $ref->tag = $matches[1] == 'tags';
          $ref->branch = $matches[1] == 'branch';
          $ref->name = $matches[2];

          $refs[] = $ref;
        }
      }
    }

    return $refs;
  }

  function fetch() {
    list($output, $return) = $this->run_command("git fetch -a");

    return $this->check_git_return("Checkout failed", $return, $output);
  }

  protected function check_git_return($message, $return, $output) {
    if($return !== 0) {
      echo "{$message}:\n\n" . implode("\n", $output);

      return false;
    }

    return true;
  }

  protected function run_command($command, $cd = true) {
    $output = array();
    $return = 0;

    if($cd) {
      $cd = "cd {$this->repo_path} && ";
    }
    else {
      $cd = '';
    }

    exec("{$cd}{$command}", $output, $return);
    // echo ("{$cd}{$command}\n");

    return array($output, $return);
  }
};