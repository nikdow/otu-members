jQuery(document).ready(function($){
  $('#country').bind('change', function () {
      var cval=$('#country').get('value');
      if($('#country').val()==="AU") {
        $('.state').each(function(index, el) {
            $(el).removeClass('removed');
        });
     } else {
       $('.state').each(function(index, el) {
           $(el).addClass('removed');
       });
      }
    });
  $('#name').focus();

  $('#confirmButton').bind('click', function (event) {
      var data = $(document.forms['confirm']).serialize();
      $('#ajax-loading').removeClass('farleft');
      $('#returnMessage').html('&nbsp;');
      $('#saveButton').prop('disabled', true);
      $.post( nectarLove.ajaxurl, data, function( response ){
           $('#saveButton').prop('disabled', false);
           var ajaxdata = $.parseJSON(response);
           if( ajaxdata.error ) {
               $('#returnMessage').html( ajaxdata.error );
           } else if( ajaxdata.success ) {
               $('#returnMessage').html( ajaxdata.success );
           } else {
               $('#returnMessage').html ( ajaxdata );
           }
           $('#ajax-loading').addClass('farleft');
        });
      ga('send', 'event', 'Registration', data.action );
    } );
    
});




