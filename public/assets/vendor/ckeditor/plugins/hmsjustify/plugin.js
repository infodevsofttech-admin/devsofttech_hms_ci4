CKEDITOR.plugins.add('hmsjustify', {
    init: function(editor) {
        var iconPath = CKEDITOR.plugins.getPath('hmsjustify') + 'icons/';
        var commands = {
            HmsJustifyLeft: 'justifyLeft',
            HmsJustifyCenter: 'justifyCenter',
            HmsJustifyRight: 'justifyRight',
            HmsJustifyBlock: 'justifyFull'
        };

        Object.keys(commands).forEach(function(commandName) {
            var nativeCommand = commands[commandName];
            editor.addCommand(commandName, {
                exec: function(instance) {
                    instance.focus();
                    instance.document.$.execCommand(nativeCommand, false, null);
                }
            });
        });

        editor.ui.addButton('HmsJustifyLeft', {
            label: 'Align Left (L)',
            icon: iconPath + 'left.svg',
            command: 'HmsJustifyLeft',
            toolbar: 'paragraph,40'
        });
        editor.ui.addButton('HmsJustifyCenter', {
            label: 'Center (C)',
            icon: iconPath + 'center.svg',
            command: 'HmsJustifyCenter',
            toolbar: 'paragraph,41'
        });
        editor.ui.addButton('HmsJustifyRight', {
            label: 'Align Right (R)',
            icon: iconPath + 'right.svg',
            command: 'HmsJustifyRight',
            toolbar: 'paragraph,42'
        });
        editor.ui.addButton('HmsJustifyBlock', {
            label: 'Justify (J)',
            icon: iconPath + 'justify.svg',
            command: 'HmsJustifyBlock',
            toolbar: 'paragraph,43'
        });
    }
});
