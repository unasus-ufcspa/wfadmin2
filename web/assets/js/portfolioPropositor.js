var dragSrcEl = null;

function handleDragEnter(e) {
  this.classList.add('over');
}

function handleDragLeave(e) {
  this.classList.remove('over');
}

function handleDragEnd(e) {
  this.classList.remove('over');
}

function allowDrop(ev) {
    ev.preventDefault();
}

function drag(ev) {
    ev.dataTransfer.effectAllowed = 'move';
    ev.dataTransfer.setData("text", ev.target.id);
    // dragSrcEl = this;
}

function drop(ev) {
    if (ev.stopPropagation) {
      ev.stopPropagation(); // Stops some browsers from redirecting.
    }
    // if (dragSrcEl != this) {
      var data = ev.dataTransfer.getData("text");
      ev.target.appendChild(document.getElementById(data));
    // }
    return false;
}

  var area = document.getElementById("boxArrastar")
  area.addEventListener('dragenter', handleDragEnter, false);
  area.addEventListener('dragleave', handleDragLeave, false);
  area.addEventListener('dragend', handleDragEnd, false);
