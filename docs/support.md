# Support

This document is aimed at dxw developers who will encounter Whippet in a support context.

You are unlikely to need to provide support for the Whippet application directly whilst on the support rota. 

However, you may well need to use it as part of supporting WordPress sites we host. If so, you should start by installing Whippet so you can run the `whippet` command from your terminal, as per the instructions in the main [README](../README.md).

## Common tasks 

### Updating plugins & themes via Whippet 

Run `whippet deps update` in the directory that contains the `whippet.lock` file. 

Commit the updated `whippet.lock`.

### Adding a new plugin via Whippet 

Manually edit the `whippet.json` file to add an entry to the "plugins" section for the new plugin. e.g. if your existing `whippet.json` file looked like this:

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

And you want to add a plugin called "Foo", you would edit it to look like this:

```
{
    "src": {
        "plugins": "git@git.govpress.com:wordpress-plugins/",
        "themes": "git@git.govpress.com:wordpress-themes/"
    },
    "plugins": [
        {"name": "akismet"},
        {"name": "foo"}
    ],
    "themes": [
        {"name": "twentyfourteen"},
        {"name": "twentysixteen"},
        {"name": "twentyten"}
    ]
}
```

Then run `whippet deps update`. 

Commit the updated `whippet.json`, `whippet.lock`, and `.gitignore`.

### Removing a plugin 

Manually edit the `whippet.json` file to remove the entry for the plugin you want removed.

Run `whippet deps update`. 

Commit the updated `whippet.json`, `whippet.lock` and `.gitignore`.

Note: plugin removal can be buggy. You should double check the `whippet.lock` and `.gitignore` files to ensure that the entry for the deleted plugin has been removed, and manually update those files if it has not.

### Resolving mismatched hash errors

Occasionally you may encounter a "mismatched hash" error when running `whippet deps install`. This indicates that the entries in the `whippet.json` and `whippet.lock` files are out of alignment (e.g. one lists a plugin that isn't in the other). 

You can resolve this by running `whippet deps update` to regenerate the `whippet.lock` file.
