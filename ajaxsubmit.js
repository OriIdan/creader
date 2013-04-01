//##################################################################################
//## FORM SUBMIT WITH AJAX                                                        ##
//## @Author: Simone Rodriguez aka Pukos <http://www.SimoneRodriguez.com>         ##
//## Modified by Ori Idan                                                         ##
//## @Version: 1.2                                                                ##
//## @Released: 28/08/2007                                                        ##
//## @License: GNU/GPL v. 2 <http://www.gnu.org/copyleft/gpl.html>                ##
//##################################################################################

function xmlhttpPost(strURL, formname, responsediv) {
    var xmlHttpReq = false;
    var self = this;

    // Xhr per Mozilla/Safari/Ie7
    if (window.XMLHttpRequest) {
        self.xmlHttpReq = new XMLHttpRequest();
    }
    // per tutte le altre versioni di IE
    else if (window.ActiveXObject) {
        self.xmlHttpReq = new ActiveXObject("Microsoft.XMLHTTP");
    }

    self.xmlHttpReq.open('POST', strURL, true);
    self.xmlHttpReq.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    self.xmlHttpReq.onreadystatechange = function() {
        if (self.xmlHttpReq.readyState == 4) {
			document.getElementById(responsediv).innerHTML = self.xmlHttpReq.responseText;
         }
    }
    self.xmlHttpReq.send(getquerystring(formname));
    return false;
}

function getquerystring(formname) {
    var form = document.forms[formname];
	var qstr = "";

    function GetElemValue(name, value) {
        qstr += (qstr.length > 0 ? "&" : "")

            + name.replace(/\+/g, "%2B") + "="

            + value.replace(/\+/g, "%2B");

			// + escape(value ? value : "").replace(/\n/g, "%0D%0A");
			qstr.replace(/\n/g, "%0D%0A");
    }

	var elemArray = form.elements;

    for (var i = 0; i < elemArray.length; i++) {
        var element = elemArray[i];
        var elemType = element.type.toUpperCase();
        var elemName = element.name;
        if (elemName) {
            if (elemType == "TEXT"
                    || elemType == "TEXTAREA"
                    || elemType == "PASSWORD"
					|| elemType == "BUTTON"
					|| elemType == "RESET"
					|| elemType == "SUBMIT"
					|| elemType == "FILE"
					|| elemType == "IMAGE"
                    || elemType == "HIDDEN")
                GetElemValue(elemName, element.value);
            else if (elemType == "CHECKBOX" && element.checked)
                GetElemValue(elemName, element.value ? element.value : "On");
            else if (elemType == "RADIO" && element.checked)
                GetElemValue(elemName, element.value);
            else if (elemType.indexOf("SELECT") != -1)
                for (var j = 0; j < element.options.length; j++) {
                    var option = element.options[j];
                    if (option.selected)
                        GetElemValue(elemName, option.value ? option.value : option.text);
                }
        }

    }
    return qstr;
}

