[+jquery+]
<link rel="stylesheet" type="text/css" href="[+site_url+]assets/plugins/simplegallery/js/easy-ui/themes/bootstrap/easyui.css">
<link rel="stylesheet" type="text/css" href="[+site_url+]assets/plugins/simplegallery/js/easy-ui/themes/icon.css">
<script type="text/javascript" src="[+site_url+]assets/plugins/simplegallery/js/easy-ui/jquery.easyui.min.js"></script>
<script type="text/javascript" src="[+site_url+]assets/plugins/simplegallery/js/easy-ui/locale/easyui-lang-ru.js"></script>
<script type="text/javascript" src="[+site_url+]assets/plugins/simplegallery/js/fileapi/FileAPI.min.js"></script>
<script type="text/javascript" src="[+site_url+]assets/plugins/simplegallery/js/fileapi/jquery.fileapi.min.js"></script>
<script type="text/javascript" src="[+site_url+]assets/plugins/simplegallery/js/sortable/Sortable.js"></script>
<style type="text/css">
#SimpleGallery .pagination select, #SimpleGallery .pagination input {
    width:auto;
    height:auto;
}
#SimpleGallery .pagination td {
    vertical-align: middle;
}
.js-fileapi-wrapper {
	min-height: 300px;
	opacity:1;
}
.dnd_hover {
	opacity:0.5;
}
.btn {
display: inline-block;
*display: inline;
*zoom: 1;
position: relative;
overflow: hidden;
cursor: pointer;
padding: 4px 15px;
vertical-align: middle;
border: 1px solid #ccc;
border-radius: 3px;
background-color: #f5f5f5;
background: -moz-linear-gradient(top, #fff 0%, #f5f5f5 49%, #ececec 50%, #eee 100%);
background: -webkit-linear-gradient(top, #fff 0%,#f5f5f5 49%,#ececec 50%,#eee 100%);
background: -o-linear-gradient(top, #fff 0%,#f5f5f5 49%,#ececec 50%,#eee 100%);
background: linear-gradient(to bottom, #fff 0%,#f5f5f5 49%,#ececec 50%,#eee 100%);
-webkit-user-select: none;
-moz-user-select: none;
user-select: none;
}
.btn:hover {
	border-color: #fa0;
	box-shadow: 0 0 2px #fa0;
}

.btn-text {
	font-weight: bold;
}
.btn-text img {
	vertical-align: top;
	margin-right: 2px;
}
.btn-input {
	cursor: pointer;
	opacity: 0;
	filter:progid:DXImageTransform.Microsoft.Alpha(opacity=0);
	top: -10px;
	right: -40px;
	font-size: 50px;
	position: absolute;
}
.btn-right {
	float: right;
	margin-left: 10px;
}
#sg_pages {
	margin-top:20px;
	background:#efefef;
	border:1px solid #ccc;
}
.btn-deleteAll {
	background: url([+manager_url+]media/style/[+theme+]/images/icons/trash.png) -2px center no-repeat;
}
#sg_images {
	margin:20px 0;
}
.sg_image {
	display:inline-block;
	border:1px solid #ccc;
	margin:0 10px 10px 0;
	padding:5px;
	border-radius: 5px;
	background: none;
	width:[+w+]px;
	position:relative;
}
.sg_image:hover {
	background: #ddebf8;
}
.selected,.selected:hover {
	background: #5b9bda;
}
.sg_image img {
	width:[+w+]px;
	height:[+h+]px;
}
.sg_image .name {
	font-weight: bold;
    height: 32px;
    margin-top: 5px;
    overflow: hidden;
    text-align: center;
    font-size:11px;
    font-family:Tahoma,Verdana,Arial,sans-serif;
}
.sg_image .del {
	width:16px;
	height:16px;
	display: block;
	position: absolute;
	top:-6px;
	right:-6px;
	background: url([+manager_url+]media/style/[+theme+]/images/icons/delete.png) 0 0 no-repeat;
}
.sortable-ghost {
	opacity: .2;
	cursor: move;
}
#sgEdit img {
	max-width: 100%;
	max-height: 210px;
}
#sgEdit .sgRow {
	width: 300px;
	float:left;
}
#sgEdit .sgRow > div {
	padding:10px;
}
#sgEdit table {
	width:100%;
}
#sgEdit table td {
	padding:3px 5px;
	vertical-align: top;
}
#sgEdit table td.rowTitle {
	font-weight: bold;
	text-align: right;
}
#sgForm label {
	display: block;
	margin:3px 0;
	font-weight: bold;
}
#sgForm input[type="text"], #sgForm textarea {
	width:270px;
	padding:4px;
	margin-bottom:10px;
}
#sgForm input[type="checkbox"] {
	vertical-align: bottom;
}
#sgForm textarea {
	height:112px;
}
#sgProgress {
	padding:10px;
}
#sgProgress div {
	width:0;
	height:10px;
	background: green;
	transition: width 0.5s ease;
	border-radius: 5px;
}
#sgFilesList{
	height:285px;
	overflow-y: scroll;
}
#sgUploadState table {
	width:100%;
}
#sgUploadState table td, #sgUploadState table th {
	padding:3px 5px;
}
#sgUploadState .sgrow1 {
	width:251px;
}
#sgUploadState .sgrow2 {
	width:65px;
	text-align: center;
}
#sgUploadState .sgrow3 {
	text-align: center;
}
#sgFilesList table tbody tr:nth-child(even) {
	background-color: #f5f5f5;
}
#sgFilesList table tbody tr:nth-child(odd) {
	background-color: #fff;
}
</style>
<script type="text/javascript">
var rid = [+id+],
    sgLoaded = false,
    sgTotal = [+total+],
    sgSort = null,
    sgFileId = 0,
    sgLastChecked = null;
(function($){

	$.fn.pagination.defaults.pageList = [50,100,150,200];
	var sgHelper = {
		init: function() {
			$('#SimpleGallery').append('<div class="js-fileapi-wrapper"><div class="btn"><div class="btn-text"><img src="[+manager_url+]media/style/[+theme+]/images/icons/folder_page_add.png">Загрузить</div><input id="sg_files" name="sg_files" class="btn-input" type="file" multiple /></div><div id="sg_refresh" class="btn-right btn"><div class="btn-text"><img src="[+manager_url+]media/style/[+theme+]/images/icons/refresh.png">Обновить превью</div></div><div id="sg_pages"></div><div id="sg_images"></div><div style="clear:both;"></div></div>');
			$('#sg_refresh').click(function(){
		    	sgHelper.refresh();
		    });
			$('#SimpleGallery').fileapi({
            	url: '[+site_url+]assets/plugins/simplegallery/ajax.php?mode=upload',
            	autoUpload: true,
            	accept: 'image/*',
            	multiple: true,
            	clearOnSelect: true,
            	data: {
            		'sg_rid': [+id+]
            	},
            	filterFn: function (file, info) {
            		return /jpeg|gif|png$/.test(file.type) 
            	},
            	onBeforeUpload: function(e,uiE) {
	            	var uploadStateForm = $('<div id="sgUploadState"><div id="sgProgress"><span></span><div></div></div><table><thead><tr><th class="sgrow1">Файл</th><th class="sgrow2">Размер</th><th class="sgrow3">Прогресс</th></tr></thead></table><div id="sgFilesList"><table><tbody></tbody></table></div><div style="clear:both;padding:10px;float:right;"><div id="sgUploadCancel" class="btn btn-right"><div class="btn-text"><img src="[+manager_url+]media/style/[+theme+]/images/icons/stop.png"><span>Отменить</span></div></div></div></div>');
	            	$.each(uiE.files,function(i,file){
	            		$('tbody',uploadStateForm).append('<tr id="sgFilesListRow'+(i+1)+'"><td class="sgrow1">'+file.name+'</td><td class="sgrow2">'+sgHelper.bytesToSize(file.size)+'</td><td class="sgrow3 progress"></td></tr>')
	            	});
	            	uploadStateForm.window({
	    				width:450,
	    				modal:true,
	    				title:'Загрузка файлов',
	    				doSize:true,
		    			collapsible:false,
		    			minimizable:false,
		    			maximizable:false,
		    			resizable:false,
		    			onOpen: function() {
		    				/*$('#sgEditCancel').click(function(e){
		    					$('#sgEdit').window('close',true);
		    				})*/
	            			$('body').css('overflow','hidden');
	            			$('#sgUploadCancel').click(function(e){
	            				$('#sgUploadState').window('close');
	            			})
		    			},
		    			onClose: function() {
		    				$('#SimpleGallery').fileapi('abort');
		    				$('#sgUploadState').window('destroy',true);
		    				$('.window-shadow,.window-mask').remove();
		    				$('body').css('overflow','auto');
		    			}
		    		});
            	},
            	onProgress: function (e, uiE){
    				var part = uiE.loaded / uiE.total;
    				var total = uiE.files.length;
    				$('#sgProgress > div').css('width',100*part+'%');
    				$('#sgProgress > span').text('Загружено '+Math.floor(total * part)+' из '+total);
				},
				onFilePrepare: function (e,uiE) {
					sgFileId++;
				},
				onFileProgress: function (e,uiE) {
					var part = uiE.loaded / uiE.total;
					$('.progress','#sgFilesListRow'+sgFileId).text(Math.floor(100*part)+'%');
				},
            	onFileComplete: function(e,uiE) {
            	},
            	onComplete: function(e,uiE) {
            		sgFileId = 0;
            		var btn = $('#sg_files');
            		btn.replaceWith(btn.val('').clone(true));
            		e.widget.files = [];
            		e.widget.uploaded = [];
            		$('#sg_pages').pagination('select');
            		$('#sgUploadCancel span').text('Закрыть');
            	},
            	elements: {
          			dnd: {
	        			el: '.js-fileapi-wrapper',
			        	hover: 'dnd_hover'
      				}
            	}
        	});
        	$('#sg_pages').pagination({
			    total:[+total+],
			    pageSize:10,
	    		buttons: [{
					iconCls:'btn-deleteAll',
					handler:function(){sgHelper.deleteAll();}
				}],
			    onSelectPage:function(pageNumber, pageSize){
					$(this).pagination('loading');
						$.post("[+url+]", { rows: pageSize, page: pageNumber, sg_rid: [+id+] }, function(data) {
							var result = $.parseJSON(data);
							$('#sg_pages').pagination('refresh',{total: result.total});
							sgHelper.renderImages(result.rows);
						});
					$(this).pagination('loaded');
				}
			});
			$('.btn-deleteAll').parent().parent().hide();
		},
		renderImages: function(rows) {
			var len = rows.length;
			var placeholder = $('#sg_images');
			placeholder.html('');
			for (i = 0; i < len; i++) {
 				var image = $('<div class="sg_image"><a href="javascript:void(0)" class="del"></a><img title="'+rows[i].sg_description+'" src="[+thumb_prefix+]'+rows[i].sg_image+'"><div class="name">'+rows[i].sg_title+'</div></div>');
 				rows[i].sg_properties = $.parseJSON(rows[i].sg_properties);
 				image.data('properties',rows[i]);
 				placeholder.append(image);
			}
			this.initImages();
		},
		initImages: function() {
			$(document).keydown(function(e) {
        		return !sgHelper.selectAll(e);
    		});
    		$('.del','.sg_image').click(function(e) {
    			sgHelper.delete($(this).parent());
    		});
			$('.sg_image').unbind();
    		$('.sg_image').click(function(e) {
        		sgHelper.unselect();
        		sgHelper.select($(this), e);
    		});
    		$('.sg_image').dblclick(function() {
		        sgHelper.unselect();
		        sgHelper.edit($(this));
		    });
		    $('.sg_image').mouseup(function() {
		        sgHelper.unselect();
		    });
		    $('.sg_image').mouseout(function() {
		        sgHelper.unselect();
		    });
		    $('body').attr('ondragstart','');
		    if (sgSort !== null) sgSort.destroy();
		    sgSort = new Sortable(sg_images);
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
			if(!sgLastChecked)
                    sgLastChecked = image;
		    if (e.ctrlKey || e.metaKey) {
		        if (image.hasClass('selected'))
		            image.removeClass('selected');
		        else
		            image.addClass('selected');
		    } else if (e.shiftKey) {
		    	var start = $('.sg_image').index(image);
                var end = $('.sg_image').index(sgLastChecked);
                $('.sg_image').slice(Math.min(start,end), Math.max(start,end)+ 1).addClass('selected');

		    } else {
		        $('.sg_image').removeClass('selected');
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
			$.messager.confirm('Удаление','Вы точно хотите удалить картинку?',function(r){
    			if (r){
        			$.post(
						"[+url+]?mode=remove", 
						{id:id},
						function(data) {
							data = $.parseJSON(data);
							if(data.success) {
								$('#sg_pages').pagination('select');
							} else {
								$.messager.alert('Ошибка','Не удалось удалить картинку');
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
		    $.messager.confirm('Удаление','Вы точно хотите удалить выделенные картинки?',function(r){
    			if (r){
        			$.post(
						"[+url+]?mode=removeAll", 
						{ids:ids},
						function(data) {
							data = $.parseJSON(data);
							if(data.success) {
								$('#sg_pages').pagination('select');
								$('.btn-deleteAll').parent().parent().hide();
							} else {
								$.messager.alert('Ошибка','Не удалось удалить');
							}
						}
					);
    			}
			});
		},
		edit: function(image) {
			var data = image.data('properties');
			var editForm = $('<div id="sgEdit"><div class="sgRow"><div style="font-size:0;text-align:center;"><img src="[+site_url+]'+data.sg_image+'"></div><div><table><tr><td class="rowTitle">ID</td><td>'+data.sg_id+'</td></tr><tr><td class="rowTitle">Файл</td><td>'+data.sg_image+'</td></tr><tr><td class="rowTitle">Размер</td><td>'+data.sg_properties.width+'x'+data.sg_properties.height+', '+sgHelper.bytesToSize(data.sg_properties.size)+'</td></tr><tr><td class="rowTitle">Добавлен</td><td>'+data.sg_createdon+'</td></tr></table></div></div><div class="sgRow"><div><form id="sgForm"><input type="hidden" name="sg_id" value="'+data.sg_id+'"><input type="hidden" name="sg_rid" value="'+data.sg_rid+'"><label>Название</label><input name="sg_title" type="text" value="'+data.sg_title+'"><label>Описание</label><textarea name="sg_description">'+data.sg_description+'</textarea><label>Дополнительно</label><input name="sg_add" type="text"><label>Показывать</label><input type="checkbox" name="sg_active" value="1" '+ (!!data.sg_isactive ? 'checked' : '')+'>Да</form></div></div><div style="clear:both;padding:10px;float:right;"><div id="sgEditSave" class="btn btn-right"><div class="btn-text"><img src="[+manager_url+]media/style/[+theme+]/images/icons/save.png">Сохранить</div></div><div id="sgEditCancel" class="btn btn-right"><div class="btn-text"><img src="[+manager_url+]media/style/[+theme+]/images/icons/stop.png">Отменить</div></div></div></div>');
			editForm.window({
    			modal:true,
    			title:data.sg_title,
    			doSize:true,
    			collapsible:false,
    			minimizable:false,
    			maximizable:false,
    			resizable:false,
    			onOpen: function() {
    				$('#sgEditCancel').click(function(e){
    					$('#sgEdit').window('close',true);
    				})
    			},
    			onClose: function() {
    				$('#sgEdit').window('destroy',true);
    				$('.window-shadow,.window-mask').remove();
    			}
    		});
		},
		bytesToSize: function(bytes) {
		   if(bytes == 0) return '0 байт';
		   var k = 1024;
		   var sizes = ['байт', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
		   var i = Math.floor(Math.log(bytes) / Math.log(k));
		   return (bytes / Math.pow(k, i)).toPrecision(3) + ' ' + sizes[i];
		},
		refresh: function() {
			$.messager.confirm('Обновление превью','Эта операция может занять много времени. Вы точно хотите продолжить?',function(r){
    			if (r){
        			$.post(
						"[+url+]?mode=refresh", 
						{},
						function(data) {
							data = $.parseJSON(data);
							
						}
					);
    			}
			});
		}
	}
	$(window).load(function(){
    	if ($('#sg-tab')) {
    		$('#sg-tab.selected').trigger('click');    
		}
	})
	$(document).ready(function() {
		$('#sg-tab').click(function(){
    		if (sgLoaded) {
        		$('#sg_pages').pagination('select');
    		} else {
        		sgHelper.init();
        		$('#sg_pages').pagination('select');
        		sgLoaded = true;
    		}
		})
	})
})(jQuery);
</script>
<div id="SimpleGallery" class="tab-page" style="display:none;width:100%;-moz-box-sizing: border-box; box-sizing: border-box;">
<h2 class="tab" id="sg-tab">[+tabName+]</h2>
</div>