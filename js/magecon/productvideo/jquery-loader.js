var jQueryScriptOutputted=false;function initJQuery(){varjQueryFileName="jquery.min.js";if(typeof(jQuery)=='undefined'){if(!jQueryScriptOutputted){jQueryScriptOutputted=true;var scripts=document.getElementsByTagName('script');var scriptPath=scripts[scripts.length-1].src.split('?')[0].split('/').slice(0,-1).join('/')+'/';document.write("<scr"+"ipt type=\"text/javascript\" src=\""+scriptPath+varjQueryFileName+"\"></scr"+"ipt>")}setTimeout("initJQuery()",50)}}initJQuery();