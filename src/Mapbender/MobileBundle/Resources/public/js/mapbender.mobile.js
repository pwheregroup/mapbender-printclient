$(function(){
    $(document).on('mbfeatureinfofeatureinfo', function(e, options){
        $.each($('#mobilePane .mobileContent').children(), function(idx, item){
            $(item).addClass('hidden');
        });
        $('#' + options.id).removeClass('hidden');
        $('#mobilePane .contentTitle').text($('#' + options.id).attr('data-title'));
        $('#mobilePane').attr('data-state', 'opened');
    });
    $('#footer').on('click', '.mb-element,.mb-button', function(e){
        var button = $('#' + $(e.target).attr('for')).data('mapbenderMbButton');
        if($('#' + button.options.target).parents('.mobilePane').length > 0){ // only for elements at '.mobilePane'
            $.each($('#mobilePane .mobileContent').children(), function(idx, item){
                $(item).addClass('hidden');
            });
            $('#' + button.options.target).removeClass('hidden');
            var targets = $('#' + button.options.target).data();
            $('#mobilePane .contentTitle').text('undefined');
            for(widgetName in targets){
                if(typeof targets[widgetName] === 'object' && targets[widgetName].options){
                    var title = null;
                    if(title = targets[widgetName].element.attr('title'));
                    else if(title = targets[widgetName].element.attr('data-title'));
                    $('#mobilePane .contentTitle').text(title);
                    break;
                }
            }
            $('#mobilePane').attr('data-state', 'opened');
            e.stopPropagation();
        }
    });
    $('#mobilePaneClose').on('click', function(){
        $('#mobilePane').removeAttr('data-state');
    });
    $('.mb-element-basesourceswitcher li').on('click', function(e){
        $('#mobilePaneClose').click();
    });
    $('.mb-element-basesourceswitcher li').on('click', function(e){
        $('#mobilePaneClose').click();
    });
    $('.mb-element-simplesearch input[type="text"]').on('mbautocomplete.selected', function(e){
        $('#mobilePaneClose').click();
    });

});