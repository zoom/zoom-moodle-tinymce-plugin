# `tiny_zoomclassroom`

Moodle TinyMCE plugin that adds a **Zoom Classroom** editor button and launches a Moodle-registered **LTI 1.3 deep-linking** tool.

## What it does

- Adds a TinyMCE toolbar button and Insert menu item
- Lets a Moodle admin register or update the Zoom Classroom tool through the plugin-owned dynamic registration flow
- Reuses Moodle's LTI platform identity, signing helpers, token endpoint, and JWKS endpoint
- Opens Moodle's normal deep-link selection flow in a popup
- Stores selected resource launch metadata server-side in the plugin database
- Inserts a TinyMCE placeholder that contains only an opaque embed ID, not the launch metadata itself
- Renders saved content through plugin-owned `view.php` and `auth.php` endpoints without creating backing `mod_lti` activities

## Install

1. In Moodle, go to **Site administration → Plugins → Install plugins**
2. Upload `dist/tiny_zoomclassroom.zip`
3. Continue the installation flow
4. Visit **Site administration → Notifications** if prompted
5. Complete the plugin upgrade

## Configure

1. Go to **Site administration → Plugins → Text editors → TinyMCE editor → Zoom Classroom**
2. Paste the Zoom Classroom dynamic registration URL and start registration
3. Complete the tool-side registration flow
4. Return to the plugin page and confirm the configured Moodle tool is shown
5. If needed, replace or clear the configured tool from the same page
6. Adjust popup width, popup height, and allowed launch domains if needed

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
- `db/`
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

## Compatibility

- Requires the TinyMCE editor
- Supports **LTI 1.3 deep linking only**
- Does **not** support LTI 1.1

## Storage model

The plugin stores selected embed launch metadata in its own database table:

- tool ID
- course ID
- title
- normalized launch configuration
- timestamps

Saved TinyMCE HTML stores only:

- an opaque embed ID in `data-embed-id`
- a placeholder image URL pointing to plugin `placeholder.php`

The saved HTML does **not** store raw launch metadata, client secrets, or private keys.

## Security and trust model

- The Moodle administrator selects and trusts the backing external tool registration
- Moodle remains the LTI platform identity for issuer, token, and JWKS
- The plugin owns the TinyMCE-specific OpenID configuration, registration, and authorization endpoints
- Launch URLs must use `https` and match an administrator-managed allowlist of approved domains
- Rendered launches require an authenticated Moodle session and course access at runtime
- The final `view.php` → `auth.php` handoff uses short-lived server-side session state to reduce replay risk

## Support boundaries

- This repository contains the Moodle TinyMCE plugin only
- It does not include the Zoom Classroom LTI tool implementation
- Institutions remain responsible for their Moodle deployment, external tool configuration, and browser policy settings

## Development notes

- `dist/` is a generated release artifact and should not be committed
- This repository does not require Node.js or Composer to package a release zip
- For public releases, use `./ci/build.sh` to generate a clean installable artifact
