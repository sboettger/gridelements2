/* this is executed inside Ext.onReady() */
if(typeof GridElementsDD === "undefined"){
	//console.error("GridElementsDD.initAll not loaded!");
} else {
	// setting piped in here from PHP
	top.skipDraggableDetails = 0;
	top.geSprites = {};
	top.backPath = '';

	top.TYPO3.Components.PageModule = {
		enableDragDrop: function() {
			return true;
		}
	}

	if(Ext.get('ext-cms-layout-db-layout-php')) {

		// add action for show/hide gridColumn contents
		var toggleIcons = Ext.select('.toggle-content').elements;
		Ext.each(toggleIcons, function(el) {
		  Ext.get(el).on('click', function(e) {
			  Ext.get(e.target).findParent('td.t3-gridCell', 99, true).toggleClass('t3-gridCell-invisibleContent');
		  })
		});

		// add "allowed ctypes" classes to pageColumns
		var pageColumnsAllowedCTypes = top.pageColumnsAllowedCTypes.split('|');
		for (var i = 0; i < pageColumnsAllowedCTypes.length; i++) {
			var currentColClass = pageColumnsAllowedCTypes[i].split(':');
			var currentCol = Ext.select('td.t3-page-column-' + currentColClass[0]);
			Ext.each(currentCol, function(column) {
				if(Ext.get(column).hasClass('t3-gridCell')) {
					Ext.get(column).addClass(currentColClass[1]);
				}
			});
		}

		// add topLevel class to pageColumns
		var mainGrid = Ext.select('table.t3-page-columns, table.t3-page-langMode').elements[0];
		if(mainGrid){
			var topLevelTDs = Ext.get(mainGrid).select('> tbody > tr > td');
			Ext.each(topLevelTDs, function(topLevelTD){
				Ext.get(topLevelTD).addClass('t3-gridTL');
				//console.log(topLevelTD);
				if(Ext.get(topLevelTD).el.dom === undefined || !Ext.get(topLevelTD).el.dom.className.match(/t3-allow-/)) {
					Ext.get(topLevelTD).addClass('t3-allow-all');
				}
			});
		}

		// add allowed ctypes to addNewButtons of gridColumns and contentElements
		var rowHeaders = Ext.select('.t3-row-header').elements;
		Ext.each(rowHeaders, function(rowHeader){
			var rowHeaderLinkNew = Ext.get(rowHeader).select('a').first();
			if(rowHeaderLinkNew !== null) {
				var onClick = rowHeaderLinkNew.getAttribute('onclick');
				if (onClick !== null && !onClick.match(/tx_gridelements_allowed=/)) {
					var parentColumn = Ext.get(rowHeader).findParent('td.t3-gridCell', 4);
					if(parentColumn){
						var allowedCTypes = [];
						var currentClasses = Ext.get(parentColumn).dom.className.split(' ');
						for (var i = 0; i < currentClasses.length; i++) {
							var currentClass = currentClasses[i];
							if(currentClass.substr(0, 9) == 't3-allow-'){
								allowedCTypes.push(currentClass.substr(9));
							}
						}
						if(allowedCTypes[0] !== 'all'){
							onClick = onClick.replace('db_new_content_el.php?', 'db_new_content_el.php?tx_gridelements_allowed=' + allowedCTypes.join(',') + '&');
							rowHeaderLinkNew.set({onclick: onClick});
						}
					}
				}
			}
		});

		// add "active" class to t3-page-ce-header/body on hover
		var contentElements = Ext.select('.t3-page-ce').elements;
		Ext.each(contentElements, function(contentElement){
			Ext.get(contentElement).addListener('mouseenter', function(e, t){
				if(this.select('> .t3-page-ce-header').first()) {
					this.select('> .t3-page-ce-header').first().addClass('t3-page-ce-header-active');
				}
				if(this.select('> .t3-page-ce-body').first()) {
					this.select('> .t3-page-ce-body').first().addClass('t3-page-ce-body-active');
				}

				var gridTable = this.select('> .t3-page-ce-body table.t3-gridTable').first();
				if(gridTable){
					gridTable.select('> tbody > tr > td > .t3-page-colHeader > .t3-page-colHeader-icons').addClass('t3-page-colHeader-icons-active');
				}
			});
			Ext.get(contentElement).addListener('mouseleave', function(e, t){
				if(this.select('> .t3-page-ce-header').first()) {
					this.select('> .t3-page-ce-header').first().removeClass('t3-page-ce-header-active');
				}
				if(this.select('> .t3-page-ce-body').first()) {
					this.select('> .t3-page-ce-body').first().removeClass('t3-page-ce-body-active');
				}

				var gridTable = this.select('> .t3-page-ce-body table.t3-gridTable').first();
				if(gridTable){
					gridTable.select('> tbody > tr > td > .t3-page-colHeader > .t3-page-colHeader-icons').removeClass('t3-page-colHeader-icons-active');
				}
			});
		});

		// add top dropzones after t3-page-colHeader elements
		var dropZoneTpl = '<div class="x-dd-droptargetarea">' + TYPO3.l10n.localize('tx_gridelements_js.drophere') + '</div>',
			dropZonePar = Ext.select('.t3-page-colHeader').elements;

		Ext.each(dropZonePar, function(currentColHeader){
			var parentCell = Ext.get(currentColHeader).parent(),
				dropZoneID = null;
			if(Ext.get(parentCell).dom.className.search(/t3-gridCell-unassigned/g) == -1) {
				if(Ext.get(parentCell).id.substr(0, 6) != 'column') {
					var parentCellClass = Ext.get(parentCell).dom.className.split(' ');
					for(i = 0; i < parentCellClass.length; i++) {
						if(parentCellClass[i].substr(0, 15) == 't3-page-column-') {
							dropZoneID = 'DD_DROP_PIDx' + parentCellClass[i].substr(15);
						}
					};
				} else {
					dropZoneID = Ext.get(parentCell).id;
				}
				var currentDropZone = document.createElement('div');
				Ext.get(currentDropZone).addClass([
					'x-dd-makedroptarget',
					'x-dd-droptargetgroup-els'
				]);
				currentDropZone.innerHTML = dropZoneTpl;
				Ext.get(currentDropZone).select('div.x-dd-droptargetarea').set({title: dropZoneID});
				Ext.get(currentDropZone).insertAfter(currentColHeader);
			}
		});

		// add dropzones within .t3-page-ce existing elements
		var dropZoneEl = Ext.select('.t3-page-ce .t3-page-ce-body').elements;
		Ext.each(dropZoneEl, function(currentElement){
			var dropZoneID = Ext.get(currentElement).parent().select('.t3-page-ce-header span a span').elements[0].getAttribute('title');
			var currentDropZone = document.createElement('div');
			currentDropZone.innerHTML = dropZoneTpl;
			Ext.get(currentDropZone).select('div.x-dd-droptargetarea').set({title: dropZoneID});
			Ext.get(currentDropZone).insertAfter(currentElement);
		});

		// make existing content elements draggable
		var contentEls = Ext.select('.t3-page-ce').elements;
		Ext.each(contentEls, function(currentEl){
			Ext.get(currentEl).addClass([
				'x-dd-makemedrag',
				'x-dd-makedroptarget',
				'x-dd-droptargetgroup-els'
			]);
		});

		firstNewIconContainer = Ext.get('typo3-docheader-row1');

		// get link around the "new content element" icon and if there is one do the magic
		if(firstNewIconContainer && firstNewIconContainer.select('.t3-icon-document-new').elements[0] !== undefined) {

				var
					// create container for content draggables
					draggableContainer = new Ext.Element(document.createElement ('div'), true),
					firstNewIconLink = Ext.get(firstNewIconContainer.select('.t3-icon-document-new').elements[0].parentNode),
					draggableContainerFilled = false;

				// add id and "loading..." text to draggables container
				Ext.get(draggableContainer).dom.id = 'x-dd-draggablecontainer';

				// add draggables container to DOM, right after firstNewIconLink
				draggableContainer.insertAfter(firstNewIconLink);

			// define callback function executed when tempDiv (below) finishes loading
			var fillDraggableContainer = function(tempDiv, success){

				if (!success) {
					return;
				}

				draggableContainerFilled = true;

				Ext.get(draggableContainer).dom.innerHTML = '';
				
				var dyntabMenuTabs = Ext.get(tempDiv).select('#user-setup-wrapper div.typo3-dyntabmenu-tabs');
				draggableContainer.appendChild(dyntabMenuTabs);
				var dyntabMenuDivs = Ext.get(tempDiv).select('#user-setup-wrapper div.typo3-dyntabmenu-divs');
				draggableContainer.appendChild(dyntabMenuDivs);

				Ext.each(Ext.get(draggableContainer).select('div.typo3-dyntabmenu-divs div').elements, function(layer){
				    var layerContent = '';
				    Ext.each(Ext.get(layer).select('tr').elements, function(row) {
						var headerText = Ext.get(row).select('td:last a strong').elements[0].innerHTML;
						Ext.get(row).select('td:last a strong').remove();
						var descText = Ext.get(row).select('td:last a').elements[0].innerHTML;
						
						Ext.get(row).select('td:last a strong').remove();
						Ext.get(row).select('td:first, td:last').remove();
						
						// set additional info either to rel or to title
						if(!top.skipDraggableDetails) {
							descText = descText.replace('<br>', '').replace('<BR>', '');
							Ext.get(row).select('td a').set({title: '', rel: headerText + '|' + descText}).addClass('x-dd-draggableitem x-dd-droptargetgroup-els x-dd-usetpl-useradd');
						}else{
							descText = descText.replace('<br>', ' - ').replace('<BR>', ' - ');
							Ext.get(row).select('td a').set({title: headerText + descText}).addClass('x-dd-draggableitem x-dd-droptargetgroup-els x-dd-usetpl-useradd');
						}
						
					});
					Ext.each(Ext.get(layer).select('td').elements, function(cell) {
						layerContent += '<div class="x-dd-new-element-link">' + cell.innerHTML + '</div>';
					});
					// add content and a container for additional info
					layer.innerHTML = layerContent;
				});
				
				Ext.get(draggableContainer).select('div.typo3-dyntabmenu-divs div').show();

				var dyntabMenuID = Ext.get(draggableContainer).select('.tab').elements[0].id.replace('-1-MENU', '');
				DTM_array[dyntabMenuID] = new Array();
				for(counter = 0; counter < Ext.get(draggableContainer).select('.tab').elements.length; counter++) {
					DTM_array[dyntabMenuID][counter] = dyntabMenuID + '-' + (counter + 1);
				}
				Ext.each(Ext.get(draggableContainer).select('div.typo3-dyntabmenu-divs a').elements, function(draggerNow){
					GridElementsDD.makeDragger(draggerNow);
				});
				
				// show additional info for each icon on mouseover below all tab containers if not deactivated
				if(!top.skipDraggableDetails) {
					var detailInfoTpl = new Ext.XTemplate(
							'<div>',
								'<img class="x-dd-draggableiteminfoimg" src="{bigIconSrc}">',
								'<div class="x-dd-draggableiteminfotext">',
									'<strong>{addInfoHeader}</strong><br>',
									'{addInfoText}',
								'</div>',
							'</div>',
							'<br>'
						),
						detailInfoData = {};
					
					// for each tab group
					Ext.each(Ext.get(draggableContainer).select('.typo3-dyntabmenu-divs > div').elements, function(divNow) {
						// add additional info container
						Ext.get(divNow).createChild({
							tag: 'div',
							// "class" without quotes throws an error in IE8, so it's within quotes here
							"class": 'x-dd-draggableiteminfo'
						});
						
						// for each icon: show container on mouseover on an icon
						Ext.each(Ext.get(divNow).select('div.x-dd-droptargetgroup').elements, function(iconNow) {
							Ext.get(iconNow).on('mouseover', function(evtObj, thisNode) {
								// get the a-tag
								var aTag = Ext.get(thisNode).prev();
								// return early if aTag not found (happens while dragging)
								if(!aTag || top.isDragging) {
									return;
								}
								var
									// get the image tag before this dragger
									imgTag = aTag.select('img').elements[0],
									// get data array from data attribute
									aData = aTag.dom.rel.split('|'),
									// template data object
									detailInfoData = {
										// description is in aData
										addInfoHeader: aData[0],
										// text is in aData too
										addInfoText: aData[1]
									},
									// bigger icon is "hidden" in the aTag onclick JS code, here we extract it
									aTagOnClickPartOne = aTag.dom.onclick.toString().split('largeIconImage%3D')[1];
									bigIcon = typeof aTagOnClickPartOne !== 'undefined' ? aTagOnClickPartOne.split('%26')[0].split('%2F') : false;
									
								detailInfoData.bigIconSrc = bigIcon ? imgTag.src.replace(imgTag.src.substr(imgTag.src.lastIndexOf('/') + 1), bigIcon[bigIcon.length - 1]) : imgTag.src;
								
								Ext.get(divNow).select('.x-dd-draggableiteminfo').update(detailInfoTpl.apply(detailInfoData));
								Ext.get(divNow).select('.x-dd-draggableiteminfo').show();
							// hide container on mouseout of an icon
							}).on('mouseout', function(evtObj, thisNode) {
								Ext.get(divNow).select('.x-dd-draggableiteminfo').hide();
								Ext.get(divNow).select('.x-dd-draggableiteminfo').update('');
							});
						});
					});
				}
				
				DTM_activate(dyntabMenuID, top.DTM_currentTabs[dyntabMenuID] ? top.DTM_currentTabs[dyntabMenuID] : 1, 0);
			};
			
			if(top.draggableContainerActive) {
				
				// load HTML output from /typo3/sysext/cms/layout/db_new_content_el.php to temp element
				// e.g. http://core-540-rgeorgi.typo3-entw.telekom.de/typo3/sysext/cms/layout/db_new_content_el.php?id=4722&colPos=1&sys_language_uid=0&uid_pid=4722
				if(draggableContainerFilled == false) {
					Ext.get(document.createElement('div')).load({
						url: top.TYPO3.configuration.PATH_typo3 + 'sysext/cms/layout/db_new_content_el.php' + top.TYPO3.Backend.ContentContainer.iframe.window.location.search,
						method: 'GET',
						scripts: false,
						params: {},
						callback: fillDraggableContainer
					});
				}
				
				// show content draggables dialog instead
				draggableContainer.toggle();
				Ext.get(draggableContainer).dom.style.display = Ext.get(draggableContainer).dom.style.display == 'block' ? 'none' : 'block';

			}

			// over write click event on first "new content element" icon on page - show content draggables instead
			firstNewIconLink.on('click', function(e) {
				
				// disable click (jumping to href)
				e.preventDefault();

				top.draggableContainerActive = top.draggableContainerActive == true ? false : true;
				
				// load HTML output from /typo3/sysext/cms/layout/db_new_content_el.php to temp element
				// e.g. http://core-540-rgeorgi.typo3-entw.telekom.de/typo3/sysext/cms/layout/db_new_content_el.php?id=4722&colPos=1&sys_language_uid=0&uid_pid=4722
				if(draggableContainerFilled == false) {
				    Ext.get(document.createElement('div')).load({
					url: top.TYPO3.configuration.PATH_typo3 + 'sysext/cms/layout/db_new_content_el.php' + top.TYPO3.Backend.ContentContainer.iframe.window.location.search,
					method: 'GET',
					scripts: false,
					params: {
					},
					callback: fillDraggableContainer
				    });
				}

				// show content draggables dialog instead
				draggableContainer.toggle();
				Ext.get(draggableContainer).dom.style.display = Ext.get(draggableContainer).dom.style.display == 'block' ? 'none' : 'block';
			});

		}
		
		// set current server time and base conf values within JS object
		GridElementsDD.baseConf.pageRenderTime = 'insert_server_time_here';
		GridElementsDD.baseConf.extBaseUrl = 'insert_ext_baseurl_here';
		GridElementsDD.baseConf.doReloadsAfterDrops = true;
		GridElementsDD.baseConf.useIconsForNew = true;
		
		// init DD library
		GridElementsDD.initAll();
		
		if(top.DDclipboardfilled && top.DDclipboardElId) {
			GridElementsDD.addPasteAndRefIcons(top.DDclipboardElId);
		}

		// bend Clickmenu.callURL() to our liking (only once)
		if(typeof Clickmenu !== 'undefined' && !GridElementsDD.originalClickmenucallURL){
			GridElementsDD.originalClickmenucallURL = Clickmenu.callURL;
			
			// patched version of Clickmenu.callURL
			Clickmenu.callURL = function(params) {
				if(this.ajax && Ajax.getTransport()) { // run with AJAX
					params += '&ajax=1';
					var call = new Ajax.Request(this.clickURL, {
						method: 'get',
						parameters: params,
						onComplete: function(xhr) {
							var response = xhr.responseXML;
							
							// patching starts here
							var clipboardItemUID = params.match(/&uid=(\d+)&/)[1];
							if(params.search(/&CB.+/) != -1) {
								GridElementsDD.handleClipboardItem(clipboardItemUID, params);
							}
							// patch ends
							
							if(!response.getElementsByTagName('data')[0]) {
								return;
							}
							var menu  = response.getElementsByTagName('data')[0].getElementsByTagName('clickmenu')[0];
							var data  = menu.getElementsByTagName('htmltable')[0].firstChild.data;
							var level = menu.getElementsByTagName('cmlevel')[0].firstChild.data;
							this.populateData(data, level);
						}.bind(this)
					});
				}
			};
			// end patched version of Clickmenu.callURL
			
		}
	}
}
