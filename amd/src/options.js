/**
 * Options helper for tiny_zoomclassroom.
 *
 * @module     tiny_zoomclassroom/options
 */

import {getPluginOptionName} from 'editor_tiny/options';
import {pluginName} from './common';

const courseIdOption = getPluginOptionName(pluginName, 'courseid');
const toolOption = getPluginOptionName(pluginName, 'tool');
const launcherPathOption = getPluginOptionName(pluginName, 'launcherpath');
const sesskeyOption = getPluginOptionName(pluginName, 'sesskey');
const popupWidthOption = getPluginOptionName(pluginName, 'popupwidth');
const popupHeightOption = getPluginOptionName(pluginName, 'popupheight');
const buttonLabelOption = getPluginOptionName(pluginName, 'buttonlabel');
const errorMisconfiguredOption = getPluginOptionName(pluginName, 'errormisconfigured');
const errorNoCourseOption = getPluginOptionName(pluginName, 'errornocourse');
const errorPopupBlockedOption = getPluginOptionName(pluginName, 'errorpopupblocked');

const courseIdProcessor = (value) => {
    if (typeof value === 'string' || typeof value === 'number') {
        return {
            valid: true,
            value: String(value),
        };
    }

    return {
        valid: false,
        message: 'The value must be a string or number.',
    };
};

export const register = (editor) => {
    const registerOption = editor.options.register;
    registerOption(courseIdOption, {processor: courseIdProcessor});
    registerOption(toolOption, {processor: 'object'});
    registerOption(launcherPathOption, {processor: 'string'});
    registerOption(sesskeyOption, {processor: 'string'});
    registerOption(popupWidthOption, {processor: 'string'});
    registerOption(popupHeightOption, {processor: 'string'});
    registerOption(buttonLabelOption, {processor: 'string'});
    registerOption(errorMisconfiguredOption, {processor: 'string'});
    registerOption(errorNoCourseOption, {processor: 'string'});
    registerOption(errorPopupBlockedOption, {processor: 'string'});
};

export const getCourseId = (editor) => editor.options.get(courseIdOption);
export const getTool = (editor) => editor.options.get(toolOption);
export const getLauncherPath = (editor) => editor.options.get(launcherPathOption);
export const getSesskey = (editor) => editor.options.get(sesskeyOption);
export const getPopupWidth = (editor) => editor.options.get(popupWidthOption);
export const getPopupHeight = (editor) => editor.options.get(popupHeightOption);
export const getButtonLabel = (editor) => editor.options.get(buttonLabelOption);
export const getErrorMisconfigured = (editor) => editor.options.get(errorMisconfiguredOption);
export const getErrorNoCourse = (editor) => editor.options.get(errorNoCourseOption);
export const getErrorPopupBlocked = (editor) => editor.options.get(errorPopupBlockedOption);
