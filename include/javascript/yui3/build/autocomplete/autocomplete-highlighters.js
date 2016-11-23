/*
 Copyright (c) 2010, Yahoo! Inc. All rights reserved.
 Code licensed under the BSD License:
 http://developer.yahoo.com/yui/license.html
 version: 3.3.0
 build: 3167
 */
YUI.add('autocomplete-highlighters',function(Y){var YArray=Y.Array,Highlight=Y.Highlight,Highlighters=Y.mix(Y.namespace('AutoCompleteHighlighters'),{charMatch:function(query,results,caseSensitive){var queryChars=YArray.unique((caseSensitive?query:query.toLowerCase()).split(''));return YArray.map(results,function(result){return Highlight.all(result.text,queryChars,{caseSensitive:caseSensitive});});},charMatchCase:function(query,results){return Highlighters.charMatch(query,results,true);},phraseMatch:function(query,results,caseSensitive){return YArray.map(results,function(result){return Highlight.all(result.text,[query],{caseSensitive:caseSensitive});});},phraseMatchCase:function(query,results){return Highlighters.phraseMatch(query,results,true);},startsWith:function(query,results,caseSensitive){return YArray.map(results,function(result){return Highlight.all(result.text,[query],{caseSensitive:caseSensitive,startsWith:true});});},startsWithCase:function(query,results){return Highlighters.startsWith(query,results,true);},wordMatch:function(query,results,caseSensitive){return YArray.map(results,function(result){return Highlight.words(result.text,query,{caseSensitive:caseSensitive});});},wordMatchCase:function(query,results){return Highlighters.wordMatch(query,results,true);}});},'3.3.0',{requires:['array-extras','highlight-base']});
