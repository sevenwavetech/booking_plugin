(function($){
    $(function(){
        var $form = $('#tb-booking-form');
        if(!$form.length) return;
        $form.on('submit', function(e){
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: TB_Form.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(resp){
                    var $msg = $form.find('.tb-message').show();
                    if(resp && resp.success){
                        $msg.removeClass('error').addClass('updated').text(resp.data.message || 'Success');
                        $form[0].reset();
                    } else {
                        $msg.removeClass('updated').addClass('error').text((resp && resp.data && resp.data.message) ? resp.data.message : 'Error');
                    }
                },
                error: function(){
                    var $msg = $form.find('.tb-message').show();
                    $msg.removeClass('updated').addClass('error').text('Error');
                }
            });
        });
    });
})(jQuery);