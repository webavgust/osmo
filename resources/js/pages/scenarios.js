$(document).ready(function() {
   $("#add-scenario").on("click", function(e) {
       $(this).attr("href", $(this).data('href') + '/' + $(".todo-link.active").data('group-id'));
   });
   $("#edit-scenario-group").on("click", function(e) {
       $(this).attr("href", $(this).data('href') + '/' + $(".todo-link.active").data('group-id'));
   });
});
