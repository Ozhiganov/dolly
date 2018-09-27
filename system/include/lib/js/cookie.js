/*
 * jQuery Cookie Plugin v1.3.1
 * https://github.com/carhartl/jquery-cookie
 *
 * Copyright 2013 Klaus Hartl
 * Released under the MIT license
 */
(function(a){if(typeof define==="function"&&define.amd){define(["jquery"],a)}else{a(jQuery)}}(function(a){var f=/\+/g;function e(g){return g}function d(g){return decodeURIComponent(g.replace(f," "))}function c(h){if(h.indexOf('"')===0){h=h.slice(1,-1).replace(/\\"/g,'"').replace(/\\\\/g,"\\")}try{return b.json?JSON.parse(h):h}catch(g){}}var b=a.cookie=function(r,g,j){if(g!==undefined){j=a.extend({},b.defaults,j);if(typeof j.expires==="number"){var h=j.expires,p=j.expires=new Date();p.setDate(p.getDate()+h)}g=b.json?JSON.stringify(g):String(g);return(document.cookie=[b.raw?r:encodeURIComponent(r),"=",b.raw?g:encodeURIComponent(g),j.expires?"; expires="+j.expires.toUTCString():"",j.path?"; path="+j.path:"",j.domain?"; domain="+j.domain:"",j.secure?"; secure":""].join(""))}var o=b.raw?e:d;var q=document.cookie.split("; ");var n=r?undefined:{};for(var s=0,u=q.length;s<u;s++){var m=q[s].split("=");var v=o(m.shift());var k=o(m.join("="));if(r&&r===v){n=c(k);break}if(!r){n[v]=c(k)}}return n};b.defaults={};a.removeCookie=function(h,g){if(a.cookie(h)!==undefined){a.cookie(h,"",a.extend({},g,{expires:-1}));return true}return false}}));