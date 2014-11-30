var sgHelper = {};
(function($){
	$.fn.pagination.defaults.pageList = [50,100,150,200];
	sgHelper = {
        init: function() {
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
            		return /jpeg|gif|png$/.test(file.type) 
            	},
            	onBeforeUpload: function(e,uiE) {
	            	var total = uiE.files.length;
	            	var uploadStateForm = $('<div id="sgUploadState"><div id="sgProgress"><span></span><div></div></div><table><thead><tr><th class="sgrow1">'+_sgLang['file']+'</th><th class="sgrow2">'+_sgLang['size']+'</th><th class="sgrow3">'+_sgLang['progress']+'</th></tr></thead></table><div id="sgFilesList"><table><tbody></tbody></table></div><div style="clear:both;padding:10px;float:right;"><div id="sgUploadCancel" class="btn btn-right"><div class="btn-text"><img src="'+_modxTheme+'/images/icons/stop.png"><span>'+_sgLang['cancel']+'</span></div></div></div></div>');
	            	$.each(uiE.files,function(i,file){
	            		$('tbody',uploadStateForm).append('<tr id="sgFilesListRow'+(i+1)+'"><td class="sgrow1">'+file.name+'</td><td class="sgrow2">'+sgHelper.bytesToSize(file.size)+'</td><td class="sgrow3 progress"></td></tr>')
	            	});
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
					$('.progress','#sgFilesListRow'+sgFileId).text(Math.floor(100*part)+'%');
				},
            	onFileComplete: function(e,uiE) {
                    if (uiE.result === undefined) return;
            		var errorCode = parseInt(uiE.result.data._FILES.sg_files.error);
            		if (errorCode) {
            			$('.progress','#sgFilesListRow'+sgFileId).html('<img src="'+_modxTheme+'/images/icons/error.png'+'" title="'+_sgUploadResult[errorCode]+'">');
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
	    		buttons: [{
					iconCls:'btn-deleteAll',
					handler:function(){sgHelper.deleteAll();}
				}],
			    onSelectPage:function(pageNumber, pageSize){
					$(this).pagination('loading');
						$.post(_xtAjaxUrl, { rows: pageSize, page: pageNumber, sg_rid: rid }, function(data) {
							if (sgHelper.isValidJSON(data)) {
								var result = $.parseJSON(data);
                                $('#sg_pages').pagination('refresh',{total: result.total});
								sgHelper.renderImages(result.rows);
							}
						});
					$(this).pagination('loaded');
				}
			});
			$('.btn-deleteAll').parent().parent().hide();
		},
        update: function() {
            $('#sg_pages').pagination('select');
        },
        destroyWindow: function(wnd) {
            wnd.window('destroy',true);
            $('.window-shadow,.window-mask').remove();
            $('body').css('overflow','auto');
        },
		renderImages: function(rows) {
			var len = rows.length;
			var placeholder = $('#sg_images');
			placeholder.html('');
			for (i = 0; i < len; i++) {
 				var image = $('<div class="sg_image"><a href="javascript:void(0)" class="del" title="'+_sgLang['delete']+'"></a><img title="'+this.escape(this.stripText(rows[i].sg_description,100))+'" src="'+_xtThumbPrefix+rows[i].sg_image+'"><div class="name'+(parseInt(rows[i].sg_isactive) ? '' : ' notactive')+'">'+rows[i].sg_title+'</div></div>');
 				if (!rows[i].sg_description.length) image.append('<a href="javascript:void(0)" class="edit" title="'+_sgLang['emptydesc']+'"></a>');
 				rows[i].sg_properties = $.parseJSON(rows[i].sg_properties);
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
							if (sgHelper.isValidJSON(data)) {
								data = $.parseJSON(data);
							} else {
								data = {
									success:false
								}
							}
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
		    if(images.length > 1) {
		    	$('.btn-deleteAll').parent().parent().show();
		    } else {
		    	$('.btn-deleteAll').parent().parent().hide();
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
		    $('.btn-deleteAll').parent().parent().show();
		    return true;
		},
		delete: function(image) {
			var id = image.data('properties').sg_id;
			$.messager.confirm(_sgLang['delete'],_sgLang['are_you_sure_to_delete'],function(r){
    			if (r){
        			$.post(
						_xtAjaxUrl+'?mode=remove', 
						{id:id},
						function(data) {
							if (sgHelper.isValidJSON(data)) {
								data = $.parseJSON(data);
							} else {
								data = {
									success:false
								}
							}
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
			var ids = [];
			var images = $('.sg_image.selected');
		    if (images.length) {
		        $.each(images, function(i, image) {
		        	ids.push($(image).data('properties').sg_id);
			    });
		    }
		    ids = ids.join();
		    $.messager.confirm(_sgLang['delete'],_sgLang['are_you_sure_to_delete_many'],function(r){
    			if (r){
        			$.post(
						_xtAjaxUrl+'?mode=removeAll', 
						{ids:ids},
						function(data) {
							if (sgHelper.isValidJSON(data)) {
								data = $.parseJSON(data);
							} else {
								data = {
									success:false
								}
							}
							if(data.success) {
								sgHelper.update();
								$('.btn-deleteAll').parent().parent().hide();
							} else {
								$.messager.alert(_sgLang['error'],_sgLang['delete_fail']);
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
            context.data.sg_properties.size = this.bytesToSize(data.sg_properties.size);
            context.data.sg_isactive = parseInt(data.sg_isactive) ? 'checked' : '';
			var editForm = $(Handlebars.templates.edit(context));
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
							if (sgHelper.isValidJSON(data)) {
								data = $.parseJSON(data);
							} else {
								data = {
									success:false
								}
							}
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
		   return (bytes / Math.pow(k, i)).toFixed(0) + ' ' + sizes[i];
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
		                if (sgHelper.isValidJSON(data)) {
							data = $.parseJSON(data);
						} else {
							data = {
								success:false
							}
						}
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
						if (sgHelper.isValidJSON(data)) {
							data = $.parseJSON(data);
						} else {
							data = {
								success:false
							}
						}
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
    				var tpls = $.parseJSON(_xtTpls),
    					_tpls = '';
	            	$.each(tpls,function(i,tpl) {
	            		_tpls += '<label><input type="checkbox" value="'+tpl.id+'" name="template[]">'+sgHelper.stripText(tpl.templatename,21)+'</label>';
	            	});
    				var refreshForm = $('<div id="sgRefresh"><div id="sgRefreshTpls"><p>'+_sgLang['select_tpls']+'</p><form>'+_tpls+'</form></div><div id="sgRefreshProgress"><span></span><div></div></div><div style="clear:both;padding:10px;float:right;"><div id="sgRefreshStart" class="btn btn-right"><div class="btn-text"><img src="'+_modxTheme+'/images/icons/save.png"><span>'+_sgLang['continue']+'</span></div></div></div></div>');
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
										if (sgHelper.isValidJSON(data)) {
											data = $.parseJSON(data);
										} else {
											data = {
												success:false
											}
										}
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