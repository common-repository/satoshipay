<script>
var cookieBrowserName = document.cookie.replace(/(?:(?:^|.*;)\s*{{cookie_name}}\s*\=\s*([^;]*).*$)|^.*$/, "$1");

function checkBrowserName() {
  if (!window.google_onload_fired && navigator.userAgent && !navigator.userAgent.includes('Chrome')) {
    return false;
  }

  var testIframe = document.createElement('iframe');
  testIframe.style.display = 'none';
  document.body.appendChild(testIframe);

  var browserName = (testIframe.contentWindow.google_onload_fired === true) ? 'brave' : '';

  testIframe.parentNode.removeChild(testIframe);

  if (browserName != '') {
    document.cookie = '{{cookie_name}}=' + browserName;
    if (cookieBrowserName != browserName) {
      window.location.reload(true);
    }
  }
}

if (window.addEventListener !== undefined) {
  window.addEventListener('load', function() { setTimeout(checkBrowserName, 1); }, false);
} else {
  window.attachEvent('onload', function() { setTimeout(checkBrowserName, 1); });
}
</script>
