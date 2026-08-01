/**
 * Plugin entrypoint for tiny_zoomclassroom.
 *
 * @module     tiny_zoomclassroom/plugin
 */

import {getTinyMCE} from 'editor_tiny/loader';
import {getPluginMetadata} from 'editor_tiny/utils';
import {component, pluginName} from './common';
import {register as registerOptions} from './options';
import {getSetup as getCommandSetup} from './commands';
import * as Configuration from './configuration';
import {downgradePlaceholdersInRoot, upgradePlaceholdersInRoot} from './render';

export default new Promise(async(resolve) => {
    const [tinyMCE, pluginMetadata, setupCommands] = await Promise.all([
        getTinyMCE(),
        getPluginMetadata(component, pluginName),
        getCommandSetup(),
    ]);

    tinyMCE.PluginManager.add(pluginName, (editor) => {
        registerOptions(editor);
        setupCommands(editor);

        const hydrateEditor = () => {
            const body = editor.getBody();
            if (body) {
                upgradePlaceholdersInRoot(body);
            }
        };

        const dehydrateHtml = (event) => {
            if (!event || typeof event.content !== 'string') {
                return;
            }

            const tempRoot = editor.getDoc().createElement('div');
            tempRoot.innerHTML = event.content;
            downgradePlaceholdersInRoot(tempRoot);
            event.content = tempRoot.innerHTML;
        };

        editor.on('init', hydrateEditor);
        editor.on('SetContent', hydrateEditor);
        editor.on('ExecCommand', hydrateEditor);
        editor.on('Change', hydrateEditor);
        editor.on('SaveContent', dehydrateHtml);

        return pluginMetadata;
    });

    resolve([pluginName, Configuration]);
});
