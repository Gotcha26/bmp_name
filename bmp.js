$(function() {
  $("#input_BMPN4").on("click", function() {
    const area = $('#BMPN3');
    if (!this.checked) {
      area.show();
    } else {
      area.hide();
    }
  });
});