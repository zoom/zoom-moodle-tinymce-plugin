# `tiny_zoomclassroom`

Moodle TinyMCE plugin that adds a **Zoom Classroom** editor button and launches an already-registered **LTI 1.3 deep-linking** tool.

## What it does

- Adds a TinyMCE toolbar button and Insert menu item
- Lets a Moodle admin map that button to an existing Moodle external tool
- Opens a popup that starts Moodle's deep-link selection flow
- Creates or reuses a hidden backing Moodle `mod_lti` instance for each selected asset
- Inserts an iframe into TinyMCE pointing at Moodle's standard `/mod/lti/launch.php?id=<cmid>` launch path
- Lets Moodle perform the full LTI 1.3 OIDC launch flow for rendered content

## Install

1. In Moodle, go to **Site administration → Plugins → Install plugins**
2. Upload `dist/tiny_zoomclassroom.zip`
3. Continue the installation flow
4. Visit **Site administration → Notifications** if prompted
5. Complete the plugin upgrade

## Configure

1. Go to **Site administration → Plugins → Text editors → TinyMCE editor → Zoom Classroom**
2. Choose the existing Moodle **External tool** registration for Zoom Classroom
3. Save

## Build release zip

Build the customer-installable plugin zip with:

```bash
./ci/build.sh
```

This creates:

- `dist/tiny_zoomclassroom.zip`

The release zip includes only installable plugin files:

- PHP plugin files
- `classes/`
- `lang/`
- `pix/`
- compiled JavaScript in `amd/build/`
- `LICENSE`
- `README.md`

It intentionally excludes repository-only content such as:

- `.git/`
- `docs/`
- source JavaScript in `amd/src/`
- `ci/`
- any pre-existing `dist/` contents

## Assumptions

- The selected external tool is already configured in Moodle
- The selected external tool supports **LTI 1.3 deep linking**
- The tool returns launchable deep-link content that Moodle can convert into a usable LTI resource configuration

## Compatibility

- Requires Moodle `4.1+` based on `version.php`
- Tested in a Moodle `4.5.x` development environment
- Requires the TinyMCE editor

## Supported LTI version

- This plugin supports **LTI 1.3 deep linking only**
- LTI 1.1 tools are intentionally not supported

## Current scope

Current implementation:

- supports site-level mapping to one registered LTI tool
- uses a popup launcher rather than a custom modal
- creates hidden backing `mod_lti` instances for selected assets
- launches rendered content through Moodle's standard LTI 1.3 OIDC flow

You may want follow-up work for:

- lifecycle management for hidden backing `mod_lti` instances
- tighter validation that the chosen tool and returned content are launchable
- a better admin selector UI
- automated tests in a Moodle dev environment

## Security and trust model

- The Moodle administrator selects and trusts the backing external tool registration
- The plugin supports **LTI 1.3 only** and intentionally does not support LTI 1.1
- Deep-link rendering is launched through Moodle core instead of directly iframing the tool target URL
- Tool-returned launch metadata, including custom parameters, may be stored in Moodle core `mod_lti` records

## Support boundaries

- This repository contains the Moodle TinyMCE plugin only
- It does not include the Zoom Classroom LTI tool implementation
- Institutions remain responsible for their Moodle deployment, external tool configuration, and browser policy settings

## Development notes

- `dist/` is a generated release artifact and should not be committed
- This repository does not require Node.js or Composer to package a release zip
- For public releases, use `./ci/build.sh` to generate a clean installable artifact
