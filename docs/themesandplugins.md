# Managing themes and plugins

Note: At the moment, Whippet assumes it is running within dxw's infrastructure, and makes some assumptions accordingly. If you run into a problem where this may be the cause, please open an issue.

To manage plugins and themes using Whippet, you make entries in the `whippet.json` file in the root of the application.

The file should specify a source for plugins and themes. The source should be a base url for a git repo.

If you are a dxw customer, the source will be `git@git.govpress.com:wordpress-plugins/`. If not, we suggest using `https://github.com/wp-plugins` for plugins.

The rest of the file should specify plugins and themes that you want to install. Example:

```
{
    "src": {
        "plugins": "git@git.govpress.com:wordpress-plugins/",
        "themes": "git@git.govpress.com:wordpress-themes/"
    },
    "plugins": [
        {"name": "akismet"}
    ],
    "themes": [
        {"name": "twentyfourteen"},
        {"name": "twentysixteen"},
        {"name": "twentyten"}
    ]
}
```

The `{"name": "akismet"}` instructs Whippet (on request) to install the most recent version of Akismet available in the repo. Whippet will determine a valid repo URL for the akismet plugin by appending the name to the source. In this example:

```
git@git.govpress.com:wordpress-plugins/akismet
```

You can also specify a particular label or branch that you want to use. Generally, this will either be master (the default) or a tag (for a specific version), but you can use any git reference. So you can do:

```
{
    "name": "akismet",
    "ref": "v1.1"
}
```

Which will cause Whippet to install the plugin at the commit with that tag or branch.

Finally, you can also specify a repo for an individual plugin or theme explicitly:

- Pull version 3.0.0 from your own special repo:

```
{
    "name": "akismet",
    "ref": "v3.0.0",
    "src": "git@my-git-server.com:akismet"
}
```

- Or, pull master:

```
{
    "name": "akismet",
    "ref": "master",
    "src": "git@my-git-server.com:akismet"
}
```

- This works too:

```
{
    "name": "akismet",
    "src": "git@my-git-server.com:akismet"
}
```

## Commands

### whippet deps update

This command will:

1. Check the commit hash for each ref of each repo specified in `whippet.json`
2. Update `whippet.lock`
3. Update `.gitignore` with the plugins/themes installed, and remove plugins/themes that are removed from `whippet.json`
4. Run `whippet deps install`

### whippet deps update [type]/[name]

e.g. `whippet deps update plugins/twitget`

This will:

1. Check the commit hash for the ref of the specified repo, provided it is in `whippet.json`
2. Update that repo in `whippet.lock`
3. Update `.gitignore` if the repo was not previously installed
4. Install the repo at the specified ref

### whippet deps install

This command will run through the items in `whippet.lock` and clone any missing plugins/themes, or fetch and checkout.

### whippet deps validate

Will check that `whippet.json` and `whippet.lock` are well-formed and aligned with one another, i.e.:

1. Both files are valid JSON
1. The hash in `whippet.lock` is as expected from the contents of `whippet.json`
1. There are the same number of dependencies listed in each file
1. Each dependency in `whippet.json` has a corresponding entry in `whippet.lock`
1. Each dependency in `whippet.lock` is well-formed, with a name, src and revision

## Checking for inspections

Both the `install` and `update` commands will both attempt to check that a
plugin has had a security inspection by checking the API on
https://advisories.dxw.com/

This API is only available to dxw employees, since it contains privately
published inspections which for various reasons cannot be published. To
disable these checks pass `-c` when running these commands.

To use a different host for the API (e.g. for development and testing) set an
environment variable, eg.

    export INSPECTIONS_API_HOST=http://localhost:8000
