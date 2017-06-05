var sgHelper = {};
(function ($) {
    sgHelper = {
        init: function () {
            var workspace = $('#SimpleGallery');
            workspace.append('<div class="js-fileapi-wrapper"><div class="btn-left"><a href="javascript:void(0)" id="sgUploadBtn"></a></div>' + (sgConfig._xtRefreshBtn ? '<div class="btn-right"><a id="sgShowRefreshFormBtn" href="javascript:void(0)">' + _sgLang['refresh_previews'] + '</a></div>' : '') + '<div id="sg_pages"></div><div id="sg_images"></div><div style="clear:both;"></div></div>');
            $('#sgShowRefreshFormBtn').linkbutton({
                iconCls: 'fa fa-recycle fa-lg',
                onClick: function () {
                    sgHelper.refresh();
                }
            });
            var uploaderOptions = {
                workspace: '#SimpleGallery',
                dndArea: '.js-fileapi-wrapper',
                uploadBtn: '#sgUploadBtn',
                url: sgConfig._xtAjaxUrl,
                data: {
                    mode: 'upload',
                    sg_rid: sgConfig.rid
                },
                filterFn: function (file) {
                    return /jpeg|gif|png$/.test(file.type);
                },
                completeCallback: function () {
                    sgHelper.update();
                }
            };
            if (Object.keys(sgConfig.clientResize).length) {
                uploaderOptions.imageTransform = sgConfig.clientResize;
                uploaderOptions.imageAutoOrientation = true;
            } else {
                uploaderOptions.imageAutoOrientation = false;
            }
            var sgUploader = new EUIUploader(uploaderOptions);
            this.initImages();
            var buttons = [];
            buttons.push({
                iconCls: 'btn-red fa fa-trash fa-lg btn-extra',
                handler: function () {
                    sgHelper.delete();
                }
            });
            if (sgConfig._xtRefreshBtn) {
                buttons.push({
                    iconCls: 'btn-green fa fa-mail-forward fa-lg btn-extra',
                    handler: function () {
                        sgHelper.move();
                    }
                });
            }
            buttons.push({
                iconCls: 'fa fa-arrow-up fa-lg btn-extra',
                handler: function () {
                    sgHelper.place('top');
                }
            },{
                iconCls: 'fa fa-arrow-down fa-lg btn-extra',
                handler: function () {
                    sgHelper.place('bottom');
                }
            });
            $('#sg_pages').pagination({
                total: 0,
                pageSize: 50,
                pageList: [50, 100, 150, 200],
                buttons: buttons,
                onSelectPage: function (pageNumber, pageSize) {
                    $(this).pagination('loading');
                    $.post(
                        sgConfig._xtAjaxUrl,
                        {
                            rows: pageSize,
                            page: pageNumber,
                            sg_rid: sgConfig.rid
                        },
                        function (response) {
                            $('#sg_pages').pagination('refresh', {total: response.total});
                            sgHelper.renderImages(response.rows);
                        }, 'json'
                    ).fail(function (xhr) {
                        $.messager.alert(_sgLang['error'], _sgLang['server_error'] + xhr.status + ' ' + xhr.statusText, 'error');
                    });
                    $(this).pagination('loaded');
                    $('.btn-extra').parent().parent().hide();
                }
            });
        },
        update: function () {
            $('#sg_pages').pagination('select');
        },
        destroyWindow: function (wnd) {
            wnd.window('destroy', true);
            $('.window-shadow,.window-mask').remove();
            $('body').css('overflow', 'auto');
        },
        renderImages: function (rows) {
            var len = rows.length;
            var placeholder = $('#sg_images');
            placeholder.html('');
            for (i = 0; i < len; i++) {
                var context = {
                    data: rows[i],
                    sgLang: _sgLang,
                    thumbPrefix: sgConfig._xtThumbPrefix
                };
                var image = $(Handlebars.templates.preview(context));
                image.data('properties', rows[i]);
                placeholder.append(image);
            }
        },
        initImages: function () {
            var _this = this;
            $('#sg_images').on('click', '.del', function () {
                _this.delete($(this).parent());
            }).on('click', '.edit', function () {
                _this.edit($(this).parent());
            }).on('click', '.sg_image', function (e) {
                _this.unselect();
                _this.select($(this), e);
            }).on('dblclick', '.sg_image', function (e) {
                _this.unselect();
                _this.edit($(this));
            });
            $(document).on('keydown', function (e) {
                return !_this.selectAll(e);
            });
            $('body').attr('ondragstart', '');
            if (sgConfig.sgSort !== null) sgConfig.sgSort.destroy();
            var sg_images = document.getElementById('sg_images');
            sgConfig.sgSort = new Sortable(sg_images, {
                draggable: '.sg_image',
                onStart: function (e) {
                    sgConfig.sgBeforeDragState = {
                        prev: e.item.previousSibling != null ? $(e.item.previousSibling).data('properties').sg_index : -1,
                        next: e.item.nextSibling != null ? $(e.item.nextSibling).data('properties').sg_index : -1
                    };
                },
                onEnd: function (e) {
                    var sgAfterDragState = {
                        prev: e.item.previousSibling != null ? $(e.item.previousSibling).data('properties').sg_index : -1,
                        next: e.item.nextSibling != null ? $(e.item.nextSibling).data('properties').sg_index : -1
                    };
                    if (sgAfterDragState.prev == sgConfig.sgBeforeDragState.prev && sgAfterDragState.next == sgConfig.sgBeforeDragState.next) return;
                    var source = $(e.item).data('properties');
                    sourceIndex = parseInt(source.sg_index);
                    sourceId = source.sg_id;
                    var target = e.item.nextSibling == null ? $(e.item.previousSibling).data('properties') : $(e.item.nextSibling).data('properties');
                    targetIndex = parseInt(target.sg_index);
                    if (targetIndex < sourceIndex && sgAfterDragState.next != -1) targetIndex++;

                    var tempIndex = targetIndex,
                        item = e.item;
                    if (sourceIndex < targetIndex) {
                        while (tempIndex >= sourceIndex) {
                            $(item).data('properties').sg_index = tempIndex--;
                            item = item.nextSibling == null ? item : item.nextSibling;
                        }
                    } else {
                        while (tempIndex <= sourceIndex) {
                            $(item).data('properties').sg_index = tempIndex++;
                            item = item.previousSibling == null ? item : item.previousSibling;
                        }
                    }
                    $.post(
                        sgConfig._xtAjaxUrl + '?mode=reorder', {
                            sg_rid: sgConfig.rid,
                            sourceId: sourceId,
                            sourceIndex: sourceIndex,
                            targetIndex: targetIndex
                        },
                        function (response) {
                            if (!response.success) {
                                _this.update();
                            }
                        }, 'json'
                    ).fail(function (xhr) {
                        $.messager.alert(_sgLang['error'], _sgLang['server_error'] + xhr.status + ' ' + xhr.statusText, 'error');
                    });
                }
            });
        },
        unselect: function () {
            if (document.selection && document.selection.empty)
                document.selection.empty();
            else if (window.getSelection) {
                var sel = window.getSelection();
                if (sel && sel.removeAllRanges)
                    sel.removeAllRanges();
            }
        },
        select: function (image, e) {
            var _image = $('.sg_image');
            if (!sgConfig.sgLastChecked)
                sgConfig.sgLastChecked = image;
            if (e.ctrlKey || e.metaKey) {
                if (image.hasClass('selected'))
                    image.removeClass('selected');
                else
                    image.addClass('selected');
            } else if (e.shiftKey) {
                var start = _image.index(image);
                var end = _image.index(sgConfig.sgLastChecked);
                _image.slice(Math.min(start, end), Math.max(start, end) + 1).addClass('selected');

            } else {
                _image.removeClass('selected');
                image.addClass('selected');
                sgConfig.sgLastChecked = image;
            }
            var images = $('.sg_image.selected').get();
            if (images.length) {
                $('.btn-extra').parent().parent().show();
            } else {
                $('.btn-extra').parent().parent().hide();
            }
        },
        selectAll: function (e) {
            if ((!e.ctrlKey && !e.metaKey) || ((e.keyCode != 65) && (e.keyCode != 97)))
                return false;
            var images = $('.sg_image').get();
            if (images.length) {
                $.each(images, function (i, image) {
                    if (!$(image).hasClass('selected'))
                        $(image).addClass('selected');
                });
            }
            $('.btn-extra').parent().parent().show();
            return true;
        },
        getSelected: function () {
            var ids = [];
            var images = $('.sg_image.selected');
            if (images.length) {
                $.each(images, function (i, image) {
                    ids.push($(image).data('properties').sg_id);
                });
            }
            return ids;
        },
        delete: function (image) {
            var ids = image === undefined ? this.getSelected() : [image.data('properties').sg_id];
            $.messager.confirm(_sgLang['delete'], (ids.length > 1 ? _sgLang['are_you_sure_to_delete_many'] : _sgLang['are_you_sure_to_delete']), function (r) {
                if (r && ids.length > 0) {
                    $.post(
                        sgConfig._xtAjaxUrl + '?mode=remove',
                        {
                            ids: ids.join(),
                            sg_rid: sgConfig.rid
                        },
                        function (response) {
                            console.log(response.success);
                            if (response.success) {
                                sgHelper.update();
                            } else {
                                $.messager.alert(_sgLang['error'], _sgLang['delete_fail']);
                            }
                        }, 'json'
                    ).fail(function (xhr) {
                        $.messager.alert(_sgLang['error'], _sgLang['server_error'] + xhr.status + ' ' + xhr.statusText, 'error');
                    });
                }
            });
        },
        move: function () {
            var ids = this.getSelected();
            var context = {
                sgLang: _sgLang
            };
            var moveForm = $(Handlebars.templates.moveForm(context));
            moveForm.dialog({
                modal: true,
                title: _sgLang['move'],
                doSize: true,
                collapsible: false,
                minimizable: false,
                maximizable: false,
                resizable: false,
                buttons: [{
                    iconCls: 'btn-green fa fa-check fa-lg',
                    text: _sgLang['continue'],
                    handler: function () {
                        var _to = $('#sgMoveTo').val();
                        if (_to <= 0 || _to == sgConfig.rid) return $.messager.alert(_sgLang['error'], _sgLang['move_fail']);
                        $.post(
                            sgConfig._xtAjaxUrl + '?mode=move',
                            {
                                sg_rid: sgConfig.rid,
                                ids: ids.join(),
                                to: _to
                            },
                            function (response) {
                                if (response.success) {
                                    sgHelper.update();
                                    moveForm.window('close');
                                } else {
                                    $.messager.alert(_sgLang['error'], _sgLang['move_fail']);
                                }
                            }, 'json'
                        ).fail(function (xhr) {
                            $.messager.alert(_sgLang['error'], _sgLang['server_error'] + xhr.status + ' ' + xhr.statusText, 'error');
                        });
                    }
                }, {
                    iconCls: 'btn-red fa fa-ban fa-lg',
                    text: _sgLang['cancel'],
                    handler: function () {
                        moveForm.window('close', true);
                    }
                }],
                onClose: function () {
                    sgHelper.destroyWindow(moveForm);
                }
            });
        },
        place: function (dir) {
            var ids = this.getSelected();
            $.messager.confirm(_sgLang['move'], _sgLang['are_you_sure_to_move'], function (r) {
                if (r) {
                    $.post(
                        sgConfig._xtAjaxUrl + '?mode=place',
                        {
                            sg_rid: sgConfig.rid,
                            ids: ids.join(),
                            dir: dir
                        },
                        function (response) {
                            if (response.success) {
                                sgHelper.update();
                            } else {
                                $.messager.alert(_sgLang['error'], _sgLang['move_fail']);
                            }
                        }, 'json'
                    ).fail(function (xhr) {
                        $.messager.alert(_sgLang['error'], _sgLang['server_error'] + xhr.status + ' ' + xhr.statusText, 'error');
                    });
                }
            });
        },
        edit: function (image) {
            var data = image.data('properties');
            var context = {
                data: data,
                modxSiteUrl: sgConfig._modxSiteUrl,
                sgLang: _sgLang
            };
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
                        ).fail(function (xhr) {
                            $.messager.alert(_sgLang['error'], _sgLang['server_error'] + xhr.status + ' ' + xhr.statusText, 'error');
                        });
                    }
                }, {
                    iconCls: 'btn-red fa fa-ban fa-lg',
                    text: _sgLang['cancel'],
                    handler: function () {
                        editForm.window('close', true);
                    }
                }],
                onOpen: function() {
                    $('.image img',editForm).on('load',function() {
                        console.log(this.naturalWidth, this.naturalHeight);
                        //TODO lightbox
                    });
                },
                onClose: function () {
                    sgHelper.destroyWindow(editForm);
                }
            });
        },
        escape: function (str) {
            return str
                .replace(/&/g, '&amp;')
                .replace(/>/g, '&gt;')
                .replace(/</g, '&lt;')
                .replace(/"/g, '&quot;');
        },
        refresh: function () {
            var templates = [],
                total = 0;

            function processRefresh() {
                $.ajax({
                    url: sgConfig._xtAjaxUrl + '?mode=processRefresh',
                    type: 'POST',
                    timeout: 25000,
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            refreshStatus();
                        } else {
                            $.messager.alert(_sgLang['error'], _sgLang['refresh_fail']);
                        }
                    },
                    complete: function (xhr, textStatus) {
                        if (textStatus != "success") {
                            refreshStatus();
                        }
                    }
                }).fail(function (xhr) {
                    $.messager.alert(_sgLang['error'], _sgLang['server_error'] + xhr.status + ' ' + xhr.statusText, 'error');
                });
            }

            function refreshStatus() {
                $.post(
                    sgConfig._xtAjaxUrl + '?mode=getRefreshStatus',
                    function (response) {
                        if (response.success) {
                            var part = total > 0 ? 100 * response.processed / total : 0;
                            $('#sgRefreshProgress').progressbar('setText', response.processed + ' ' + _sgLang['from'] + ' ' + total + ' ({value}%)').progressbar('setValue', part);
                            if (response.processed < total) {
                                processRefresh();
                            } else {
                                $('#sgRunRefreshBtn').linkbutton({
                                    iconCls: 'btn-red fa fa-ban fa-lg',
                                    text: _sgLang['close'],
                                    onClick: function () {
                                        $('#sgRefresh').window('close');
                                    }
                                });
                            }
                        } else {
                            $.messager.alert(_sgLang['error'], _sgLang['refresh_fail']);
                        }
                    }, 'json'
                ).fail(function (xhr) {
                    $.messager.alert(_sgLang['error'], _sgLang['server_error'] + xhr.status + ' ' + xhr.statusText, 'error');
                });
            }

            $.messager.confirm(_sgLang['refresh_previews'], _sgLang['are_you_sure_to_refresh'], function (r) {
                if (r) {
                    var tpls = $.parseJSON(sgConfig._xtTpls);
                    var context = {
                        tpls: tpls,
                        sgLang: _sgLang,
                    };
                    var refreshForm = $(Handlebars.templates.refreshForm(context));
                    refreshForm.dialog({
                        width: 450,
                        modal: true,
                        title: _sgLang['refresh_previews'],
                        doSize: true,
                        collapsible: false,
                        minimizable: false,
                        maximizable: false,
                        resizable: false,
                        buttons: [{
                            id: 'sgRunRefreshBtn',
                            iconCls: 'btn-green fa fa-check fa-lg',
                            text: _sgLang['continue'],
                            onClick: function () {
                                var method = $('form input[name="method"]:checked', '#sgRefresh').val();
                                var formdata = {};
                                switch (method) {
                                    case "0":
                                        formdata.method = 'rid';
                                        formdata.ids = sgConfig.rid;
                                        break;
                                    case "1":
                                        formdata.method = 'rid';
                                        formdata.ids = $('form input[name="ids"]', '#sgRefresh').val();
                                        break;
                                    case "2":
                                        formdata.method = 'template';
                                        formdata.ids = $('form input[name="template[]"]:checked', '#sgRefresh').map(function(){return $(this).val();}).get().join();
                                    break;
                                }
                                if (formdata.ids.length == 0) return;
                                $.post(
                                    sgConfig._xtAjaxUrl + '?mode=initRefresh',
                                    formdata,
                                    function (response) {
                                        if (response.success) {
                                            var total = response.total > 0 ? response.total : 0;
                                            $('#sgRefreshProgress').progressbar('setText', '0 ' + _sgLang['from'] + ' ' + total + ' ({value}%)').show();
                                            refreshStatus();
                                        } else {
                                            $.messager.alert(_sgLang['error'], _sgLang['refresh_fail']);
                                        }
                                    }, 'json'
                                ).fail(function (xhr) {
                                    $.messager.alert(_sgLang['error'], _sgLang['server_error'] + xhr.status + ' ' + xhr.statusText, 'error');
                                });
                            }
                        }
                        ],
                        onOpen: function () {
                            $('#sgRefreshProgress').progressbar().hide();
                            $('#sgRefresh').off().on('change','input[name="method"]',function(){
                                $('.elements','#sgRefresh').hide();
                                switch (this.value) {
                                    case "1":
                                        $('input[name="ids"]','#sgRefresh').show();
                                        break;
                                    case "2":
                                        $('.templates','#sgRefresh').show();
                                        break;
                                }
                            });
                        },
                        onClose: function () {
                            sgHelper.destroyWindow(refreshForm);
                        }
                    });
                }
            });
        }
    }
})(jQuery);
