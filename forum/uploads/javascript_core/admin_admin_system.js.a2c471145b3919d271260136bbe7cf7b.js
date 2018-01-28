ips.templates.set('menuManager.temporaryDropdown'," <li class='ipsMenu_item {{#selected}}cMenuManager_active{{/selected}}' data-itemID='temp' data-role='menuItem'>  <span>   <ul class='ipsList_inline ipsPos_right cMenuManager_tools'>    <li>     <a href='#' data-action='removeItem' data-ipsTooltip title='{{#lang}}menuManagerRemoveItem{{/lang}}' class='ipsType_blendLinks'>      <i class='fa fa-times'></i></i>     </a>    </li>   </ul>   {{#lang}}menuManagerNewItem{{/lang}}  </span> </li>");ips.templates.set('menuManager.temporaryMenuItem'," <li id='menu_{{id}}' data-role='menuNode'>  <div class='cMenuManager_leaf {{#selected}}cMenuManager_active{{/selected}}' data-itemID='temp' data-role='menuItem'>   <ul class='ipsList_inline ipsPos_right cMenuManager_tools'>    <li>     <a href='#' data-action='removeItem' data-ipsTooltip title='{{#lang}}menuManagerRemoveItem{{/lang}}' class='ipsType_blendLinks'>      <i class='fa fa-times'></i></i>     </a>    </li>   </ul>   <h3 class='cMenuManager_leafTitle'>{{#lang}}menuManagerNewItem{{/lang}}</h3>  </div> </li>");ips.templates.set('menuManager.emptyList'," <li class='cMenuManager_emptyList ipsType_light ipsType_center'>{{#lang}}menuManagerEmptyList{{/lang}}</li>");;
;(function($,_,undefined){"use strict";ips.controller.register('core.admin.system.api',{initialize:function(){this.on('click','[data-action="showEndpoint"]',this.showEndpoint);this.on('click','[data-action="toggleBranch"]',this.toggleBranch);},toggleBranch:function(e){e.preventDefault();var branchTrigger=$(e.currentTarget);var branchItem=branchTrigger.parent();if(branchItem.hasClass('cApiTree_inactiveBranch')){ips.utils.anim.go('fadeInDown',branchItem.find(' > ul'));branchItem.removeClass('cApiTree_inactiveBranch').addClass('cApiTree_activeBranch');}else{branchItem.find(' > ul').hide();branchItem.removeClass('cApiTree_activeBranch').addClass('cApiTree_inactiveBranch');}},showEndpoint:function(e){e.preventDefault();var self=this;var url=$(e.currentTarget).attr('href');this.scope.find('.cApiTree_activeNode').removeClass('cApiTree_activeNode');$(e.currentTarget).parent('li').addClass('cApiTree_activeNode');this.scope.find('[data-role="referenceContainer"]').css({height:this.scope.find('[data-role="referenceContainer"]').height()}).html($('<div/>').addClass('ipsLoading').css({height:'300px'}));ips.getAjax()(url).done(function(response){self.scope.find('[data-role="referenceContainer"]').html(response).css({height:'auto'});}).fail(function(){window.location=url;});}});}(jQuery,_));;
;(function($,_,undefined){"use strict";ips.controller.register('core.admin.system.apiPermissions',{initialize:function(){this.on('click','[data-action="toggleSection"]',this.toggleSection);this.on('click','.cApiPermissions [data-action="checkAll"]',this.checkAll);this.on('click','.cApiPermissions [data-action="checkNone"]',this.checkNone);this.on('click','.cApiPermissions_header [data-action="checkAll"]',this.checkAllHeader);this.on('click','.cApiPermissions_header [data-action="checkNone"]',this.checkNoneHeader);this.setup();},setup:function(){var self=this;var sections=this.scope.find('.cApiPermissions > li');sections.each(function(){self._calculatedCheckedEndpoints($(this));});},toggleSection:function(e){var header=$(e.currentTarget).parent();if(header.hasClass('cApiPermissions_open')){this._collapseSection(header);}else{this._expandSection(header);}},checkAllHeader:function(e){e.preventDefault();var self=this;var header=$(e.currentTarget).closest('.cApiPermissions_header');var sections=header.next('.cApiPermissions').find('> li');sections.each(function(){var next=$(this).find('> ul');if(!next.is(':visible')){next.animationComplete(function(){setTimeout(function(){self._togglePermissions(true,next);},300);});self._expandSection($(this));}else{self._togglePermissions(true,next);}})},checkNoneHeader:function(e){e.preventDefault();var self=this;var header=$(e.currentTarget).closest('.cApiPermissions_header');var sections=header.next('.cApiPermissions').find('> li');sections.each(function(){var next=$(this).find('> ul');if(!next.is(':visible')){next.animationComplete(function(){setTimeout(function(){self._togglePermissions(false,next);},300);});self._expandSection($(this));}else{self._togglePermissions(false,next);}})},checkAll:function(e){e.preventDefault();var self=this;var header=$(e.currentTarget).parents('li').first();var next=header.find('> ul');if(!next.is(':visible')){next.animationComplete(function(){setTimeout(function(){self._togglePermissions(true,next);},300);});this._expandSection(header);}else{this._togglePermissions(true,next);}},checkNone:function(e){e.preventDefault();var self=this;var header=$(e.currentTarget).parents('li').first();var next=header.find('> ul');if(!next.is(':visible')){next.animationComplete(function(){setTimeout(function(){self._togglePermissions(false,next);},300);});this._expandSection(header);}else{this._togglePermissions(false,next);}},_calculatedCheckedEndpoints:function(section){var totalEndpoints=section.find('input[name*="access"]');var checkedEndpoints=totalEndpoints.filter(':checked');var endpointSpan=section.find('[data-role="endpointOverview"]');if(section.hasClass('cApiPermissions_open')){endpointSpan.hide();}else{var text=ips.getString('apiEndpoints_all');if(!checkedEndpoints.length){text=ips.getString('apiEndpoints_none');}else if(totalEndpoints.length!==checkedEndpoints.length){text=ips.pluralize(ips.getString('apiEndpoints_some',{checked:checkedEndpoints.length}),totalEndpoints.length);}
endpointSpan.text(text).show();}},_togglePermissions:function(state,container){container.find('input[type="checkbox"]:not( [disabled] )').prop('checked',state).change();},_expandSection:function(section){var next=section.find('> ul');section.addClass('cApiPermissions_open').removeClass('cApiPermissions_closed');ips.utils.anim.go('fadeInDown fast',next);this._calculatedCheckedEndpoints(section);},_collapseSection:function(section){section.removeClass('cApiPermissions_open').addClass('cApiPermissions_closed');this._calculatedCheckedEndpoints(section);},});}(jQuery,_));;
;(function($,_,undefined){"use strict";ips.controller.register('core.admin.system.buildApp',{initialize:function(){this.on('click',this.launchAlert);},launchAlert:function(e){var self=this;e.preventDefault();ips.ui.alert.show({type:'verify',message:ips.getString('build_app'),icon:'question',buttons:{yes:ips.getString('build_download'),no:ips.getString('download'),cancel:ips.getString('cancel')},callbacks:{yes:function(){window.location=self.scope.attr('data-buildURL');},no:function(){window.location=self.scope.attr('data-downloadURL');}}});}});}(jQuery,_));;
;(function($,_,undefined){"use strict";ips.controller.register('core.admin.system.codeHook',{codeMirror:null,initialize:function(){this.on('click','[data-codeToInject]',this._itemClick);},_itemClick:function(e){var codeMirror=$(this.scope).find('textarea').data('CodeMirrorInstance');var regex=new RegExp($.parseJSON($(e.currentTarget).attr('data-signature')));var found=false;var lastLine=codeMirror.doc.lineCount()-1;codeMirror.doc.eachLine(function(line){if(line.text.match(regex)){found=true;codeMirror.setSelection({line:codeMirror.doc.getLineNumber(line),ch:0},{line:codeMirror.doc.getLineNumber(line),ch:line.text.length});codeMirror.scrollIntoView({line:codeMirror.doc.getLineNumber(line),ch:0});}
if(line.text.match(/^\s*}\s*$/)){lastLine=codeMirror.doc.getLineNumber(line);}});if(!found){codeMirror.doc.replaceRange($.parseJSON($(e.currentTarget).attr('data-codeToInject')),{line:lastLine-1,chr:0},{line:lastLine-1,chr:0});codeMirror.scrollIntoView({line:codeMirror.doc.lineCount(),ch:0});}}});}(jQuery,_));;
;(function($,_,undefined){"use strict";ips.controller.register('core.admin.system.langString',{initialize:function(){this.on('click','[data-action="saveWords"]',this.saveWords);this.on('click','[data-action="revertWords"]',this.revertWords);this.setup();},setup:function(){var self=this;var textArea=$('<textarea />');textArea.attr('data-url',this.scope.attr('href')).val(this.scope.html()).change(function(e){self._change(e);});},_change:function(e){var elem=$(e.target);elem.addClass('ipsField_loading');var url=elem.attr('data-url')+'&form_submitted=1&csrfKey='+ips.getSetting('csrfKey')+'&lang_word_custom='+encodeURIComponent(elem.val());ips.getAjax()(url).done(function(){elem.removeClass('ipsField_loading');ips.ui.flashMsg.show(ips.getString('saved'));}).fail(function(){window.location=url;});}});}(jQuery,_));;
;(function($,_,undefined){"use strict";ips.controller.register('core.admin.system.login',{initialize:function(){this.on('tabChanged',this.tabChanged);this.on('click','[data-action="upgradeWarningContinue"]',this.upgradeWarningContinue);this.setup();},setup:function(){this.scope.find("#elTabContent .ipsTabs_panel:visible").first().find('input[type="text"]').first().focus();},tabChanged:function(e,data){ips.utils.cookie.set('acpLoginMethod',data.tabID,1);},upgradeWarningContinue:function(e,data){e.preventDefault();$(this.scope).find('[data-role="upgradeWarning"]').hide();$(this.scope).find('[data-role="loginForms"]').removeClass('ipsHide').show();}});}(jQuery,_));;
;(function($,_,undefined){"use strict";ips.controller.register('core.admin.system.menuManager',{_dropdownManager:null,_menuManager:null,_editForm:null,_previewWindow:null,_ajaxObj:null,_currentDropdown:0,_previewOpen:false,initialize:function(){this.on('click',"[data-action='editDropdown']",this.editDropdown);this.on('click',"[data-role='menuItem']",this.editItem);this.on('click',"[data-action='removeItem']",this.removeItem);this.on('click',"[data-action='newDropdown']",this.newDropdown);this.on('click',"[data-action='newItem']",this.newItem);this.on('click',"[data-action='navBack']",this.navBack);this.on('submit',"[data-role='editForm'] form",this.saveEditForm);this.on('click',"[data-action='previewToggle']",this.togglePreview);this.on(document,'menuToggle.acpNav',this.menuToggled);this.on(document,'click','[data-action="publishMenu"]:not( .ipsButton_disabled )',this.publishMenu);this.on(document,'click','[data-action="restoreMenu"]',this.restoreMenu);this.on(window,'beforeunload',this.windowUnload);this.setup();},setup:function(){var self=this;this._dropdownManager=this.scope.find('[data-manager="dropdown"]');this._menuManager=this.scope.find('[data-manager="main"]');this._editForm=this.scope.find("[data-role='editForm']");this._previewWindow=this.scope.find('[data-role="preview"]');this.scope.find('.cMenuManager_root > ol').nestedSortable({forcePlaceholderSize:true,handle:'div',helper:'clone',maxLevels:2,items:'[data-role="menuNode"]',isTree:true,errorClass:'cMenuManager_emptyError',placeholder:'cMenuManager_emptyHover',start:_.bind(this._startDragging,this),toleranceElement:'> div',tabSize:30,update:_.bind(this._update,this)});this._dropdownManager.find('[data-dropdownID] > ol').sortable({items:'> li',update:function(event,ui){self._changeOccurred();var parentID=ui.item.closest('[data-menuid]').attr('data-menuid');var items=$(this).sortable('toArray');var result=[];for(var i=0;i<items.length;i++){result.push("menu_order["+items[i].replace('menu_','')+"]="+parentID);}
ips.getAjax()('?app=core&module=applications&controller=menu&do=reorder&'+result.join('&')).done(function(){self._updatePreview();});}});this._positionPreviewWindow();},_update:function(){var self=this;this._changeOccurred();ips.getAjax()('?app=core&module=applications&controller=menu&do=reorder&'+this.scope.find('.cMenuManager_root > ol').nestedSortable('serialize',{key:'menu_order'})).done(function(){self._updatePreview();});},windowUnload:function(e){if(this._hasChanges){return ips.getString('menuPublishUnsaved');}},restoreMenu:function(e){e.preventDefault();var url=$(e.currentTarget).attr('href');ips.ui.alert.show({type:'confirm',icon:'warn',message:ips.getString('menuRestoreConfirm'),subText:ips.getString('menuRestoreConfirmSubtext'),callbacks:{ok:function(){window.location=url;}}});},publishMenu:function(e){e.preventDefault();var self=this;var button=$(e.currentTarget);var url=button.attr('href');button.attr('data-currentTitle',button.find('span').text()).addClass('ipsButton_disabled').find('span').text(ips.getString('publishing'));ips.getAjax()(url,{bypassRedirect:true}).done(function(response){ips.ui.flashMsg.show(ips.getString("publishedMenu"));self._hasChanges=false;button.find('span').text(button.attr('data-currentTitle'));}).fail(function(){window.location=url;});},togglePreview:function(e){e.preventDefault();var toggle=$(e.currentTarget);if(this._previewOpen){this._previewOpen=false;this._previewWindow.stop().animate({height:'48px'});}else{this._previewOpen=true;this._previewWindow.stop().animate({height:'350px'});}
toggle.find('[data-role="closePreview"]').toggleClass('ipsHide',!this._previewOpen);toggle.find('[data-role="openPreview"]').toggleClass('ipsHide',this._previewOpen);},newDropdown:function(e){e.preventDefault();var self=this;var newItem=ips.templates.render('menuManager.temporaryDropdown',{selected:true});var menuID=this._currentDropdown;var menu=this._dropdownManager.find('[data-menuID="'+menuID+'"]');var url=$(e.currentTarget).attr('href');this._unselectAllItems();var doNew=function(){menu.append(newItem);self._checkForEmptyDropdown(menu);self._loadEditForm(url,{parent:menuID});};this._checkTempBeforeCallback(doNew);},newItem:function(e){e.preventDefault();var self=this;var newItem=ips.templates.render('menuManager.temporaryMenuItem',{selected:true});var url=$(e.currentTarget).attr('href');this._unselectAllItems();var doNew=function(){self.scope.find('.cMenuManager_root > ol').prepend(newItem);self._loadEditForm(url,{parent:0});};this._checkTempBeforeCallback(doNew);},saveEditForm:function(e){e.preventDefault();var self=this;var form=$(e.currentTarget);var url=form.attr('action');var id=form.attr('data-itemID');var isNew=form.find('input[type="hidden"][name="newItem"]').val();ips.getAjax()(url,{type:'post',data:form.serialize()}).done(function(response){if(typeof response=='string'){self._editForm.html(response);$(document).trigger('contentChange',[self._editForm]);return;}
if(isNew){self.scope.find('[data-itemID="temp"]').closest('[data-role="menuNode"]').attr('id','menu_'+response.id).end().replaceWith(response.menu_item);}else{self.scope.find('[data-itemID="'+id+'"]').replaceWith(response.menu_item);}
if(response.dropdown_menu){var dropdownContent=$('<div>'+response.dropdown_menu+'</div>');dropdownContent.find('[data-dropdownid]').each(function(){var id=$(this).attr('data-dropdownid');if(self._dropdownManager.find('[data-dropdownid="'+id+'"]').length){self._dropdownManager.find('[data-dropdownid="'+id+'"]').html($(this).html());}else{var newDropdown=$('<div/>').attr('data-dropdownid',id).html($(this).html());self._dropdownManager.append(newDropdown);}});}
self._editForm.removeClass('cMenuManager_formActive').find('> div').fadeOut('fast',function(){$(this).remove();});ips.utils.anim.go('pulseOnce',self.scope.find('[data-itemID="'+id+'"]'));if(isNew){self.scope.find('.cMenuManager_root > ol').sortable('refreshPositions');self._update();}
self._changeOccurred();self._updatePreview();}).fail(function(){});},removeItem:function(e){e.preventDefault();var self=this;var removeIcon=$(e.currentTarget);var item=removeIcon.closest('[data-role="menuItem"]');var li=item.closest('li');var menu=item.closest('ol');var url=removeIcon.attr('href');if(item.attr('data-itemID')=='temp'){ips.utils.anim.go('fadeOutDown',li).done(function(){li.remove();self._checkForEmptyDropdown(menu);});this._unselectAllItems();}
var removeItem=function(){self._changeOccurred();ips.utils.anim.go('fadeOutDown',li);ips.getAjax()('?app=core&module=applications&controller=menu&do=remove&wasConfirmed=1&id='+item.attr('data-itemID'));};if(item.find('+ ol > li').length){ips.ui.alert.show({type:'confirm',icon:'warn',message:ips.getString('menuItemHasChildren'),callbacks:{ok:removeItem}});}else{removeItem();}},editItem:function(e){var self=this;var clickFocus=$(e.target);var item=$(e.currentTarget);var editURL=item.find('[data-action="editItem"]').attr('href');var doEdit=function(){if(clickFocus.closest('a').length){return;}
self._menuManager.find('.cMenuManager_active').removeClass('cMenuManager_active');self._dropdownManager.find('.cMenuManager_active').removeClass('cMenuManager_active');self._editForm.addClass('cMenuManager_formActive');item.addClass('cMenuManager_active');self._loadEditForm(editURL,{});};this._checkTempBeforeCallback(doEdit);},editDropdown:function(e){e.preventDefault();var self=this;var icon=$(e.currentTarget);var dropdownID=icon.closest('[data-itemID]').attr('data-itemID');var menuWrapper=this._dropdownManager.find('[data-dropdownID="'+dropdownID+'"]');var doEdit=function(){if(!self._currentDropdown){self.scope.find('.cMenuManager_column').addClass('cMenuManager_readyToSlide');self._dropdownManager.find('[data-dropdownID]').hide();menuWrapper.show();self.scope.find('[data-manager="dropdown"], [data-manager="main"]').show().animate({left:"-50%"},function(){self.scope.find('.cMenuManager_column').addClass('cMenuManager_readyToSlide');});self._currentDropdown=0;}else{var currentMenuWrapper=self._dropdownManager.find('[data-dropdownID="'+self._currentDropdown+'"]');currentMenuWrapper.find(' > ol').fadeOut('fast',function(){currentMenuWrapper.hide();menuWrapper.find('> ol').hide().end().show().find('> ol').fadeIn();});}
self._editForm.removeClass('cMenuManager_formActive').find('> div').fadeOut('fast',function(){$(this).remove();});self._currentDropdown=dropdownID;};this._checkTempBeforeCallback(doEdit);},navBack:function(e){e.preventDefault();var self=this;var parentID=$(e.currentTarget).attr('data-parentID');if(parentID==0){this.scope.find('.cMenuManager_column').addClass('cMenuManager_readyToSlide');this.scope.find('[data-manager="dropdown"], [data-manager="main"]').show().animate({left:"0"},function(){self._dropdownManager.find('[data-dropdownID]').hide();self.scope.find('.cMenuManager_column').removeClass('cMenuManager_readyToSlide');});this._currentDropdown=0;}else{var menuWrapper=this._dropdownManager.find('[data-dropdownID="'+parentID+'"]');var currentMenuWrapper=this._dropdownManager.find('[data-dropdownID="'+this._currentDropdown+'"]');currentMenuWrapper.find(' > ol').fadeOut('fast',function(){currentMenuWrapper.hide();menuWrapper.find('> ol').hide().end().show().find('> ol').fadeIn();});}
this._unselectAllItems();},menuToggled:function(){this._positionPreviewWindow();},_loadEditForm:function(url,obj){var self=this;if(this._ajaxObj&&_.isFunction(this._ajaxObj.abort)){this._ajaxObj.abort();}
this._editForm.addClass('ipsLoading').addClass('cMenuManager_formActive').find('> div').css({opacity:0.4}).after($('<div/>').addClass('cMenuManager_formLoading ipsLoading'));this._ajaxObj=ips.getAjax()(url,{data:obj}).done(function(response){self._editForm.html(response);$(document).trigger('contentChange',[self._editForm]);}).always(function(){self._editForm.removeClass('ipsLoading cMenuManager_formLoading');});},_checkForEmptyDropdown:function(menu){if(!menu.length){return;}
if(menu.find('[data-itemID]').length||menu.find('.ipsMenu_sep').length){menu.find('.cMenuManager_emptyList').remove();}else{menu.append(ips.templates.render('menuManager.emptyList'));}},_checkTempBeforeCallback:function(callback){var self=this;if(this.scope.find('[data-itemID="temp"]').length){ips.ui.alert.show({type:'confirm',icon:'warn',message:ips.getString('menuManagerUnsavedTemp'),callbacks:{ok:function(){self.scope.find('[data-itemID="temp"]').remove();callback.apply(self);}}});}else{callback.apply(self);}},_unselectAllItems:function(){this._editForm.find('> div').fadeOut('fast',function(){$(this).remove();});this._menuManager.find('.cMenuManager_active').removeClass('cMenuManager_active');this._dropdownManager.find('.cMenuManager_active').removeClass('cMenuManager_active');this._editForm.removeClass('cMenuManager_formActive');},_changeOccurred:function(){this._hasChanges=true;$('[data-action="publishMenu"]').removeClass('ipsButton_disabled');},_updatePreview:function(e){this.scope.find("[data-role='previewBody'] iframe").get(0).contentWindow.location.reload(true);},_startDragging:function(){},_positionPreviewWindow:function(){if($('body').find('#acpAppList').is(':visible')){var width=$('body').find('#acpAppList').width();this._previewWindow.css({left:width+'px'});}}});}(jQuery,_));;
;(function($,_,undefined){"use strict";ips.controller.register('core.admin.system.themeHook',{initialize:function(){var scope=this.scope;scope.find('[data-action="showTemplate"]').removeClass('ipsHide');this.on('openDialog',function(e,data){$('#'+data.dialogID).on('click','li[data-selector]',function(e){e.stopPropagation();ips.ui.dialog.getObj(scope.find('[data-action="showTemplate"]')).hide();scope.find('input[name="plugin_theme_hook_selector"]').val($(e.currentTarget).attr('data-selector'));})});},});}(jQuery,_));;
;(function($,_,undefined){"use strict";ips.controller.register('core.admin.system.themeHookEditor',{initialize:function(){this.on('click','a[data-action="templateLink"]',this._itemClick);},_itemClick:function(e){e.preventDefault();var themeHookWindow=$(this.scope).find('[data-role="themeHookWindow"]');var sidebar=$(this.scope).find('[data-role="themeHookSidebar"]');var target=$(e.currentTarget);History.replaceState({},document.title,target.attr('href'));themeHookWindow.children('[data-template],[data-role="themeHookWindowPlaceholder"]').hide();themeHookWindow.addClass('ipsLoading');sidebar.find('.cHookEditor_activeTemplate').removeClass('cHookEditor_activeTemplate');if(themeHookWindow.children('[data-template="'+target.text()+'"]').length){themeHookWindow.children('[data-template="'+target.text()+'"]').show();}else{ips.getAjax()(target.attr('href')+'&editor=1').done(function(response){themeHookWindow.append("<div class='cHookEditor_content' data-template='"+target.text()+"'>"+response+'</div>');$(document).trigger('contentChange',[themeHookWindow]);}).fail(function(){window.location=target.attr('href');})}
target.addClass('cHookEditor_activeTemplate');themeHookWindow.removeClass('ipsLoading');}});}(jQuery,_));;
;(function($,_,undefined){"use strict";ips.controller.register('core.admin.system.themeHookTemplate',{initialize:function(){this.on('click','li[data-selector]',this._itemClick);},_itemClick:function(e){this.trigger($(e.currentTarget),'templateClicked');}});}(jQuery,_));;