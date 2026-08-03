$(document).ready(function() {
   $("#add-access").on("click", function(e) {
       $(this).attr("href", $(this).data('href') + '/' + $(".todo-link.active").data('group-id'));
   });
   $("#edit-access-group").on("click", function(e) {
       $(this).attr("href", $(this).data('href') + '/' + $(".todo-link.active").data('group-id'));
   });
});
