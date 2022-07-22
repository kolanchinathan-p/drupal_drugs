(function ($, Drupal, drupalSettings) {
    Drupal.behaviors.custom = {
        attach: function (context) {

        if ($('#edit-drug-name').length ) {
          // Autocomplete - on select navigation changes
          $(".drug-name").autocomplete({
            select: function (event, ui) {
              if (ui.item) {
                var drugName = ui.item.value;
                if(ui.item.url){
                  drugName = ui.item.url;
                }
                window.location.href = Drupal.url(`drug/${drugName}`);
              }
            },
            autoFocus: true,
            minLength: 3
          }).data("ui-autocomplete")._renderItem = function (ul, item) {
              // Adding Data-url value for navigation
              if (item.url != '') {
                return $( "<li>" )
               .attr( "data-value", item.value )
               .attr('data-url', item.url)
               .append( item.label )
               .appendTo( ul );
              }
          };
       }

      $('#edit-drugformfilter').change( function(){
        if($(this).children('option').length > 1){
          $('input:hidden[name=filterType]').val('form');
          $('#spinner-wrapper').removeClass('d-none');
          var formVal = $("#edit-drugformfilter").children("option:selected").text();
          $("form#drug-search-filter-form").submit();
        }
      });

      $('#edit-drugstrfilter').change( function(){
        if($(this).children('option').length > 1){
          $('#spinner-wrapper').removeClass('d-none');
          var dosageVal = $("#edit-drugstrfilter").children("option:selected").text();
          $('input:hidden[name=filterType]').val('strength');
          $("form#drug-search-filter-form").submit();
        }
      });


      $('#edit-drugquantfilter').change( function(event){
        if($(this).children('option').length > 1 && !$(this).val().includes("custom")){
          $('#spinner-wrapper').removeClass('d-none');
          var quantityVal = $("#edit-drugquantfilter").children("option:selected").text();
          $('input:hidden[name=filterType]').val('quantity');
          $("form#drug-search-filter-form").submit();
        }

      });

      $("#edit-custom-quantity").blur(function (){
        $('#spinner-wrapper').removeClass('d-none');
        if($('#edit-drugquantfilter').val() == 'custom' && $('#edit-custom-quantity').val() != '' && $('#edit-custom-quantity').val() > 0){
          $('input:hidden[name=filterType]').val('quantity');
          $("form#drug-search-filter-form").submit();
        }

      });

      $('#edit-custom-quantity').keypress(function(event){
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode == '13'){
          $('#spinner-wrapper').removeClass('d-none');
          if($('#edit-drugquantfilter').val() == 'custom' && $('#edit-custom-quantity').val() != '' && $('#edit-custom-quantity').val() > 0){
            $('input:hidden[name=filterType]').val('quantity');
            $("form#drug-search-filter-form").submit();
          }
        }
      });

    }
  }
}(jQuery, Drupal, drupalSettings));
