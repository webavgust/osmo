$(document).ready(function() {
   $("#add-neuroservice").on("click", function(e) {
       $(this).attr("href", $(this).data('href') + '/' + $(".todo-link.active").data('group-id'));
   });
   $("#edit-neuroservice-group").on("click", function(e) {
       $(this).attr("href", $(this).data('href') + '/' + $(".todo-link.active").data('group-id'));
   });
});
