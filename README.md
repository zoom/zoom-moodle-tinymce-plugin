# `tiny_zoomclassroom`

`tiny_zoomclassroom` is a **Moodle 5+** TinyMCE plugin that adds a **Zoom Classroom** button to the editor and lets users insert **LTI 1.3 deep-linked** Zoom Classroom content into Moodle text areas.

It is designed for Moodle administrators who want to:

- install a customer-ready plugin ZIP into Moodle
- register Zoom Classroom through the plugin's dynamic registration flow
- let teachers and students insert Zoom Classroom content from TinyMCE
- render that content later through a normal authenticated **LTI 1.3** launch

## What this plugin does

- Adds a TinyMCE toolbar button and Insert menu item
- Starts Zoom Classroom deep linking from Moodle text areas
- Uses a plugin-owned dynamic registration flow to create or update the Moodle external tool
- Reuses Moodle's LTI platform identity, signing helpers, token endpoint, and JWKS endpoint
- Stores selected launch metadata server-side in the plugin database
- Stores only an opaque embed reference in saved Moodle HTML
- Renders embedded content later without creating duplicate visible `mod_lti` activities

## Before you start

You will need:

- a Moodle site with the **TinyMCE** editor enabled
- Moodle administrator access
- a Zoom Classroom **dynamic registration URL**
- a Moodle site URL that Zoom Classroom can reach during registration and launch

This plugin supports:

- **Moodle 5+**
- **LTI 1.3 deep linking only**

This plugin does **not** support:

- LTI 1.1

## Quick start

If you are installing this from GitHub, the normal path is:

1. Download the latest plugin ZIP from the latest GitHub release.
2. In Moodle, go to **Site administration → Plugins → Install plugins**.
3. Upload `tiny_zoomclassroom.zip`.
4. Complete the Moodle installation and upgrade flow.
5. Open **Site administration → Plugins → Text editors → TinyMCE editor → Zoom Classroom**.
6. Copy the **dynamic registration URL** from Zoom Classroom.
7. Paste that URL into the plugin configuration page and start registration.
8. Complete the Zoom Classroom registration flow.
9. Return to the plugin page and confirm the configured Moodle tool is shown.
10. Validate the button in a real course TinyMCE field.

If you only read one section of this README, read this one.

## Installation

1. Download the latest `tiny_zoomclassroom.zip` artifact from GitHub Releases.
2. Sign in to Moodle as an administrator.
3. Open:

   **Site administration → Plugins → Install plugins**

4. Upload:

   ```text
   tiny_zoomclassroom.zip
   ```

5. Continue through Moodle's plugin installation screens.
6. Open **Site administration → Notifications** if Moodle prompts for upgrade completion.
7. Finish the upgrade.
8. Confirm the plugin appears in:

   **Site administration → Plugins → Text editors → TinyMCE editor → Zoom Classroom**

## Configuration

1. Open:

   **Site administration → Plugins → Text editors → TinyMCE editor → Zoom Classroom**

2. In Zoom Classroom, copy the **dynamic registration URL** for the Moodle integration.
3. Paste the URL into the plugin configuration page.
4. Start dynamic registration.
5. Complete the tool-side registration flow.
6. Return to the plugin page.
7. Confirm the configured Moodle tool is shown.
8. If needed, use the plugin page to:
   - update the registration with a new URL
   - select an existing eligible Moodle LTI 1.3 tool
   - clear the configured tool
   - delete the configured Moodle tool
9. Optionally adjust:
   - popup width
   - popup height
   - allowed launch domains

## Validation checklist

After configuration, verify the full flow:

1. Open a real Moodle course.
2. Edit a resource, activity, submission, or other field that uses TinyMCE.
3. Confirm the **Zoom Classroom** button appears.
4. Click the button.
5. Confirm the popup opens.
6. Confirm Zoom Classroom content selection loads.
7. Select a piece of content.
8. Confirm the popup closes and the content appears in the editor.
9. Save the Moodle item.
10. Re-open the content in view mode and confirm the embedded item renders correctly.

Recommended extra checks:

- teacher-author insert flow
- student online-text submission flow
- teacher grading / review view for student-authored content

## How it works

At a high level, the plugin:

1. opens Moodle's deep-link selection flow from TinyMCE
2. receives the selected Zoom Classroom resource
3. validates and normalizes the launch configuration
4. stores that launch configuration server-side
5. writes only an opaque embed ID into saved HTML
6. performs the final LTI 1.3 launch later through plugin-owned `view.php` and `auth.php`

This avoids creating duplicate visible Moodle external-tool activities for each embedded asset.

For detailed design and review material, see:

- `docs/architecture-and-technical-deep-dive.md`
- `docs/security-review-guide.md`
- `docs/admin-installation-guide.md`
- `docs/teacher-student-guide.md`

## Storage model

The plugin stores selected embed launch metadata in its own database table, including:

- tool ID
- course ID
- title
- normalized launch configuration
- timestamps

Saved TinyMCE HTML stores only:

- an opaque embed ID in `data-embed-id`
- a placeholder image URL pointing to plugin `placeholder.php`

Saved HTML does **not** store:

- raw launch metadata
- client secrets
- private signing keys

## Security and trust model

- The Moodle administrator decides which external tool registration to trust
- Moodle remains the LTI platform identity for issuer, token, and JWKS
- The plugin owns the TinyMCE-specific OpenID configuration, registration, and authorization endpoints
- Launch URLs must use `https` and match an administrator-managed allowlist
- Runtime rendering still requires Moodle authentication and course access
- The final `view.php` → `auth.php` launch handoff uses short-lived server-side session state

## Support boundaries

This repository contains the Moodle TinyMCE plugin only.

It does **not** include:

- the Zoom Classroom LTI tool implementation
- Moodle itself
- institutional deployment or browser policy management

## Building a release ZIP

To build the customer-installable plugin ZIP locally:

```bash
./ci/build.sh
```

This creates:

- `dist/tiny_zoomclassroom.zip`

The generated ZIP includes installable plugin content only, such as:

- PHP plugin files
- `classes/`
- `db/`
- `lang/`
- `pix/`
- compiled JavaScript in `amd/build/`
- `LICENSE`
- `README.md`

It excludes repository-only content such as:

- `.git/`
- `docs/`
- source files in `amd/src/`
- `ci/`
- pre-existing `dist/` contents

## Development notes

- `dist/` is a generated release artifact and should not be committed
- This repository does not require Node.js or Composer to package the release ZIP
- Use `./ci/build.sh` to generate the installable artifact for release distribution
