function gislCheckForWebPSupport(feature, callback) {
  var kTestImages = {
      lossy: "UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA",
      lossless: "UklGRhoAAABXRUJQVlA4TA0AAAAvAAAAEAcQERGIiP4HAA==",
      alpha: "UklGRkoAAABXRUJQVlA4WAoAAAAQAAAAAAAAAAAAQUxQSAwAAAARBxAR/Q9ERP8DAABWUDggGAAAABQBAJ0BKgEAAQAAAP4AAA3AAP7mtQAAAA==",
      animation: "UklGRlIAAABXRUJQVlA4WAoAAAASAAAAAAAAAAAAQU5JTQYAAAD/////AABBTk1GJgAAAAAAAAAAAAAAAAAAAGQAAABWUDhMDQAAAC8AAAAQBxAREYiI/gcA"
  };
  var img = new Image();
  img.onload = function () {
    var result = (img.width > 0) && (img.height > 0);
    callback(feature, result);
  };
  img.onerror = function () {
    callback(feature, false);
  };
  img.src = "data:image/webp;base64," + kTestImages[feature];
}

document.addEventListener("DOMContentLoaded", function(event) {

  gislCheckForWebPSupport('lossy', function (feature, isSupported) {
    if (!isSupported) {
      var divs = document.getElementsByClassName('gisl-webp');
      if(divs.length>0) {
        for(i=0; i<divs.length; i++) {
          divs[i].classList.remove("gisl-webp");
          if(typeof(divs[i].dataset.fallback)!=typeof(this_is_not_defined)) {
            divs[i].src = divs[i].dataset.fallback;
          }
        }
      }
    }
  });

});