/*
 Copyright (c) 2010, Yahoo! Inc. All rights reserved.
 Code licensed under the BSD License:
 http://developer.yahoo.com/yui/license.html
 version: 3.3.0
 build: 3167
 */
YUI.add('event-valuechange',function(Y){var YArray=Y.Array,VALUE='value',VC={POLL_INTERVAL:50,TIMEOUT:10000,_history:{},_intervals:{},_notifiers:{},_timeouts:{},_poll:function(node,stamp,e){var domNode=node._node,newVal=domNode&&domNode.value,prevVal=VC._history[stamp],facade;if(!domNode){VC._stopPolling(node,stamp);return;}
if(newVal!==prevVal){VC._history[stamp]=newVal;facade={_event:e,newVal:newVal,prevVal:prevVal};YArray.each(VC._notifiers[stamp],function(notifier){notifier.fire(facade);});VC._refreshTimeout(node,stamp);}},_refreshTimeout:function(node,stamp){VC._stopTimeout(node,stamp);VC._timeouts[stamp]=setTimeout(function(){VC._stopPolling(node,stamp);},VC.TIMEOUT);},_startPolling:function(node,stamp,e,force){if(!stamp){stamp=Y.stamp(node);}
if(!force&&VC._intervals[stamp]){return;}
VC._stopPolling(node,stamp);VC._intervals[stamp]=setInterval(function(){VC._poll(node,stamp,e);},VC.POLL_INTERVAL);VC._refreshTimeout(node,stamp,e);},_stopPolling:function(node,stamp){if(!stamp){stamp=Y.stamp(node);}
VC._intervals[stamp]=clearInterval(VC._intervals[stamp]);VC._stopTimeout(node,stamp);},_stopTimeout:function(node,stamp){if(!stamp){stamp=Y.stamp(node);}
VC._timeouts[stamp]=clearTimeout(VC._timeouts[stamp]);},_onBlur:function(e){VC._stopPolling(e.currentTarget);},_onFocus:function(e){var node=e.currentTarget;VC._history[Y.stamp(node)]=node.get(VALUE);VC._startPolling(node,null,e);},_onKeyDown:function(e){VC._startPolling(e.currentTarget,null,e);},_onKeyUp:function(e){if(e.charCode===229||e.charCode===197){VC._startPolling(e.currentTarget,null,e,true);}},_onMouseDown:function(e){VC._startPolling(e.currentTarget,null,e);},_onSubscribe:function(node,subscription,notifier){var stamp=Y.stamp(node),notifiers=VC._notifiers[stamp];VC._history[stamp]=node.get(VALUE);notifier._handles=node.on({blur:VC._onBlur,focus:VC._onFocus,keydown:VC._onKeyDown,keyup:VC._onKeyUp,mousedown:VC._onMouseDown});if(!notifiers){notifiers=VC._notifiers[stamp]=[];}
notifiers.push(notifier);},_onUnsubscribe:function(node,subscription,notifier){var stamp=Y.stamp(node),notifiers=VC._notifiers[stamp],index=YArray.indexOf(notifiers,notifier);notifier._handles.detach();if(index!==-1){notifiers.splice(index,1);if(!notifiers.length){VC._stopPolling(node,stamp);delete VC._notifiers[stamp];delete VC._history[stamp];}}}};Y.Event.define('valueChange',{detach:VC._onUnsubscribe,on:VC._onSubscribe,publishConfig:{emitFacade:true}});Y.ValueChange=VC;},'3.3.0',{requires:['event-focus','event-synthetic']});
