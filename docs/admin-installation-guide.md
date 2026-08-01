# Zoom Classroom TinyMCE Plugin Admin Installation Guide

## Overview

The `tiny_zoomclassroom` plugin adds a **Zoom Classroom** button to **Moodle 5+** TinyMCE editors and connects that button to a Moodle-registered **LTI 1.3 deep-linking** tool.

The plugin:

- installs as a standard Moodle TinyMCE plugin
- starts dynamic registration from the plugin configuration page
- reuses Moodle's issuer, token, signing, and JWKS infrastructure
- stores selected launch metadata server-side
- stores only an opaque embed reference in saved TinyMCE HTML
- avoids creating duplicate visible `mod_lti` activities for embedded resources

This guide is for Moodle administrators doing first-time installation, configuration, and rollout.

## Requirements

You will need:

- Moodle with the TinyMCE editor enabled
- administrator access to install plugins
- a Zoom Classroom **dynamic registration URL**
- a Zoom Classroom tool that supports **LTI 1.3 Deep Linking**
- a Moodle site URL that Zoom Classroom can reach during registration and launch

## Supported scope

- Supports **Moodle 5+**
- Supports **LTI 1.3 only**
- Does **not** support LTI 1.1
- Designed to use one admin-selected Zoom Classroom tool registration per Moodle site

## Installation

1. Download the latest `tiny_zoomclassroom.zip` artifact from GitHub Releases.
2. Log in to Moodle as an administrator.
3. Open:

   **Site administration → Plugins → Install plugins**

4. Upload:

   ```text
   tiny_zoomclassroom.zip
   ```

5. Continue through the Moodle installation flow.
6. Open **Site administration → Notifications** if prompted.
7. Complete the plugin upgrade.
8. Confirm the plugin appears in:

   **Site administration → Plugins → Text editors → TinyMCE editor → Zoom Classroom**

## Configuration

1. Open:

   **Site administration → Plugins → Text editors → TinyMCE editor → Zoom Classroom**

2. In Zoom Classroom, copy the **dynamic registration URL** for this Moodle integration.
3. Paste the URL into the plugin page.
4. Start dynamic registration.
5. Complete the Zoom Classroom registration flow.
6. Return to the plugin page.
7. Confirm the configured Moodle tool is shown.
8. If needed, use the same page to:
   - update the registration with a new URL
   - select an existing eligible Moodle LTI 1.3 tool
   - clear the configured tool
   - delete the configured Moodle tool
9. Optionally adjust:
   - popup width
   - popup height
   - allowed launch domains

## First-time rollout checklist

Use this sequence for a clean first setup:

1. Install the plugin ZIP.
2. Complete the Moodle upgrade.
3. Open the Zoom Classroom plugin page.
4. Paste the Zoom Classroom dynamic registration URL.
5. Complete registration.
6. Confirm the configured Moodle tool appears on the plugin page.
7. Test the editor button in a real course.
8. Test teacher-authored content insertion.
9. Test student insertion if students should use the button.
10. Test rendering later in normal view mode and grading/review surfaces.

## Recommended Moodle checks

Before giving this to teachers or students, verify:

- the configured external tool exists in Moodle
- the tool is **LTI 1.3**
- the tool supports **deep linking**
- the configured tool shown on the plugin page is the one you intend to use
- the plugin OpenID configuration endpoint is reachable by the tool
- Moodle's token and JWKS endpoints are reachable by the tool
- the Moodle site URL is externally reachable if the tool must call back to Moodle
- popups are allowed in the browser for your Moodle site

## Permissions and visibility

The Zoom Classroom button is intended for users who:

- are editing TinyMCE content
- are working inside a real course context
- are not guests
- can complete the configured deep-link flow

If the button does not appear, check:

- the plugin is configured with a valid tool
- the page is using TinyMCE
- the user is editing inside a real course context
- the user is not a guest
- Moodle role configuration allows the user to edit that content area

## Validation

Use this validation after installation and configuration:

1. Open a real course as a teacher or administrator.
2. Edit an activity, page, label, submission field, or other item that uses TinyMCE.
3. Confirm the **Zoom Classroom** button appears in the editor.
4. Click the button.
5. Confirm a popup opens to the Zoom Classroom deep-link picker.
6. Select content in Zoom Classroom.
7. Confirm the popup closes.
8. Confirm content is inserted back into the TinyMCE editor.
9. Save the Moodle item.
10. Re-open the content in view mode.
11. Confirm the embedded content renders through an LTI 1.3 launch.

If students should use the plugin, also test:

12. A student online-text submission that uses TinyMCE.
13. A teacher grading or review page that displays student-authored embedded content.

## Troubleshooting

### The button does not appear

Check:

- the plugin is installed in the correct folder
- Moodle upgrade completed successfully
- TinyMCE is the active editor
- a valid Zoom Classroom LTI 1.3 tool is configured
- the editor is being used inside a course context

### The popup opens but launch fails

Check:

- the configured external tool is the correct Zoom Classroom registration
- the tool supports **LTI 1.3 deep linking**
- the tool's OIDC login initiation and redirect URIs are configured correctly
- the Moodle site URL is reachable from the tool

### The deep-link picker opens but returned content fails

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

### The plugin page shows no configured tool

Check:

- dynamic registration completed successfully
- the tool-side flow returned to Moodle
- the Moodle external tool was created or updated successfully
- the plugin page was refreshed after registration

## Upgrade notes

When updating the plugin:

1. Replace the plugin code in the same folder.
2. Visit **Site administration → Notifications**.
3. Complete the upgrade.
4. Purge Moodle caches if needed.

## Security notes

- The Moodle administrator decides which external tool registration to trust
- The plugin is intentionally scoped to **LTI 1.3 deep linking only**
- Tool registration and trust decisions remain an administrator responsibility
- Moodle remains the LTI platform identity for issuer, token, and JWKS
- The plugin stores launch metadata in its own database table and keeps only an opaque embed ID in saved editor HTML
- The plugin does not create backing `mod_lti` instances for embedded launches
