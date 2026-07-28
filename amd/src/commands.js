/**
 * Command registration for tiny_zoomclassroom.
 *
 * @module     tiny_zoomclassroom/commands
 */

import {getButtonImage} from 'editor_tiny/utils';
import {get_string as getString} from 'core/str';
import {buttonName, component, icon} from './common';
import {handleAction} from './ui';
import {getTool} from './options';

export const getSetup = async() => {
    const [buttonImage, menuText] = await Promise.all([
        getButtonImage('icon', component),
        getString('menuitem', component),
    ]);

    return (editor) => {
        if (!getTool(editor)) {
            return;
        }

        editor.ui.registry.addIcon(icon, buttonImage.html);

        editor.ui.registry.addButton(buttonName, {
            icon,
            tooltip: menuText,
            onAction: () => handleAction(editor),
        });

        editor.ui.registry.addMenuItem(buttonName, {
            icon,
            text: menuText,
            onAction: () => handleAction(editor),
        });
    };
};
