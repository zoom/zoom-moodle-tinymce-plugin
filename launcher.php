<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Popup launcher for Zoom Classroom deep linking.
 *
 * @package     tiny_zoomclassroom
 * @copyright   2026
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->dirroot . '/mod/lti/lib.php');
require_once($CFG->dirroot . '/mod/lti/locallib.php');

$courseid = required_param('course', PARAM_INT);
$editorid = required_param('editorid', PARAM_ALPHANUMEXT);
$toolid = required_param('id', PARAM_INT);
require_sesskey();

$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
if (isguestuser()) {
    throw new required_capability_exception($context, 'moodle/course:view', 'nopermissions', '');
}
$PAGE->set_context($context);
$PAGE->set_pagelayout('popup');
$PAGE->set_url(new moodle_url('/lib/editor/tiny/plugins/zoomclassroom/launcher.php', [
    'course' => $courseid,
    'editorid' => $editorid,
    'id' => $toolid,
    'sesskey' => sesskey(),
]));
$PAGE->set_title(get_string('launchpage', 'tiny_zoomclassroom'));

$iframeurl = new moodle_url('/mod/lti/contentitem.php', [
    'course' => $courseid,
    'id' => $toolid,
]);

echo $OUTPUT->header();
?>
<style>
    .container-fluid.d-print-block,
    #region-main-box,
    #region-main,
    .region_main_settings_menu_proxy,
    #page.drawers .main-inner,
    #page-content {
        margin: 0 !important;
        padding: 0 !important;
    }
</style>
<div style="padding: 0;">
    <iframe
        id="tiny-zoomclassroom-frame"
        title="<?php echo s(get_string('launchpage', 'tiny_zoomclassroom')); ?>"
        src="<?php echo s($iframeurl->out(false)); ?>"
        style="width: 100%; height: 100vh; border: 0;"
    ></iframe>
</div>
<script>
    (function() {
        const editorId = <?php echo json_encode($editorid); ?>;
        const courseId = <?php echo json_encode($courseid); ?>;
        const toolId = <?php echo json_encode($toolid); ?>;
        const sesskey = <?php echo json_encode(sesskey()); ?>;
        const fallbackText = <?php echo json_encode(get_string('insertfallbacktext', 'tiny_zoomclassroom')); ?>;
        const invalidResponseMessage = <?php echo json_encode(get_string('invalidresponse', 'tiny_zoomclassroom')); ?>;

        const escapeHtml = (value) => String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        const insertHtml = (html) => {
            const targetEditor = window.opener && window.opener.tinyMCE ? window.opener.tinyMCE.get(editorId) : null;
            if (!targetEditor) {
                window.close();
                return;
            }

            if (html) {
                targetEditor.execCommand('mceInsertContent', false, html);
                if (window.opener && typeof window.opener.require === 'function') {
                    window.opener.require(['tiny_zoomclassroom/render'], (render) => {
                        const body = targetEditor.getBody ? targetEditor.getBody() : null;
                        if (body && render && typeof render.upgradePlaceholdersInRoot === 'function') {
                            render.upgradePlaceholdersInRoot(body);
                        }
                    });
                }
                if (typeof targetEditor.fire === 'function') {
                    targetEditor.fire('SetContent');
                }
                if (typeof targetEditor.nodeChanged === 'function') {
                    window.setTimeout(() => targetEditor.nodeChanged(), 0);
                }
            }
            window.close();
        };

        const buildEmbedHtml = (embedId, title) => {
            const label = title || fallbackText;
            const escapedLabel = escapeHtml(label);
            const placeholderUrl = `${M.cfg.wwwroot}/lib/editor/tiny/plugins/zoomclassroom/placeholder.php?id=${encodeURIComponent(embedId)}`;
            const escapedPlaceholderUrl = escapeHtml(placeholderUrl);
            const escapedEmbedId = escapeHtml(embedId);
            return `<div class="tiny_zoomclassroom-embed" data-title="${escapedLabel}" data-embed-id="${escapedEmbedId}">` +
                `<div class="tiny_zoomclassroom-preview">${escapedLabel}</div>` +
                `<img src="${escapedPlaceholderUrl}" alt="" role="presentation" aria-hidden="true" class="tiny_zoomclassroom-sentinel" width="1" height="1" style="display:none" />` +
                `</div>`;
        };

        window.processContentItemReturnData = async function(returnData) {
            const config = returnData && Array.isArray(returnData.multiple) ? returnData.multiple[0] : returnData;
            if (!config || typeof config !== 'object') {
                window.tiny_zoomclassroom_handleError(invalidResponseMessage);
                return;
            }

            try {
                const response = await fetch(M.cfg.wwwroot + '/lib/editor/tiny/plugins/zoomclassroom/prepare.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        sesskey,
                        course: courseId,
                        toolid: toolId,
                        config,
                    }),
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const payload = await response.json();
                if (!payload || !payload.launchurl || !payload.embedid) {
                    throw new Error(
                        payload?.error ||
                        payload?.message ||
                        payload?.debuginfo ||
                        'Missing launch URL'
                    );
                }

                insertHtml(buildEmbedHtml(payload.embedid, payload.title));
            } catch (error) {
                window.tiny_zoomclassroom_handleError(error.message || invalidResponseMessage);
            }
        };

        window.tiny_zoomclassroom_handleError = function(message) {
            alert(message || 'Zoom Classroom launch failed.');
        };
    })();
</script>
<?php
echo $OUTPUT->footer();
