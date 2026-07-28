/**
 * UI helper for tiny_zoomclassroom.
 *
 * @module     tiny_zoomclassroom/ui
 */

import Config from 'core/config';
import {
    getCourseId,
    getTool,
    getLauncherPath,
    getSesskey,
    getPopupHeight,
    getPopupWidth,
    getErrorMisconfigured,
    getErrorNoCourse,
    getErrorPopupBlocked,
} from './options';

const toPositiveInt = (value, fallbackValue) => {
    const parsed = parseInt(value, 10);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : fallbackValue;
};

export const handleAction = (editor) => {
    const tool = getTool(editor);
    if (!tool || !tool.id) {
        window.alert(getErrorMisconfigured(editor));
        return;
    }

    const configuredCourseId = getCourseId(editor);
    const fallbackCourseId = window.M?.cfg?.courseId;
    const courseId = String(configuredCourseId || fallbackCourseId || '0');
    if (!courseId || courseId === '0') {
        window.alert(getErrorNoCourse(editor));
        return;
    }

    const width = toPositiveInt(getPopupWidth(editor), 1200);
    const height = toPositiveInt(getPopupHeight(editor), 800);
    const left = Math.max(0, Math.round((window.screen.width - width) / 2));
    const top = Math.max(0, Math.round((window.screen.height - height) / 2));
    const launcherUrl = new URL(`${Config.wwwroot}${getLauncherPath(editor)}`);
    launcherUrl.searchParams.set('course', courseId);
    launcherUrl.searchParams.set('id', String(tool.id));
    launcherUrl.searchParams.set('editorid', editor.id);
    launcherUrl.searchParams.set('sesskey', getSesskey(editor));

    const popup = window.open(
        launcherUrl.toString(),
        'tiny_zoomclassroom_picker',
        `popup=yes,width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`
    );

    if (popup) {
        popup.focus();
        return;
    }

    window.alert(getErrorPopupBlocked(editor));
};
