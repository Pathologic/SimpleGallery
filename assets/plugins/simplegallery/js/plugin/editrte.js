(function($){
sgHelper.rteForm = function(textarea) {
    var context = {
        textarea: textarea.val(),
        modxTheme: sgConfig._modxTheme,
        modxSiteUrl: sgConfig._modxSiteUrl,
        sgLang: _sgLang
    };
    var rteForm = $(Handlebars.templates.rteForm(context));
    rteForm.window({
        modal:true,
        title:"Редактирование",
        collapsible:false,
        minimizable:false,
        maximizable:false,
        resizable:false,
        onOpen: function() {
            $('#rteCancel').click(function(e){
                rteForm.window('close',true);
            });
            $('#rteSave').click(function(e){
                var content = tinyMCE.activeEditor.getContent();
                textarea.val(content);
                rteForm.window('close',true);
            });
            sgHelper.initRTE();
        },
        onClose: function() {
            sgHelper.destroyWindow(rteForm);
        }
    })
};
sgHelper.edit = function(image) {
    var data = image.data('properties');
    var context = {
        data: data,
        modxTheme: sgConfig._modxTheme,
        modxSiteUrl: sgConfig._modxSiteUrl,
        sgLang: _sgLang
    };
    var editForm = $(Handlebars.templates.editFormRTE(context));
    editForm.window({
        modal:true,
        title:sgHelper.escape(this.stripText(data.sg_title,80)),
        doSize:true,
        collapsible:false,
        minimizable:false,
        maximizable:false,
        resizable:false,
        onOpen: function() {
            $('.btn-rte').click(function(e){
                sgHelper.rteForm($('textarea','#sgEdit'));
            });
            $('#sgEditCancel').click(function(e){
                editForm.window('close',true);
            });
            $('#sgEditSave').click(function(e){
                $.post(
                sgConfig._xtAjaxUrl+'?mode=edit', 
                $('#sgForm').serialize(),
                function(data) {
                    data = sgHelper.getData(data);
                    if(data.success) {
                        editForm.window('close',true);
                        sgHelper.update();
                    } else {
                        $.messager.alert(_sgLang['error'],_sgLang['save_fail']);
                    }
                })
            })
        },
        onClose: function() {
            sgHelper.destroyWindow(editForm);
        }
    });
}
})(jQuery)