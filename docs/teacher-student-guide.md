# Zoom Classroom TinyMCE Plugin Teacher and Student Guide

## What this plugin does

The Zoom Classroom TinyMCE plugin adds a **Zoom Classroom** button to Moodle's TinyMCE editor so users can select Zoom Classroom content through **LTI 1.3 deep linking** and insert it into Moodle content areas.

Typical use cases include:

- assignment instructions
- assignment submissions
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
8. Confirm the selected content appears in the editor.
9. Save the Moodle activity, resource, or page.

### What happens after saving

After saving:

- Moodle stores the editor content normally
- the plugin later renders the embedded Zoom Classroom item through an authenticated LTI 1.3 launch
- no separate Moodle external-tool activity is created for that embed

## For students

Students may also use the Zoom Classroom button when Moodle allows them to edit TinyMCE content, such as:

- assignment online-text submissions
- discussion replies
- other student-editable TinyMCE fields

Whether students see the button depends on:

- the field using TinyMCE
- the plugin being configured
- Moodle role permissions for editing that content area

### Student submission flow

1. Open the editable Moodle submission or discussion field.
2. Click the **Zoom Classroom** button.
3. Select content in Zoom Classroom.
4. Confirm the content appears in the editor.
5. Save the submission or post.

When the saved content is viewed later, the plugin performs the same authenticated LTI 1.3 render flow used for teacher-authored embeds.

## If the button is missing

Possible reasons:

- the plugin is not configured yet
- the page is not using TinyMCE
- you are not editing in a real course context
- you are a guest user
- Moodle role configuration does not allow editing for that field

## If the popup is blocked

Allow popups for your Moodle site, then try again.

## If the popup opens but Zoom Classroom shows an error

That usually means the Moodle button worked, but the external tool or deep-link response needs administrator review.

Report the issue to your Moodle administrator with:

- the course name
- the Moodle page you were editing
- the time of the issue
- any popup or tool error message you saw

## What viewers should expect

If content was inserted successfully:

- the content should render as embedded Zoom Classroom content when viewed later, or
- it may appear according to the exact resource behavior returned by Zoom Classroom

## Common questions

### Why do I not see the Zoom Classroom button?

Most likely because:

- the current page is not using TinyMCE
- the plugin is not configured
- the page is not in a course context
- your role cannot edit that field

### Does the inserted content launch through Moodle?

Yes. The final rendered resource launches through plugin-owned endpoints that reuse Moodle's LTI 1.3 platform trust and signing infrastructure.

### Does this create a separate Moodle activity?

No. The embed renders from TinyMCE content and does not create a separate visible external-tool activity for each selected resource.

## Tips

- Save after inserting content into TinyMCE
- Test the flow in a course before broad rollout
- If content renders unexpectedly, report the exact Moodle page and the selected Zoom Classroom item to your administrator
