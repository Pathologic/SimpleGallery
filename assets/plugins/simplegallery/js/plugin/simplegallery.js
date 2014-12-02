var sgHelper = {};
(function($){
	$.fn.pagination.defaults.pageList = [50,100,150,200];
	sgHelper = {
        init: function() {
            Handlebars.registerHelper('stripText', function(str, len){
                return sgHelper.stripText(str, len);
            });
            Handlebars.registerHelper('bytesToSize', function(bytes){
                return sgHelper.bytesToSize(bytes);
            });
            Handlebars.registerHelper('ifCond', function(v1, v2, options) {
                if(v1 === v2) {
                    return options.fn(this);
                }
                return options.inverse(this);
            });
            var workspace = $('#SimpleGallery');
            workspace.append('<div class="js-fileapi-wrapper"><div class="btn"><div class="btn-text"><img src="'+_modxTheme+'/images/icons/folder_page_add.png">'+_sgLang['upload']+'</div><input id="sg_files" name="sg_files" class="btn-input" type="file" multiple /></div>'+_xtRefreshBtn+'<div id="sg_pages"></div><div id="sg_images"></div><div style="clear:both;"></div></div>');
			$('#sg_refresh').click(function(){
		    	sgHelper.refresh();
		    });
            workspace.fileapi({
            	url: _xtAjaxUrl+'?mode=upload',
            	autoUpload: true,
            	accept: 'image/*',
            	multiple: true,
            	clearOnSelect: true,
            	data: {
            		'sg_rid': rid
            	},
            	filterFn: function (file, info) {
            		return /jpeg|gif|png$/.test(file.type);
            	},
            	onBeforeUpload: function(e,uiE) {
	            	var total = uiE.files.length;
                    var context = {
                        files: uiE.files,
                        sgLang: _sgLang,
                        modxTheme: _modxTheme
                    };
	            	var uploadStateForm = $(Handlebars.templates.uploadForm(context));
	            	uploadStateForm.window({
	    				width:450,
	    				modal:true,
	    				title:_sgLang['files_upload'],
	    				doSize:true,
		    			collapsible:false,
		    			minimizable:false,
		    			maximizable:false,
		    			resizable:false,
		    			onOpen: function() {
	            			$('body').css('overflow','hidden');
	            			$('#sgProgress > span').html(_sgLang['uploaded']+' <span>'+sgFileId+'</span> '+_sgLang['from']+' '+total);
	            			$('#sgUploadCancel').click(function(e){
	            				uploadStateForm.window('close');
	            			})
		    			},
		    			onClose: function() {
                            workspace.fileapi('abort');
		    				sgHelper.destroyWindow(uploadStateForm);
                            sgHelper.update();
		    			}
		    		});
            	},
            	onProgress: function (e, uiE){
    				var part = uiE.loaded / uiE.total;
    				$('#sgProgress > div').css('width',100*part+'%');
				},
				onFilePrepare: function (e,uiE) {
					sgFileId++;
				},
				onFileProgress: function (e,uiE) {
					var part = uiE.loaded / uiE.total;
					$('.progress','#sgFilesListRow'+(sgFileId-1)).text(Math.floor(100*part)+'%');
				},
            	onFileComplete: function(e,uiE) {
                    if (uiE.result === undefined) return;
            		var errorCode = parseInt(uiE.result.data._FILES.sg_files.error);
            		if (errorCode) {
            			$('.progress','#sgFilesListRow'+(sgFileId-1)).html('<img src="'+_modxTheme+'/images/icons/error.png'+'" title="'+_sgUploadResult[errorCode]+'">');
            		}
    				$('#sgProgress > span > span').text(sgFileId);
            	},
            	onComplete: function(e,uiE) {
                    sgFileId = 0;
            		var btn = $('#sg_files');
            		btn.replaceWith(btn.val('').clone(true));
            		e.widget.files = [];
            		e.widget.uploaded = [];
            		sgHelper.update();
            		$('#sgUploadCancel span').text(_sgLang['close']);
                    if (!uiE.error) $('#sgUploadCancel').trigger('click');
            	},
            	elements: {
          			dnd: {
	        			el: '.js-fileapi-wrapper',
			        	hover: 'dnd_hover'
      				}
            	}
        	});
            $('#sg_pages').pagination({
			    total:0,
			    pageSize:10,
	    		buttons: [
                    {
					    iconCls:'btn-deleteAll btn-extra',
					    handler:function(){sgHelper.deleteAll();}
				    },
                    {
                        iconCls:'btn-move btn-extra',
                        handler:function() {sgHelper.move();}
                    },
                    {
                        iconCls:'btn-placeTop btn-extra',
                        handler:function() {sgHelper.placeTop();}
                    },
                    {
                        iconCls:'btn-placeBottom btn-extra',
                        handler:function() {sgHelper.placeBottom();}
                    }
                ],
			    onSelectPage:function(pageNumber, pageSize){
					$(this).pagination('loading');
						$.post(_xtAjaxUrl, { rows: pageSize, page: pageNumber, sg_rid: rid }, function(data) {
                            data = sgHelper.getData(data);
                            if (data.success) {
                                $('#sg_pages').pagination('refresh',{total: data.total});
								sgHelper.renderImages(data.rows);
							}
						});
					$(this).pagination('loaded');
                    $('.btn-extra').parent().parent().hide();
				}
			});
		},
        update: function() {
            $('#sg_pages').pagination('select');
        },
        destroyWindow: function(wnd) {
            wnd.window('destroy',true);
            $('.window-shadow,.window-mask').remove();
            $('body').css('overflow','auto');
        },
        getData: function(data) {
            if (sgHelper.isValidJSON(data)) {
                data = $.parseJSON(data);
                if (data.rows !== undefined) data.success = true;
            } else {
                data = {
                    success:false
                }
            }
            return data;
        },
		renderImages: function(rows) {
            var len = rows.length;
			var placeholder = $('#sg_images');
			placeholder.html('');
			for (i = 0; i < len; i++) {
                rows[i].sg_properties = $.parseJSON(rows[i].sg_properties);
                var context = {
                    data: rows[i],
                    sgLang: _sgLang,
                    thumbPrefix: _xtThumbPrefix
                };
 				var image = $(Handlebars.templates.preview(context));
 				image.data('properties',rows[i]);
 				placeholder.append(image);
			}
			this.initImages();
		},
		initImages: function() {
			var _this = this;
			$(document).keydown(function(e) {
        		return !_this.selectAll(e);
    		});
    		$('.del','.sg_image').click(function(e) {
    			_this.delete($(this).parent());
    		});
    		$('.edit','.sg_image').click(function(e) {
    			_this.edit($(this).parent());
    		});
            var _image = $('.sg_image');
			_image.unbind();
    		_image.click(function(e) {
        		_this.unselect();
        		_this.select($(this), e);
    		});
    		_image.dblclick(function() {
		        _this.unselect();
		        _this.edit($(this));
		    });
		    $('body').attr('ondragstart','');
		    if (sgSort !== null) sgSort.destroy();
		    sgSort = new Sortable(sg_images,{
		    	draggable: '.sg_image',
		    	onStart: function (e) {
		    		sgBeforeDragState = {
		    			prev: e.item.previousSibling != null ? $(e.item.previousSibling).data('properties').sg_index : -1,
		    			next: e.item.nextSibling != null ? $(e.item.nextSibling).data('properties').sg_index : -1
		    		};
		    	},
		    	onEnd: function (e) {
					var sgAfterDragState = {
						prev: e.item.previousSibling != null ? $(e.item.previousSibling).data('properties').sg_index : -1,
		    			next: e.item.nextSibling != null ? $(e.item.nextSibling).data('properties').sg_index : -1
					};
					if (sgAfterDragState.prev == sgBeforeDragState.prev && sgAfterDragState.next == sgBeforeDragState.next) return;
					var source = $(e.item).data('properties');
					sourceIndex = parseInt(source.sg_index); 
					sourceId = source.sg_id;
					var target = e.item.nextSibling == null ? $(e.item.previousSibling).data('properties') : $(e.item.nextSibling).data('properties');
					targetIndex = parseInt(target.sg_index);
					if (targetIndex < sourceIndex && sgAfterDragState.next != -1) targetIndex++;

                    var tempIndex = targetIndex,
                        item = e.item;
					if (sourceIndex < targetIndex) {
						while(tempIndex >= sourceIndex) {
							$(item).data('properties').sg_index = tempIndex--;
							item = item.nextSibling == null ? item : item.nextSibling;
						}
					} else {
						while(tempIndex <= sourceIndex) {
							$(item).data('properties').sg_index = tempIndex++;
							item = item.previousSibling == null ? item : item.previousSibling;
						}
					}
					$.post(
						_xtAjaxUrl+'?mode=reorder', {
							sg_rid: rid, 
							sourceId: sourceId, 
							sourceIndex: sourceIndex, 
							targetIndex: targetIndex 
						},
						function(data) {
							data = sgHelper.getData(data);
							if(!data.success) {
                                sgHelper.update();
							} 
						}
					);
		    	}
		    });
		},
		unselect: function() {
		    if (document.selection && document.selection.empty)
        	document.selection.empty() ;
    		else if (window.getSelection) {
        		var sel = window.getSelection();
        		if (sel && sel.removeAllRanges)
        		sel.removeAllRanges();
    		}
		},
		select: function(image,e) {
			var _image = $('.sg_image');
            if(!sgLastChecked)
                    sgLastChecked = image;
		    if (e.ctrlKey || e.metaKey) {
		        if (image.hasClass('selected'))
		            image.removeClass('selected');
		        else
		            image.addClass('selected');
		    } else if (e.shiftKey) {
		    	var start = _image.index(image);
                var end = _image.index(sgLastChecked);
                _image.slice(Math.min(start,end), Math.max(start,end)+ 1).addClass('selected');

		    } else {
		        _image.removeClass('selected');
		        image.addClass('selected');
		        sgLastChecked=image;
		    }
		    var images = $('.sg_image.selected').get();
		    if(images.length) {
                $('.btn-extra').parent().parent().show();
		    } else {
                $('.btn-extra').parent().parent().hide();
		    }
		},
		selectAll: function(e) {
		    if ((!e.ctrlKey && !e.metaKey) || ((e.keyCode != 65) && (e.keyCode != 97)))
			        return false;
		    var images = $('.sg_image').get();
		    if (images.length) {
		        $.each(images, function(i, image) {
		            if (!$(image).hasClass('selected'))
		                $(image).addClass('selected');
			        });
		    }
		    $('.btn-extra').parent().parent().show();
		    return true;
		},
        getSelected: function() {
            var ids = [];
            var images = $('.sg_image.selected');
            if (images.length) {
                $.each(images, function(i, image) {
                    ids.push($(image).data('properties').sg_id);
                });
            }
            ids = ids.join();
            return ids;
        },
		delete: function(image) {
			var id = image.data('properties').sg_id;
			$.messager.confirm(_sgLang['delete'],_sgLang['are_you_sure_to_delete'],function(r){
    			if (r){
        			$.post(
						_xtAjaxUrl+'?mode=remove', 
						{
                            id:id,
                            sg_rid:rid
                        },
						function(data) {
                            data = sgHelper.getData(data);
							if(data.success) {
								sgHelper.update();
							} else {
								$.messager.alert(_sgLang['error'],_sgLang['delete_fail']);
							}
						}
					);
    			}
			});
		},
		deleteAll: function() {
			var ids = this.getSelected();
		    $.messager.confirm(_sgLang['delete'],_sgLang['are_you_sure_to_delete_many'],function(r){
    			if (r){
        			$.post(
						_xtAjaxUrl+'?mode=removeAll', 
						{
                            ids:ids,
                            sg_rid:rid
                        },
						function(data) {
                            data = sgHelper.getData(data);
							if(data.success) {
								sgHelper.update();
							} else {
								$.messager.alert(_sgLang['error'],_sgLang['delete_fail']);
							}
						}
					);
    			}
			});
		},
        move: function() {
            var ids = this.getSelected();
            $.messager.confirm(_sgLang['move'],_sgLang['are_you_sure_to_move'],function(r){
                if (r){
                    var context = {
                        modxTheme: _modxTheme,
                        sgLang: _sgLang
                    };
                    var moveForm = $(Handlebars.templates.moveForm(context));
                    moveForm.window({
                        modal:true,
                        title:_sgLang['move'],
                        doSize:true,
                        collapsible:false,
                        minimizable:false,
                        maximizable:false,
                        resizable:false,
                        onOpen: function() {
                            $('#sgMoveStart').click(function(e){
                                _to = $('#sgMoveTo').val();
                                if (!_to || _to === rid) return;
                                $.post(
                                    _xtAjaxUrl+'?mode=move',
                                    {
                                        sg_rid:rid,
                                        ids:ids,
                                        to:_to
                                    },
                                    function(data) {
                                        data = sgHelper.getData(data);
                                        if(data.success) {
                                            sgHelper.update();
                                            moveForm.window('close');
                                        } else {
                                            $.messager.alert(_sgLang['error'],_sgLang['move_fail']);
                                        }
                                    }
                                );
                            })
                        },
                        onClose: function() {
                            sgHelper.destroyWindow(moveForm);
                        }
                    });
                }
            });
        },
        placeTop: function() {
            var ids = this.getSelected();
            $.messager.confirm(_sgLang['move'],_sgLang['are_you_sure_to_move'],function(r){
                if (r){
                    $.post(
                        _xtAjaxUrl+'?mode=place',
                        {
                            sg_rid:rid,
                            ids:ids,
                            dir:"top"
                        },
                        function(data) {
                            data = sgHelper.getData(data);
                            if(data.success) {
                                sgHelper.update();
                            } else {
                                $.messager.alert(_sgLang['error'],_sgLang['move_fail']);
                            }
                        }
                    );
                }
            });
        },
        placeBottom: function() {
            var ids = this.getSelected();
            $.messager.confirm(_sgLang['move'],_sgLang['are_you_sure_to_move'],function(r){
                if (r){
                    $.post(
                        _xtAjaxUrl+'?mode=place',
                        {
                            sg_rid:rid,
                            ids:ids,
                            dir:"bottom"
                        },
                        function(data) {
                            data = sgHelper.getData(data);
                            if(data.success) {
                                sgHelper.update();
                            } else {
                                $.messager.alert(_sgLang['error'],_sgLang['move_fail']);
                            }
                        }
                    );
                }
            });
        },
		edit: function(image) {
			var data = image.data('properties');
            var context = {
                data: data,
                modxTheme: _modxTheme,
                modxSiteUrl: _modxSiteUrl,
                sgLang: _sgLang
            };
			var editForm = $(Handlebars.templates.editForm(context));
			editForm.window({
    			modal:true,
    			title:sgHelper.escape(this.stripText(data.sg_title,80)),
    			doSize:true,
    			collapsible:false,
    			minimizable:false,
    			maximizable:false,
    			resizable:false,
    			onOpen: function() {
    				$('#sgEditCancel').click(function(e){
    					editForm.window('close',true);
    				});
    				$('#sgEditSave').click(function(e){
    					$.post(
						_xtAjaxUrl+'?mode=edit', 
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
		},
		bytesToSize: function(bytes) {
		   if(bytes == 0) return '0 B';
		   var k = 1024;
		   var sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
		   var i = Math.floor(Math.log(bytes) / Math.log(k));
		   return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i];
		},
		stripText: function(str,len) {
			str.replace(/<\/?[^>]+>/gi, '');
			if (str.length > len) str = str.slice(0,len) + '...';
			return str;
		},
		escape: function(str) {
			return str
			    .replace(/&/g, '&amp;')
			    .replace(/>/g, '&gt;')
			    .replace(/</g, '&lt;')
			    .replace(/"/g, '&quot;');
		},
		isValidJSON: function(src) {
		    var filtered = src;
		    filtered = filtered.replace(/\\["\\\/bfnrtu]/g, '@');
		    filtered = filtered.replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']');
		    filtered = filtered.replace(/(?:^|:|,)(?:\s*\[)+/g, '');
		    return (/^[\],:{}\s]*$/.test(filtered));
		},
		refresh: function() {
			var templates = [],
				total = 0;
			function processRefresh() {
			    $.ajax({
		            url: _xtAjaxUrl+'?mode=processRefresh',
		            type: 'POST',
		            timeout: 25000,
		            data: {
		            	template:templates
		            },
		            success: function(data, textStatus){
                        data = sgHelper.getData(data);
						if(data.success) {
							refreshStatus();
						} else {
							$.messager.alert(_sgLang['error'],_sgLang['refresh_fail']);
						}
		            },
		            complete: function(xhr, textStatus){
		                if (textStatus != "success") {
                            refreshStatus();		
                        }
		            }
			    });
			}
			function refreshStatus() {
				$.post(
					_xtAjaxUrl+'?mode=getRefreshStatus', 
					{
						template:templates
					},
					function(data) {
                        data = sgHelper.getData(data);
						if(data.success) {
							$('#sgRefreshProgress > span').text(data.processed+' '+_sgLang['from']+' '+total);
							var part = data.processed / total;
    						$('#sgRefreshProgress > div').css('width',100*part+'%');
    						if (data.processed < total) {
    							processRefresh();
    						} else {
    							$('#sgRefreshStart > div').html('<img src="'+_modxTheme+'/images/icons/delete.png"><span>'+_sgLang['close']+'</span>');
    							$('#sgRefreshStart').unbind('click').click(function(e) {
    								$('#sgRefresh').window('close');
    							})
    						}
						} else {
							$.messager.alert(_sgLang['error'],_sgLang['refresh_fail']);
						}
					}
				);
			}
			$.messager.confirm(_sgLang['refresh_previews'],_sgLang['are_you_sure_to_refresh'],function(r){
    			if (r){
    				var tpls = $.parseJSON(_xtTpls);
	            	$.each(tpls,function(i,tpl) {
	            		tpl.templatename = sgHelper.stripText(tpl.templatename,21);
	            	});
                    var context = {
                        tpls: tpls,
                        sgLang: _sgLang,
                        modxTheme: _modxTheme
                    };
    				var refreshForm = $(Handlebars.templates.refreshForm(context));
	            	refreshForm.window({
	    				width:450,
	    				modal:true,
	    				title:_sgLang['refresh_previews'],
	    				doSize:true,
		    			collapsible:false,
		    			minimizable:false,
		    			maximizable:false,
		    			resizable:false,
		    			onOpen: function() {
	            			$('body').css('overflow','hidden');
	            			$('#sgRefreshStart').click(function(e){
	            				templates = $('form','#sgRefreshTpls').serializeArray();
                                if (templates.length === 0) return;
					            $.post(
									_xtAjaxUrl+'?mode=initRefresh', 
									{
										template:templates
									},
									function(data) {
                                        data = sgHelper.getData(data);
										if(data.success) {
											total = data.total;
											refreshStatus();
										} else {
											$.messager.alert(_sgLang['error'],_sgLang['refresh_fail']);
										}
									}
								);
	            			})
		    			},
		    			onClose: function() {
		    				sgHelper.destroyWindow(refreshForm);
		    			}
		    		});
    			}
			});
		}
	}
})(jQuery);