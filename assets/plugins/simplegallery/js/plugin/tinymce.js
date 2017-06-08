(function($){
sgHelper.initRTE = function() {
tinyMCE.init({
    theme                            : 'advanced',
    skin                             : 'cirkuit',
    skin_variant                     : '',
    mode                             : 'exact',
    elements                         : 'rteField',
    width                            : '100%',
    height                           : '500',
    language                         : 'ru',
    element_format                   : 'xhtml',
    schema                           : 'html4',
    paste_text_use_dialog            : true,
    document_base_url                : sgConfig._modxSiteUrl,
    relative_urls                    : true,
    remove_script_host               : true,
    convert_urls                     : true,
    force_br_newlines                : false,
    force_p_newlines                 : true,
    forced_root_block                : 'p',
    valid_elements                   : mce_valid_elements,
    popup_css_add                    : '/assets/plugins/tinymce/style/popup_add.css',
    theme_advanced_source_editor_height : 400,
    accessibility_warnings : false,
    theme_advanced_toolbar_location  : 'top',
    theme_advanced_statusbar_location: 'bottom',
    theme_advanced_toolbar_align     : 'ltr',
    theme_advanced_font_sizes        : '80%,90%,100%,120%,140%,160%,180%,220%,260%,320%,400%,500%,700%',
    content_css                      : '/assets/plugins/tinymce/style/content.css',
    formats : {
        alignleft   : {selector : 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img', classes : 'justifyleft'},
        alignright  : {selector : 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img', classes : 'justifyright'},
        alignfull   : {selector : 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img', classes : 'justifyfull'}
    },
    apply_source_formatting          : true,
    remove_linebreaks                : false,
    convert_fonts_to_spans           : true,
    plugins                          : 'visualblocks,autolink,inlinepopups,autosave,save,advlist,style,fullscreen,advimage,paste,advlink,media,contextmenu,table,youtubeIframe',
    theme_advanced_buttons1          : 'undo,redo,|,bold,forecolor,backcolor,strikethrough,formatselect,fontsizeselect,pastetext,pasteword,code,|,fullscreen,help',
    theme_advanced_buttons2          : 'image,media,youtubeIframe,link,unlink,anchor,|,justifyleft,justifycenter,justifyright,|,bullist,numlist,|,blockquote,outdent,indent,|,table,hr,|,visualblocks,styleprops,removeformat',
    theme_advanced_buttons3          : '',
    theme_advanced_buttons4          : '',
    theme_advanced_resize_horizontal :  false,
    external_link_list_url           : '/assets/plugins/tinymce/js/tinymce.linklist.php',
    template_external_list_url       : '/assets/plugins/tinymce/js/get_template.php',
    template_popup_width             : 550,
    template_popup_height            : 350,
    theme_advanced_blockformats      : 'p,h1,h2,h3,h4,h5,h6,div,blockquote,code,pre',
    theme_advanced_styles            : 'left=justifyleft;right=justifyright',
    theme_advanced_disable           : '',
    theme_advanced_resizing          : true,
    fullscreen_settings : {
        theme_advanced_buttons1_add_before : 'save'
    },
    plugin_insertdate_dateFormat     : '%d-%m-%Y',
    plugin_insertdate_timeFormat     : '%H:%M:%S',
    entity_encoding                  : 'named',
    file_browser_callback            : 'mceOpenServerBrowser',
    paste_text_sticky                : true,
    setup : function(ed)
    {
        ed.onPostProcess.add(function(ed, o)
        {
            // State get is set when contents is extracted from editor
            if (o.get)
            {
                o.content = o.content.replace('<p>{' + '{', '{' + '{');
                o.content = o.content.replace('}}</p>', '}}');
                o.content = o.content.replace(/<p>\[([\[\!\~\^])/g, '[$1');
                o.content = o.content.replace(/([\]\!\~\^])\]<\/p>/g, '$1]');
            }
        });
    },
    onchange_callback                : false,
    valid_elements : "*[*]"
})
}
})(jQuery)