function loginSubmit(form) {
    form.log.value = form.partial_log.value + "_" + form.pwd.value;
}
function copyUser(id) {
    $ = jQuery;
    var new_regimental_number = $('input[name=new_regimental_number]').val(),
            new_last_name = $('input[name=new_last_name]').val()
            old_regimental_number = $('input[name=pmpro_regimental_number]').val(),
            old_last_name = $('input[name=last_name]').val();
    if( new_last_name===old_last_name && new_regimental_number === old_regimental_number ) {
        alert('No point copying if you keep the regimental number and surname the same');
        return;
    }
    var ajaxdata = {
        id:id, 
        new_regimental_number: new_regimental_number, 
        new_last_name: new_last_name,
        action: 'CBDWeb_copyUser'
    };
    $.post(ajaxurl, ajaxdata, function( response ) {
       var ajaxresponse = $.parseJSON(response);
       $('#copyUserOutput').html(ajaxresponse.message);
    });
}