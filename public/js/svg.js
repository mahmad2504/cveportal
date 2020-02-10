var ajax = new XMLHttpRequest();
ajax.open("GET", "https://static.mentor-cdn.com/common/icons/icons.svg?20181206", true);
ajax.send();
ajax.onload = function(e) {
  var div = document.createElement("div");
  div.innerHTML = ajax.responseText;
  document.body.insertBefore(div, document.body.childNodes[0]);
}