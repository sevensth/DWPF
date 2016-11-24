(function($){
    $.ie678_prompt = function()
    {
        var iePromptOnceSessionCookie = 'iePromptOnceSessionCookie';

        this.showAlertIfNeeded = function() {
            if ($.IE.is678() && !$.docCookies.hasItem(iePromptOnceSessionCookie))
            {
                var url = $('html').attr('alerturl');
                if (url && url.length > 0)
                {
                    loadAndShowIEAlert(url);
                }
            }
        }

        function validateData(data)
        {
            if (data.status == 'success')
            {
                return true;
            }
            return false;
        }

        function loadAndShowIEAlert(url)
        {
            $.ajax({
                url: url,
                dataType: "json",
                timeout : 30000
            }).done(function(data, textStatus, jqXHR) {
                if (validateData(data))
                {
                    showIEAlert(data.content);
                    bindClickToCancel(data.content_dom_id);
                }
                else
                {
                    showIEAlertFallback();
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                showIEAlertFallback();
            }).always(function(dataOrJqXHR, textStatus, jqXHROrErrorThrown) {
            });
        }

        function showIEAlert(content)
        {
            $('body').append(content);
            recordShowingInCookie();
        }

        function showIEAlertFallback()
        {
            var message = '您正在使用IE' + $.IE.version() + '浏览器，要正常浏览本站内容，请升级IE9及以上，或使用Chrome、Safari、Firefox';
            alert(message);
            recordShowingInCookie();
        }

        function recordShowingInCookie()
        {
            //expired after 10min
            $.docCookies.setItem(iePromptOnceSessionCookie, 3, 10*60, '/');
        }

        function bindClickToCancel(domId)
        {
            $("#"+domId).click(function(){
                $(this).remove();
                $('body').css('overflow': 'auto');
            });
        }
    }
    
    $(document).ready(function()
    {
        var ie678Prompt = new $.ie678_prompt();
        ie678Prompt.showAlertIfNeeded();
	});
})(jQuery)