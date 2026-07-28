/**
 * Toolbar configuration for tiny_zoomclassroom.
 *
 * @module     tiny_zoomclassroom/configuration
 */

import {addMenubarItem, addToolbarButton} from 'editor_tiny/utils';
import {buttonName} from './common';

export const configure = (instanceConfig) => ({
    toolbar: addToolbarButton(instanceConfig.toolbar, 'content', buttonName),
    menu: addMenubarItem(instanceConfig.menu, 'insert', buttonName),
});
