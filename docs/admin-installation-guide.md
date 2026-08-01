# Zoom Classroom TinyMCE Plugin Admin Installation Guide

## Overview

The `tiny_zoomclassroom` plugin adds a Zoom Classroom button to Moodle's TinyMCE editor and launches a Moodle-registered **LTI 1.3 deep-linking** tool.

The plugin:

- uses a plugin-owned dynamic registration flow
- reuses Moodle's issuer, token, signing, and JWKS infrastructure
- stores selected launch metadata server-side
- stores only an opaque embed reference in saved TinyMCE HTML
- avoids creating duplicate Moodle `mod_lti` activities for embedded resources

## Requirements

- Moodle with the TinyMCE editor enabled
- A Zoom Classroom dynamic registration URL or an existing **LTI 1.3** external tool for Zoom Classroom
- The external tool must support **LTI Deep Linking**
- Administrator access to install local plugins

## Supported scope

- Supports **LTI 1.3 only**
- Does **not** support LTI 1.1
- Designed to work with one admin-selected Zoom Classroom tool registration per site

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

2. Paste the Zoom Classroom dynamic registration URL.
3. Start the registration flow.
4. Complete the tool-side registration flow.
5. Return to the plugin page.
6. Confirm the configured Moodle tool is shown.
7. If needed, use the plugin page to:
   - replace the configured tool
   - select an existing eligible tool
   - clear the configured tool
   - delete the configured tool
8. Optionally adjust:
   - popup width
   - popup height
   - allowed launch domains

## Recommended Moodle checks

Before giving this to teachers or students, verify:

- the configured external tool exists in Moodle
- the tool is **LTI 1.3**
- the tool supports **deep linking**
- the plugin OpenID configuration endpoint is reachable by the tool
- Moodle's token and JWKS endpoints are reachable by the tool
- the Moodle site URL is externally reachable if the tool must call back to Moodle
- popups are allowed in the browser for your Moodle site

## Permissions and visibility

The Zoom Classroom button is intended for users who can edit TinyMCE content in a real course context and who can successfully complete the configured deep-link flow.

If the button does not appear, check:

- the plugin is configured with a valid tool
- the user is editing inside a real course context
- the page is using TinyMCE
- the user is not a guest
- Moodle role configuration allows the user to edit that content area

## Smoke test

Use this quick validation after installation:

1. Open a course as a teacher or admin.
2. Edit an activity, page, label, or other item that uses TinyMCE.
3. Confirm the **Zoom Classroom** button appears in the editor.
4. Click the button.
5. Confirm a popup opens to the Zoom Classroom deep-link picker.
6. Select content in Zoom Classroom.
7. Confirm the popup closes.
8. Confirm content is inserted back into the TinyMCE editor.
9. Save the Moodle page.
10. Confirm the content renders through an LTI 1.3 launch when viewed later.

## Troubleshooting

### Button does not appear

Check:

- plugin is installed in the correct folder
- Moodle upgrade completed successfully
- TinyMCE is the active editor
- a valid Zoom Classroom LTI 1.3 tool is configured
- the editor is being used inside a course context

### Popup opens but launch fails

Check:

- the configured external tool is the correct Zoom Classroom registration
- the tool supports **LTI 1.3 deep linking**
- the tool's OIDC login initiation and redirect URIs are configured correctly
- the Moodle site URL is reachable from the tool

### Deep-link picker opens but returned content fails

Check:

- the tool is returning a valid **LtiDeepLinkingResponse**
- browser popup and cookie restrictions are not interfering with the return flow
- the returned launch URL is on an allowed domain

### Deep-link selection succeeds but rendered content does not open

Check:

- the configured tool is still active and valid
- the saved placeholder resolves to plugin `view.php`
- Moodle can complete the plugin-owned LTI 1.3 OIDC launch flow
- the selected deep-linked item returned a launchable resource configuration

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
- Moodle remains the LTI platform identity for issuer, token, and JWKS
- The plugin stores launch metadata in its own database table and keeps only an opaque embed ID in saved editor HTML
- The plugin does not create backing `mod_lti` instances for embedded launches
