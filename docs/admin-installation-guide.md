# Zoom Classroom TinyMCE Plugin Admin Installation Guide

## Overview

The `tiny_zoomclassroom` plugin adds a Zoom Classroom button to Moodle's TinyMCE editor and launches a preconfigured **LTI 1.3 deep-linking** tool.

This plugin does **not** register an LTI tool by itself. A Moodle administrator must already have a working **External tool** registration for Zoom Classroom.

For each selected deep-linked asset, the plugin creates or reuses a **hidden Moodle External tool activity** behind the scenes so Moodle can perform the normal **LTI 1.3 OIDC launch flow**.

## Requirements

- Moodle with the TinyMCE editor enabled
- A preconfigured **LTI 1.3** external tool for Zoom Classroom
- The external tool must support **LTI Deep Linking**
- Administrator access to install local plugins
- A ZIP extraction method or Git checkout process for moving plugin files into Moodle

## Supported scope

- Supports **LTI 1.3 only**
- Does **not** support LTI 1.1
- Designed to work with an admin-selected Zoom Classroom tool registration

## Installation

1. Log in to Moodle as an administrator.
2. Open:

   **Site administration → Plugins → Install plugins**

3. Upload the release ZIP:

   ```text
   tiny_zoomclassroom.zip
   ```

4. Continue the installation flow.

5. Open:

   **Site administration → Notifications**

6. Complete the plugin upgrade prompt.

7. Confirm the plugin appears in:

   **Site administration → Plugins → Text editors → TinyMCE editor → Zoom Classroom**

## Configuration

1. Open:

   **Site administration → Plugins → Text editors → TinyMCE editor → Zoom Classroom**

2. In **Registered LTI tool**, choose the Zoom Classroom external tool registration.

   Notes:
   - Only eligible **LTI 1.3** tools should be selected
   - The selected tool must support **deep linking**

3. Optionally adjust:
   - **Popup width**
   - **Popup height**

4. Save changes.

## Recommended Moodle checks

Before giving this to teachers, verify:

- The selected external tool launches successfully in Moodle
- Dynamic registration or manual registration for the tool is already complete
- The tool's **deep-linking target URL** is configured correctly on the tool side
- The Moodle site URL is externally reachable if the tool must call back to Moodle
- Popups are allowed in the browser for your Moodle site

## Permissions and visibility

The Zoom Classroom TinyMCE button is intended for users who can add or manage course content through Moodle's LTI deep-linking flow.

If the button does not appear, check:

- The plugin is configured with a valid tool
- The user is editing in a real course context
- The user has permission to use the configured deep-link workflow in that course

## Smoke test

Use this quick validation after installation:

1. Open a course as a teacher or admin.
2. Edit an activity or resource that uses TinyMCE.
3. Confirm the **Zoom Classroom** button appears in the editor.
4. Click the button.
5. Confirm a popup opens to the Zoom Classroom deep-link picker.
6. Select content in Zoom Classroom.
7. Confirm the popup closes.
8. Confirm content is inserted back into the TinyMCE editor.
9. Confirm the inserted content launches through Moodle's standard LTI 1.3 flow when rendered.

## Troubleshooting

### Button does not appear

Check:

- Plugin is installed under the correct folder
- Moodle upgrade completed successfully
- TinyMCE is the active editor
- A valid Zoom Classroom LTI 1.3 tool is selected in plugin settings
- The editor is being used inside a course context

### Popup opens but launch fails

Check:

- The selected external tool is the correct Zoom Classroom registration
- The tool supports **LTI 1.3 deep linking**
- The tool's OIDC login initiation and redirect URIs are configured correctly
- The Moodle site URL is reachable from the tool

### Deep-link picker opens but returned content fails

Check:

- The tool is returning a valid **LtiDeepLinkingResponse**
- The response is being posted back to Moodle successfully
- Browser popup/cookie restrictions are not interfering with the return flow
- The Moodle user has permission to create/use the backing hidden External tool instance

### Deep-link selection succeeds but launched content does not open correctly

Check:

- The inserted iframe is pointing to Moodle's `/mod/lti/launch.php?id=...` URL
- Moodle is able to complete the standard LTI 1.3 OIDC launch flow
- The tool's initiate-login, auth completion, and target link URI chain is valid
- The selected deep-linked item returned a launchable resource configuration

## Upgrade notes

When updating the plugin:

1. Replace the plugin code in the same folder.
2. Visit **Site administration → Notifications**.
3. Complete the upgrade.
4. Purge Moodle caches if needed.

## Security notes

- This plugin assumes the configured external tool is trusted by the Moodle administrator
- The plugin is intentionally scoped to **LTI 1.3 deep linking only**
- Tool registration and trust decisions remain an administrator responsibility
- The plugin creates hidden backing `mod_lti` instances so launches go through Moodle's standard OIDC flow
