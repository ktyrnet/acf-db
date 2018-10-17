(function () {
    var $;
    var data = {
        keyupId : -1,
        template : {
            removeButton : '<a href="#" class="acf-icon -minus small dark" data-name="remove_item"></a>'
        }
    };
    var initAcfDb = function(){
        $(document).on('click','.acf-db .choices .acf-rel-item',function (e) {
            var $item = $(e.currentTarget);
            if($item.hasClass('disabled')){
                return;
            }
            var $acfdb = $item.closest('.acf-db');
            var $li = $item.closest('li').clone();
            var $ul = $acfdb.find('.values .list');
            $ul.append($li);
            $item.addClass('disabled');
            updateAcfDbIds();
            $ul.sortable({
                update : function (e,ui) {
                    updateAcfDbIds();
                }
            });
            $ul.disableSelection();
        });
        $(document).on('click','.acf-db .remove-item',function (e) {
            e.preventDefault();
            var $item = $(e.currentTarget);
            var $li = $item.closest('li');
            var id = $li.find('[data-id]').data('id');
            var $parent = $item.closest('.acf-db');
            $parent.find('.choices [data-id="'+id+'"]').removeClass('disabled');
            $li.remove();
            updateAcfDbIds();
        });
        $('.acf-db .choices .list').on('scroll',function (e) {
            if(e.target.scrollHeight <= e.target.scrollTop + e.target.clientHeight){
                var $this = $(e.currentTarget);
                var $acfdb = $this.closest('.acf-db');
                if(updateItemsByWords($acfdb,true)) {
                    setItemLoading($acfdb);
                }
            }
        });
        $('.acf-db [name="s"]').on('keyup',function (e) {
            var $this = $(e.currentTarget);
            var $acfdb = $this.closest('.acf-db');
            clearTimeout(data.keyupId);
            data.keyupId = setTimeout(function () {
                if(updateItemsByWords($acfdb)) {
                    setItemLoading($acfdb, true);
                }
            },500);
        });
    };
    var updateAcfDbIds = function () {
        $('.acf-db').each(function (i,elm) {
            var $acfdb = $(elm);
            var $ul = $acfdb.find('.values .list');
            var tmp = [];
            $ul.find('.acf-rel-item').each(function (i,elm) {
                tmp.push($(elm).data('id'));
            });
            $acfdb.find('[type="hidden"]').val(tmp.join(','));
        });
    };
    var setItemLoading = function ($acfdb,removeItems) {
        var $ul = $acfdb.find('.choices .list');
        if($ul.hasClass('isLoading')){
            return;
        }
        if(removeItems){
            $ul.find('li').remove();
        }
        $ul.addClass('isLoading');
        $ul.append('<p><i class="acf-loading"></i></p>');
    };
    var removeItemLoading = function ($acfdb) {
        var $ul = $acfdb.find('.choices .list');
        $ul.removeClass('isLoading');
        $ul.find('.acf-loading').parent().remove();
    };
    var updateItemsByWords = function ($acfdb,nextPage) {
        var $ul = $acfdb.find('.choices .list');
        if($ul.hasClass('isLoading')){
            return false;
        }
        var _data = $acfdb.data('db');
        if(nextPage){
            _data.page++;
            if(_data.last_page < _data.page){
                return false;
            }
        }else{
            _data.page = 1;
        }
        _data['words'] = trim($acfdb.find('[name="s"]').val());
        $.ajax({
            url			: acfdb_ajax.url,
            dataType	: 'json',
            type		: 'post',
            data		: {
                'action'	: 'get_posts',
                'data'      : _data,
                'page_info' : nextPage?0:1
            }
        }).done(function (resultData) {
            if(resultData){
                removeItemLoading($acfdb);
                $ul.append(resultData.html);
                if(!nextPage && ('count' in resultData) && ('last_page' in resultData)){
                    _data.count = Number(resultData.count);
                    _data.page = 1;
                    _data.last_page = Number(resultData.last_page);
                }
                restoreItemStatus($acfdb);
            }
        });
        return true;
    };
    var restoreItemStatus = function ($acfdb) {
        if(!$acfdb){
            $('.acf-db').each(function (i,elm) {
                restoreItemStatus($(elm));
            });
            return;
        }
        var ids = $acfdb.find('[type="hidden"]').val().split(',');
        if(!ids.length)
            return;
        var i;
        for(i=0;i<ids.length;i++){
            var id = ids[i];
            var $item = $acfdb.find('.choices .list [data-id="'+id+'"]');
            $item.addClass('disabled');
        }
    };
    var init = function () {
        initAcfDb();
        restoreItemStatus();
    };
    var trim = function(str){
        return str.replace(/^[\s\/]+|[\s\/]+$/g,'');
    };
    var usableJQuery = function () {
        return ('jQuery' in window);
    };
    var preInit = function () {
        if (!usableJQuery()) {
            setTimeout(function () {
                preInit();
            }, 30);
            return;
        }
        $ = window.jQuery;
        $(function () {
            init();
        });
    };
    preInit();
})();