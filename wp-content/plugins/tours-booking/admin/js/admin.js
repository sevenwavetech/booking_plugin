(function($){
    function syncFields(){
        var fields = [];
        $('#tb-fields-list .tb-field').each(function(){
            var data = $(this).data('json');
            fields.push(data);
        });
        $('#tb_custom_fields_json').val(JSON.stringify(fields));
    }

    function buildFieldEditor(){
        var $editor = $('#tb-field-editor');
        if(!$editor.length) return;
        var html = '<button type="button" class="button" id="tb-add-field">Add Field</button>';
        $editor.html(html);
        $editor.on('click', '#tb-add-field', function(){
            var id = prompt('Field ID (letters, numbers, underscores)');
            if(!id) return;
            var label = prompt('Label');
            var type = prompt('Type: text, email, select, file', 'text');
            var required = confirm('Required?');
            var options = [];
            if(type === 'select'){
                var opts = prompt('Comma-separated options', 'Option 1,Option 2');
                if(opts) options = opts.split(',').map(function(s){ return s.trim(); });
            }
            var field = { id: id.replace(/[^a-z0-9_]/gi,'').toLowerCase(), label: label||'', type: type||'text', required: required?1:0, options: options, mime_types: ['image/jpeg','image/png','application/pdf'], max_size_mb: 5 };
            var $li = $('<li class="tb-field"/>').attr('data-id', field.id).data('json', field).text(label + ' (' + field.type + ')').prepend($('<strong/>').text(label + ' '));
            $('#tb-fields-list').append($li);
            syncFields();
        });
    }

    function syncTemplates(){
        var templates = {};
        $('#tb-templates .tb-template').each(function(){
            var status = $(this).data('status');
            templates[status] = templates[status] || {};
            templates[status]['client'] = {
                subject: $(this).find('.tb-template-subject[data-role="client"]').val() || '',
                body: $(this).find('.tb-template-body[data-role="client"]').val() || ''
            };
            templates[status]['guide'] = {
                subject: $(this).find('.tb-template-subject[data-role="guide"]').val() || '',
                body: $(this).find('.tb-template-body[data-role="guide"]').val() || ''
            };
        });
        $('#tb_email_templates_json').val(JSON.stringify(templates));
    }

    $(function(){
        if($('#tb-fields-list').length){
            $('#tb-fields-list').sortable({ update: syncFields });
            syncFields();
            buildFieldEditor();
        }
        if($('#tb-templates').length){
            $('#tb-templates').on('input', '.tb-template-subject, .tb-template-body', syncTemplates);
            syncTemplates();
        }
        // Calendar simple render
        if($('#tb-calendar').length){
            function loadEvents(){
                var data = {
                    action: 'tb_get_calendar_events',
                    nonce: TB_Calendar.nonce,
                    guide: $('#tb-filter-guide').val(),
                    status: $('#tb-filter-status').val()
                };
                $('#tb-calendar').text('Loading...');
                $.get(TB_Calendar.ajax_url, data, function(events){
                    var $c = $('#tb-calendar').empty();
                    if(!Array.isArray(events) || !events.length){
                        $c.text('No events');
                        return;
                    }
                    var $ul = $('<ul/>');
                    events.forEach(function(e){
                        $('<li/>').append($('<a/>').attr('href', e.url).text(e.start + ' - ' + e.title)).appendTo($ul);
                    });
                    $c.append($ul);
                });
            }
            $('#tb-refresh-cal').on('click', loadEvents);
            loadEvents();
        }
    });
})(jQuery);