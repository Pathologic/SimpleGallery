(function($){
sgHelper.initRTE = function() {
    tinymce.init({
        selector:'#rteField',
        relative_urls:true,
        remove_script_host:true,
        convert_urls:true,
        resize:false,
        forced_root_block:'p',
        skin:'lightgray',
        width:'100%',
        height:400,
        menubar:true,
        statusbar:true,
        document_base_url:sgConfig._modxSiteUrl,
        entity_encoding:'named',
        language:'ru',
        language_url:sgConfig._modxSiteUrl+'/assets/plugins/tinymce4/tinymce/langs/ru.js',
        schema:'html5',
        element_format:'xhtml',
        image_caption:true,
        image_advtab:true,
        image_class_list:[{title: "None", value: ""},{title: "Float left", value: "justifyleft"},{title: "Float right", value: "justifyright"},{title: "Image Responsive",value: "img-responsive"}],
        browser_spellcheck:false,
        paste_word_valid_elements:'a[href|name],p,b,strong,i,em,h1,h2,h3,h4,h5,h6,table,th,td[colspan|rowspan],tr,thead,tfoot,tbody,br,hr,sub,sup,u',
        plugins:'anchor autolink lists spellchecker pagebreak layer table save hr modxlink image imagetools code emoticons insertdatetime preview media searchreplace print contextmenu paste directionality fullscreen noneditable visualchars nonbreaking youtube autosave advlist visualblocks charmap',
        toolbar1:'bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | styleselect formatselect',
        toolbar2:'bullist numlist | outdent indent | undo redo | link unlink anchor image help code',
        style_formats:[{"title":"Inline","items":[{"title":"InlineTitle","inline":"span","classes":"cssClass1"},{"title":"InlineTitle2","inline":"span","classes":"cssClass2"}]},{"title":"Block","items":[{"title":"BlockTitle","selector":"*","classes":"cssClass3"},{"title":"BlockTitle2","selector":"*","classes":"cssClass4"}]}],
        block_formats:'',
        toolbar3:'hr removeformat visualblocks | subscript superscript | charmap',
        file_browser_callback: function (field, url, type, win) {
            if (type == 'image') {
                type = 'images';
            }
            if (type == 'file') {
                type = 'files';
            }
            tinyMCE.activeEditor.windowManager.open({
                file: sgConfig._modxSiteUrl+'/manager/media/browser/mcpuk/browse.php?opener=tinymce4&field=' + field + '&type=' + type,
                title: 'KCFinder',
                width: 840,
                height: 600,
                inline: true,
                close_previous: false
            }, {
                window: win,
                input: field
            });
            return false;
        }

    });
}
sgHelper.rteForm = function(textarea) {
    var context = {
        textarea: textarea.val(),
    };
    var rteForm = $(Handlebars.templates.rteForm(context));
    rteForm.dialog({
        modal:true,
        title:"Редактирование",
        collapsible:false,
        minimizable:false,
        maximizable:false,
        resizable:false,
        buttons:[
        {
            text:'Сохранить',
            iconCls: 'btn-green fa fa-check fa-lg',
            handler:function(){
                var content = tinymce.activeEditor.getContent();
                textarea.val(content);
                rteForm.window('close',true);
            }
        },{
            text:'Закрыть',
            iconCls: 'btn-red fa fa-ban fa-lg',
            handler:function(){
                rteForm.window('close', true);
            }
        }
        ],
        onOpen: function() {
            sgHelper.initRTE();
        },
        onClose: function() {
            var mask = $('.window-mask');
            tinymce.execCommand('mceRemoveEditor',true,"rteField");
            sgHelper.destroyWindow(rteForm);
            $('body').append(mask);
        }
    })
};
sgHelper.edit = function(image) {
    var data = image.data('properties');
    var context = {
        data: data,
        modxSiteUrl: sgConfig._modxSiteUrl,
        sgLang: _sgLang
    };
    sgConfig.sgDisableSelectAll = true;
    var editForm = $(Handlebars.templates.editForm(context));
    editForm.dialog({
        modal: true,
        title: '[' + data.sg_id + '] ' + sgHelper.escape(Handlebars.helpers.stripText(data.sg_title, 80)),
        doSize: true,
        collapsible: false,
        minimizable: false,
        maximizable: false,
        resizable: false,
        buttons: [{
            iconCls: 'btn-green fa fa-check fa-lg',
            text: _sgLang['save'],
            handler: function () {
                $.post(
                    sgConfig._xtAjaxUrl + '?mode=edit',
                    $('#sgForm').serialize(),
                    function (response) {
                        if (response.success) {
                            editForm.window('close', true);
                            sgHelper.update();
                        } else {
                            $.messager.alert(_sgLang['error'], _sgLang['save_fail']);
                        }
                    }, 'json'
                ).fail(sgHelper.handleAjaxError);
            }
        }, {
            iconCls: 'btn-red fa fa-ban fa-lg',
            text: _sgLang['cancel'],
            handler: function () {
                editForm.window('close', true);
            }
        }],
        onOpen: function() {
            var descriptionField = $('textarea[name="sg_description"]','#sgEdit');
            descriptionField.after('<a href="javascript:" class="btn-rte">Редактировать</a>');
            $('.btn-rte').click(function(e){
                sgHelper.rteForm(descriptionField);
            });
            $('.image img',editForm).on('load',function() {
                var nWidth = this.naturalWidth,
                    nHeight = this.naturalHeight;
                var wWidth = $(window).width() - 200,
                    wHeight = $(window).height() - 200;
                if (nWidth > 280 || nHeight > 210) {
                    var img = $(this);
                    var minRatio = Math.min(1, wWidth / nWidth, wHeight / nHeight );
                    var width  = Math.floor( minRatio * nWidth );
                    var height = Math.floor( minRatio * nHeight );

                    img.wrap('<a href="javascript:void(0);"></a>').parent().click(function(e) {
                        e.preventDefault();
                        img.clone().css({
                            width:width,
                            height:height
                        }).wrap('<div/>').parent().window({
                            title: '[' + data.sg_id + '] ' + sgHelper.escape(Handlebars.helpers.stripText(data.sg_title, 80)),
                            modal:true,
                            collapsible:false,
                            minimizable:false,
                            maximizable:false,
                            resizable:false
                        }).window('open');
                    });
                }
            });
        },
        onClose: function () {
            sgConfig.sgDisableSelectAll = false;
            sgHelper.destroyWindow(editForm);
        }
    });
}
})(jQuery)