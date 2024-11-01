// Widget configuration

var apiKey = retrieveApiKey();

if (typeof packetaUsePreProdWidgetVersion === 'undefined'){
    var packetaUsePreProdWidgetVersion = false;
}

if (typeof packetaSelectorBranchCountry === 'undefined') {
    var packetaSelectorBranchCountry = '#packeta-selector-branch-country, .packeta-selector-branch-country';
}

if (typeof packetaSelectorBranchName === 'undefined') {
    var packetaSelectorBranchName = '#packeta-selector-branch-name, .packeta-selector-branch-name';
}

if (typeof packetaSelectorBranchId === 'undefined') {
    var packetaSelectorBranchId = '#packeta-selector-branch-id, .packeta-selector-branch-id';
}

if (typeof packetaSelectorOpen === 'undefined') {
    var packetaSelectorOpen = '#packeta-selector-open, .packeta-selector-open';
}

if (typeof packetaPrimaryButtonColor === 'undefined') {
    var packetaPrimaryButtonColor = '#39b54a';
}

if (typeof packetaBackgroundColor === 'undefined') {
    var packetaBackgroundColor = '#ffffff';
}

if (typeof packetaFontColor === 'undefined') {
    var packetaFontColor = '#555555';
}

if (typeof packetaFontFamily === 'undefined') {
    var packetaFontFamily = 'Arial';
}

if (typeof packetaExternalCssUrl === 'undefined') {
    var packetaExternalCssUrl = '';
}

if (typeof packetaWidgetLanguage === 'undefined') {
    var packetaWidgetLanguage = '';
}

if (typeof packetaCountry === 'undefined') {
    var packetaCountry = '';
}

var defaultValues = {
    'webUrl': window.location.href,
    'apiKey': apiKey,
    'primaryButtonColor': packetaPrimaryButtonColor,
    'backgroundColor': packetaBackgroundColor,
    'fontColor': packetaFontColor,
    'fontFamily': packetaFontFamily,
    'cssUrl': packetaExternalCssUrl,
    'country': packetaCountry,
    'language': packetaWidgetLanguage,
    'widgetLanguage': packetaWidgetLanguage
};

var packetWidgetBaseUrl = 'https://widget.packeta.com';
var idWidget = 'packeta-external-widget';
var idIframeWrap = 'packeta-iframe-wrap';
var idOverlay = 'packeta-widget-overlay';

// Functions

function hidePacketaWidget() {
    var widget = document.getElementById(idOverlay);
    if (widget) {
        widget.remove();
    }
}

function prepareParameters(values) {
    return Object.keys(values).map(function (key) {
        return key + '=' + encodeURIComponent(values[key]);
    }).join('&');
}

var createIframe = function () {
    hidePacketaWidget();

    var iframeSrc = "";

    if (packetaUsePreProdWidgetVersion){
        iframeSrc = packetWidgetBaseUrl + '/v6/#/?' + prepareParameters(defaultValues);

    } else {
        iframeSrc = packetWidgetBaseUrl + '/#/?' + prepareParameters(defaultValues);
    }

    var iframeHtml = '<div id="' + idOverlay + '">' +
        '<div id="' + idIframeWrap + '">' +
        '<iframe id="' + idWidget + '" src="' + iframeSrc + '" allow="geolocation">' +
        '</div>' +
        '</div>';

    var bodyNode = document.querySelector('body');
    var iframeNode = document.createElement('div');
    iframeNode.innerHTML = iframeHtml;

    bodyNode.insertBefore(iframeNode, bodyNode.firstChild);

    addStylesToOverlay();
    addStylesToIframeWrap();
    addStylesToIframe();

    handleHidingWidget();
};

function fillNodesWithData(selectors, value) {
    var nodes = document.querySelectorAll(selectors);

    for (var i = 0; i < nodes.length; i++) {
        if (nodes[i].tagName != 'INPUT') {
            nodes[i].innerHTML = value;
        } else {
            nodes[i].value = value;
        }
    }
}

function bindOpenWidgetElements() {
    var openWidgetNodes = document.querySelectorAll(packetaSelectorOpen);
    for (var i = 0; i < openWidgetNodes.length; i++) {
        if (!openWidgetNodes[i].__packeta) { 
          openWidgetNodes[i].onclick = createIframe;
          openWidgetNodes[i].__packeta = true;
        }
    }
}

var messageHandler = function (e) {
    if (e.origin !== packetWidgetBaseUrl) {
        return;
    }

    var data = e.data;

    if (data.closePacketaWidget) {
        hidePacketaWidget();
        return;
    }

    if (!data.packetaBranchId) {
        return;
    }

    hidePacketaWidget();

    fillNodesWithData(packetaSelectorBranchId, data.packetaBranchId);
    fillNodesWithData(packetaSelectorBranchName, data.packetaBranchName);
    fillNodesWithData(packetaSelectorBranchCountry, data.packetaSelectedData.country);
};

function addStylesToIframeWrap() {
    var iframeWrap = document.getElementById(idIframeWrap);

    var styles = {
        'border': 'none',
        'width': '100%',
        'height': '100%',
        'max-width': '1000px',
        'position': 'fixed',
        'z-index': '999999',
        'left': '50%',
        'top': '50%',
        'transform': 'translate(-50%, -50%)',
        'background': 'transparent'
    };

    applyStyles(iframeWrap, styles);
}

function addStylesToIframe() {
    var iframe = document.getElementById(idWidget);

    var styles = {
        'border': 'none',
        'width': '100%',
        'height': '100%'
    };

    applyStyles(iframe, styles);
}

function addStylesToOverlay() {
    var overlay = document.getElementById(idOverlay);

    var styles = {
        'width': '100%',
        'height': '100%',
        'background': 'rgba(0, 0, 0, 0.8)',
        'position': 'fixed',
        'z-index': '99999'
    };

    applyStyles(overlay, styles);
}

function applyStyles(node, styles) {
    var keys = Object.keys(styles);

    for (var i = 0; i < keys.length; i++) {
        var key = keys[i];

        node.style[key] = styles[key];
    }
}

function handleHidingWidget() {
    document.onkeydown = function (evt) {
        evt = evt || window.event;
        if (evt.keyCode === 27) {
            hidePacketaWidget()
        }
    };

    document.getElementById(idOverlay).addEventListener('click', hidePacketaWidget);
}

function retrieveApiKey(forceOtherWay) {
    var ua = window.navigator.userAgent;
    var msie = ua.indexOf('MSIE ');
    if (forceOtherWay === true || msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./)) { // IE workaround
        var scriptNodes = document.querySelectorAll('[data-api-key]');
        for (var i = 0; i < scriptNodes.length; i++) {
            var attributes = scriptNodes[i].attributes;
            var src = attributes['src'];
            var dataApiKey = attributes['data-api-key'];
            if (!src) {
                continue;
            }

            if (src.value.indexOf('packetaWidget.js') !== -1 && dataApiKey) {
                return dataApiKey.value;
            }
        }
    } else { // Other normal browsers
        return document.currentScript.getAttribute('data-api-key') || (window.shoptet ? "shoptet" : "") || retrieveApiKey(true);
    }

    return (window.shoptet ? "shoptet" : "");
}

function reimplementRemoveFunctionForInternetExplorer () {
    if (!('remove' in Element.prototype)) {
        Element.prototype.remove = function() {
            if (this.parentNode) {
                this.parentNode.removeChild(this);
            }
        };
    }
}

reimplementRemoveFunctionForInternetExplorer();

setInterval(function() {
  bindOpenWidgetElements();
}, 50);

window.addEventListener('message', messageHandler, false);