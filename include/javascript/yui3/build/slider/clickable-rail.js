/*
 Copyright (c) 2010, Yahoo! Inc. All rights reserved.
 Code licensed under the BSD License:
 http://developer.yahoo.com/yui/license.html
 version: 3.3.0
 build: 3167
 */
YUI.add('clickable-rail',function(Y){function ClickableRail(){this._initClickableRail();}
Y.ClickableRail=Y.mix(ClickableRail,{prototype:{_initClickableRail:function(){this._evtGuid=this._evtGuid||(Y.guid()+'|');this.publish('railMouseDown',{defaultFn:this._defRailMouseDownFn});this.after('render',this._bindClickableRail);this.on('destroy',this._unbindClickableRail);},_bindClickableRail:function(){this._dd.addHandle(this.rail);this.rail.on(this._evtGuid+Y.DD.Drag.START_EVENT,Y.bind(this._onRailMouseDown,this));},_unbindClickableRail:function(){if(this.get('rendered')){var contentBox=this.get('contentBox'),rail=contentBox.one('.'+this.getClassName('rail'));rail.detach(this.evtGuid+'*');}},_onRailMouseDown:function(e){if(this.get('clickableRail')&&!this.get('disabled')){this.fire('railMouseDown',{ev:e});}},_defRailMouseDownFn:function(e){e=e.ev;var dd=this._resolveThumb(e),i=this._key.xyIndex,length=parseFloat(this.get('length'),10),thumb,thumbSize,xy;if(dd){thumb=dd.get('dragNode');thumbSize=parseFloat(thumb.getStyle(this._key.dim),10);xy=this._getThumbDestination(e,thumb);xy=xy[i]-this.rail.getXY()[i];xy=Math.min(Math.max(xy,0),(length-thumbSize));this._uiMoveThumb(xy);e.target=this.thumb.one('img')||this.thumb;dd._handleMouseDownEvent(e);}},_resolveThumb:function(e){return this._dd;},_getThumbDestination:function(e,node){var offsetWidth=node.get('offsetWidth'),offsetHeight=node.get('offsetHeight');return[(e.pageX-Math.round((offsetWidth/ 2))),(e.pageY-Math.round((offsetHeight / 2)))];}},ATTRS:{clickableRail:{value:true,validator:Y.Lang.isBoolean}}},true);},'3.3.0',{requires:['slider-base']});
