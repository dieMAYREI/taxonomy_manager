var select = document.getElementById('edit-name-select');
select.onchange = function(){
  var option = select.options[select.selectedIndex];
  document.getElementById('edit-addfield').value = option.value;
};

