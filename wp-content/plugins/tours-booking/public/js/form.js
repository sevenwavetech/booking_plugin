(function($){
    $(function(){
        var $form = $('#tb-booking-form');
        if(!$form.length) return;

        function fetchSlots(){
            var tourId = $form.find('[name="tb_tour_id"]').val();
            var date = $form.find('[name="tb_booking_date"]').val();
            if(!tourId || !date){ return; }
            $form.find('.tb-slots').text('Loading...');
            $.get(TB_Form.ajax_url, { action: 'tb_get_slots', tour_id: tourId, date: date }, function(resp){
                var $slots = $form.find('.tb-slots').empty();
                if(!resp || !resp.success || !resp.data.slots || !resp.data.slots.length){
                    $slots.text('No slots');
                    return;
                }
                var $select = $('<select name="tb_booking_start" required/>');
                resp.data.slots.forEach(function(s){
                    var txt = s.start + ' - ' + s.end;
                    $('<option/>').val(s.start).text(txt).appendTo($select);
                });
                $slots.append($select);
            });
        }

        $form.on('change', '[name="tb_tour_id"], [name="tb_booking_date"]', fetchSlots);

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
                        $form.find('.tb-slots').empty();
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