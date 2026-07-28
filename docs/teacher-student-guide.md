# Zoom Classroom TinyMCE Plugin Teacher and Student Guide

## What this plugin does

The Zoom Classroom TinyMCE plugin adds a **Zoom Classroom** button to Moodle's editor so course content can be selected from Zoom Classroom and inserted into Moodle content areas.

Typical use cases include:

- assignment instructions
- page content
- labels
- forum descriptions
- other Moodle areas that use TinyMCE

## For teachers

### When you can use it

You can use the Zoom Classroom button when:

- you are editing content inside a Moodle course
- the editor on that page is TinyMCE
- your Moodle administrator has installed and configured the plugin

### How to insert Zoom Classroom content

1. Open a course.
2. Edit or create a Moodle item that uses TinyMCE.
3. Click inside the editor.
4. Click the **Zoom Classroom** button in the toolbar or Insert menu.
5. In the popup window, choose the content in Zoom Classroom.
6. Finish the selection flow.
7. Confirm the popup closes.
8. Confirm the selected content is inserted into the editor.
9. Save the Moodle activity, resource, or page.

### If the button is missing

Possible reasons:

- the plugin is not configured yet
- you are not editing in a course context
- the page is not using TinyMCE
- your administrator has not mapped the plugin to the Zoom Classroom LTI tool

### If the popup is blocked

Allow popups for your Moodle site, then try again.

### If the popup opens but Zoom Classroom shows an error

That usually means the Moodle button worked, but the external tool or deep-link response needs administrator review.

Report the issue to your Moodle administrator with:

- the course name
- the Moodle page you were editing
- the time of the issue
- any popup or tool error message you saw

## For students

Students usually do **not** use this button to author course content.

In most Moodle sites, this plugin is intended for teachers or course editors who are preparing content.

Students may still see Zoom Classroom content that was inserted by an instructor into:

- course pages
- activity descriptions
- instructions
- other editor-based course content

## What students should expect

If an instructor inserted Zoom Classroom content successfully:

- it should appear as embedded launched content in the Moodle content area, or
- it may appear as a link or embedded item depending on what the tool returned through deep linking

## Common questions

### Why do I not see the Zoom Classroom button?

Most likely because you are not in an editing role, or the current page does not use TinyMCE.

### Why did my teacher's inserted content look different from expected?

The final inserted result depends on what Zoom Classroom returns through the LTI deep-link response.

### Does the inserted content launch through Moodle?

Yes. After a teacher selects content, Moodle uses a hidden backing External tool instance so the rendered content launches through Moodle's standard **LTI 1.3 OIDC flow**.

## Tips for teachers

- Test the flow in a course before using it in production content
- Save after inserting content into TinyMCE
- If content renders unexpectedly, try a fresh selection from Zoom Classroom and report the exact result to your admin
