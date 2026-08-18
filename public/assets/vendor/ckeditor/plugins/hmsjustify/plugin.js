CKEDITOR.plugins.add('hmsjustify', {
    init: function(editor) {
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
            icon: false,
            command: 'HmsJustifyLeft',
            toolbar: 'paragraph,40'
        });
        editor.ui.addButton('HmsJustifyCenter', {
            label: 'Center (C)',
            icon: false,
            command: 'HmsJustifyCenter',
            toolbar: 'paragraph,41'
        });
        editor.ui.addButton('HmsJustifyRight', {
            label: 'Align Right (R)',
            icon: false,
            command: 'HmsJustifyRight',
            toolbar: 'paragraph,42'
        });
        editor.ui.addButton('HmsJustifyBlock', {
            label: 'Justify (J)',
            icon: false,
            command: 'HmsJustifyBlock',
            toolbar: 'paragraph,43'
        });
    }
});
