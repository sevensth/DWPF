(function($){
	$.load_more_articles = function (container) {
        var articleContainer = $(container).find('.dw_load_more_result');
        var loadButton = $(container).find('.dw_load_more a.et_pb_promo_button');
        var baseUrl = loadButton.attr('base');
        var baseHRef = loadButton.attr('baseHRef');
        var page = loadButton.attr('page');
        var offset = loadButton.attr('offset');
        var count = loadButton.attr('count');
        var isLoading = false;
        var nextLoadUrl = null;
        var nextLoadHRef = null;
        var everythingOK = false;
        checkEverything();

        if (everythingOK) {
            loadButton.click(function (event) {
                load(nextLoadUrl);
                event.preventDefault();
            });

            count = parseInt(count);
            page = page ? parseInt(page) : -1;
            offset = offset ? parseInt(offset) : -1;

            buildLoadUrl();
        }

        function load(url) {
            if (isLoading) {
                return;
            }

            setLoadStatusToLoading();
            $.ajax({
                url: url,
                dataType: "json",
                timeout: 30000
            }).done(function (data, textStatus, jqXHR) {
                if (validateData(data)) {
                    var returnCount = parseInt(data.returnCount);
                    if (returnCount > 0) {
                        $(articleContainer).append(data.content);
                        argumentsGoNext(returnCount);
                        buildLoadUrl();
                        setLoadStatusToIdle();
                        updateButtonHRef();
                    }

                    if (returnCount <= 0 || data.noMoreData) {
                        $(loadButton).remove();
                    }
                }
                else {
                    setLoadStatusToFailed();
                }
            }).fail(function (jqXHR, textStatus, errorThrown) {
                setLoadStatusToFailed();
            }).always(function (dataOrJqXHR, textStatus, jqXHROrErrorThrown) {
            });
        }

        function argumentsGoNext(returnCount) {
            if (page >= 0) {
                page++;
            }
            else if (offset >= 0) {
                offset += returnCount;
            }

            if (count >= 0) {
                count = returnCount * 2;
            }
        }

        function buildLoadUrl() {
            nextLoadUrl = baseUrl;
            nextLoadHRef = baseHRef;
            if (page >= 0) {
                nextLoadUrl += '&page=' + page;
                nextLoadHRef += '&page=' + page;
            }
            else if (offset >= 0) {
                nextLoadUrl += '&offset=' + offset;
                nextLoadHRef += '&offset=' + offset;
            }

            if (count >= 0) {
                nextLoadUrl += '&count=' + count;
                nextLoadHRef += '&count=' + count;
            }
        }

        function updateButtonHRef() {
            loadButton.attr('href', nextLoadHRef);
        }

        function checkEverything() {
            everythingOK = articleContainer.length > 0 && loadButton.length > 0 && count && (page || offset) && baseUrl.length > 0;
        }

        function setLoadStatusToIdle() {
            isLoading = false;
            $(loadButton).removeClass('dw_load_more_failed dw_loading').addClass('dw_load_more_idle');
        }

        function setLoadStatusToLoading() {
            isLoading = true;
            $(loadButton).removeClass('dw_load_more_failed dw_load_more_idle').addClass('dw_loading');
        }

        function setLoadStatusToFailed() {
            isLoading = false;
            $(loadButton).removeClass('dw_loading dw_load_more_idle').addClass('dw_load_more_failed');
        }

        function validateData(data) {
            return data.status == 'success';
        }
    };

    $.expand_related_articles = function ($container) {
        //constructor
        var $cateMetaElements = $container.find('a.dw_post_meta_element_category');
        var $metaExpandContainer = $container.find('.dw-page-banner-meta-container');
        var $relatedPostRowPlaceholder = $container.find('.dw-page-banner-meta-expand-row-display .dw-page-banner-meta-expand-row');
        var $relatedPostRowProvider = $container.find('.dw-page-banner-meta-expand-row-storage .dw-page-banner-meta-expand-row');
        if ($cateMetaElements.length > 0 && $metaExpandContainer.length > 0 && $relatedPostRowPlaceholder.length > 0 && $relatedPostRowProvider.length > 0) {
            $cateMetaElements.click(function (event) {
                if (event.which != 1 || event.shiftKey || event.ctrlKey || event.altKey || event.metaKey) {
                    return;
                }
                cateMetaClicked($(this), true);
                event.preventDefault();
            });

            var metaHoverCSSClass = 'dw-page-banner-meta-hover';
            $cateMetaElements.mouseenter(function () {
                $container.addClass(metaHoverCSSClass);
            });
            $cateMetaElements.mouseleave(function () {
                $container.removeClass(metaHoverCSSClass);
            });
        }
        else {
            return;
        }

        //public
        this.expandForFirstCateMeta = function () {
            cateMetaClicked($($cateMetaElements[0]), false);
        };

        //private
        var metaActiveCSSClass = 'dw_post_meta_element_active';
        var metaExpandCSSClass = 'dw-page-banner-expand-meta';
        var meteHintColorCSSClassPrefix = 'dw-post-meta-hint-color';
        var relatedPostRowAnimateCSSClass = 'animated';
        var activeMetaIndex;

        function metaHintColorCSSClassForIndex(index) {
            return meteHintColorCSSClassPrefix + index;
        }

        function closeRelatedArea($element) {
            $container.removeClass(metaExpandCSSClass);
            $metaExpandContainer.removeClass(metaHintColorCSSClassForIndex(activeMetaIndex));
            $element.removeClass(metaActiveCSSClass);
            activeMetaIndex = undefined;
        }

        function openRelatedArea($element) {
            //clear old status
            if (activeMetaIndex >= 0) {
                $($cateMetaElements[activeMetaIndex]).removeClass(metaActiveCSSClass);
                $metaExpandContainer.removeClass(metaHintColorCSSClassForIndex(activeMetaIndex));
            }
            //build new status
            activeMetaIndex = $cateMetaElements.index($element);
            $element.addClass(metaActiveCSSClass);
            if (!$container.hasClass(metaExpandCSSClass)) {
                $container.addClass(metaExpandCSSClass);
            }
            $metaExpandContainer.addClass(metaHintColorCSSClassForIndex(activeMetaIndex));
            //refresh articles block
            var cloned = $($relatedPostRowProvider[activeMetaIndex]).clone();
            cloned.addClass(relatedPostRowAnimateCSSClass);
            $relatedPostRowPlaceholder.replaceWith(cloned);
            $relatedPostRowPlaceholder = cloned;
        }

        function cateMetaClicked($element, canToggle) {
            if ($element.hasClass(metaActiveCSSClass)) {
                if (canToggle) {
                    closeRelatedArea($element);
                }
            }
            else {
                openRelatedArea($element);
            }
        }
    };

    /**
     * @param $triggerElement1  When element1 is shown, condition1 is satisfied.
     * @param $triggerElement2 When element1 is shown, and condition1 is satisfied, then trigger expanding.
     */
    $.relatedPostAutoExpander = function(relatedArticlesExpander, $triggerElement1, $triggerElement2)
    {
        var condition1 = false;
        var maxAutoExpand = 1;
        var autoExpand = 0;
        
        $triggerElement1.waypoint({
            offset: '95%',
            handler: function(direction) {
                if (direction == 'down')
                {
                    condition1 = true;
                    $triggerElement1.waypoint('destroy');
                }
            }
        });

        $triggerElement2.waypoint({
            offset: 0,
            handler: function(direction) {
                if (autoExpand < maxAutoExpand && condition1 && direction == 'up')
                {
                    autoExpand++;
                    relatedArticlesExpander.expandForFirstCateMeta();
                    if (autoExpand >= maxAutoExpand)
                    {
                        $triggerElement2.waypoint('destroy');
                    }
                }
            }
        });
    };

    //main
    var ajaxArticleLoader = null;
    var relatedArticlesExpander = null;
    var relatedArticlesAutoExpander = null;
    $(document).ready(function()
    {
	    var container = $('.dw_load_more_container');//TODO:this can be an array
	    if (container.length > 0) {
	        ajaxArticleLoader = new $.load_more_articles(container);
	    }

        var $postBanner = $('.post .dw-page-banner');
        if ($postBanner.length > 0)
        {
            relatedArticlesExpander = new $.expand_related_articles($postBanner);
            relatedArticlesAutoExpander = new $.relatedPostAutoExpander(relatedArticlesExpander, $('#comment_loader'), $postBanner);
        }
	});
})(jQuery);