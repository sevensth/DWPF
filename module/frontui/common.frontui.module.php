<?php
class DWModuleFrontuiCommon extends DWModuleFrontuiAbstract
{
    const ActionBanner = 'banner';
    const ActionSlider = 'slider';
    const ActionBlurb = 'blurb';
    const ActionCateblock = 'cateblock';
    const ActionAjaxiealert = 'ajaxiealert';

    private $subModuleLayout = [
        self::ActionBanner => [],
        self::ActionSlider => [],
        self::ActionBlurb => [],
        self::ActionCateblock => [],
        self::ActionAjaxiealert => [],
    ];

    protected function submodulesConfigLayout()
    {
    	return $this->subModuleLayout;
    }
    
    public function recordShownOnce($action)
    {
        
    }


    const DWPFSlideConfigKeySetting = 'DWPF_SLIDE_SETTING';
    const DWPFSlideConfigKeyContent = 'DWPF_SLIDE_CONTENT';

    const DWPFSlideSettingKeyAuto = 'DWPF_SLIDE_AUTO';
    const DWPFSlideSettingKeyInterval = 'DWPF_SLIDE_INTERVAL';
    const DWPFSlideSettingKeyArrow = 'DWPF_SLIDE_ARROW';
    const DWPFSlideSettingKeyControl = 'DWPF_SLIDE_CONTROL';

    const DWPFSlideContentKeyTitle = 'DWPF_SLIDE_TITLE';
    const DWPFSlideContentKeyContent = 'DWPF_SLIDE_CONTENT';
    const DWPFSlideContentKeyBgColor = 'DWPF_SLIDE_BG_COLOR';
    const DWPFSlideContentKeyBgImageUrl = 'DWPF_SLIDE_BG_IMAGE';
    const DWPFSlideContentKeyImageUrl = 'DWPF_SLIDE_IMAGE';
    const DWPFSlideContentKeyImageAlt = 'DWPF_SLIDE_IMAGE_ALT';
    const DWPFSlideContentKeyImageAlign = 'DWPF_SLIDE_IMAGE_ALIGN';
    const DWPFSlideContentKeyTextColor = 'DWPF_SLIDE_TEXT_COLOR';
    const DWPFSlideContentKeyButtonUrl = 'DWPF_SLIDE_BUTTON_URL';
    const DWPFSlideContentKeyButtonTitle = 'DWPF_SLIDE_BUTTON_TITLE';
    const DWPFSlideContentKeyCanvasId = 'DWPF_SLIDE_CANVAS_ID';
    const DWPFSlideContentKeyBgCanvasId = 'DWPF_SLIDE_BG_CANVAS_ID';
    const DWPFSlideContentKeyBgCanvasJS = 'DWPF_SLIDE_BG_CANVAS_JS';

    const DWPFSlideContentKeyImageAlignBottom = 'bottom';
    const DWPFSlideContentValueMarkRef = 'DWPF_SLIDE_CONTENT_REF';

    private static $slideConfigDefaults = [self::DWPFSlideConfigKeySetting => [
        self::DWPFSlideSettingKeyAuto => false,
        self::DWPFSlideSettingKeyInterval => 10000,
        self::DWPFSlideSettingKeyArrow => true,
        self::DWPFSlideSettingKeyControl => true,
    ]];
    private static $slideContentDefaults = [
        self::DWPFSlideContentKeyBgColor => '#FFF',
        self::DWPFSlideContentKeyImageAlign => 'center',
        self::DWPFSlideContentKeyTextColor => 'light',
    ];

    public function parseSliderConfigJson($configJson, $referString)
    {
        $slideList = json_decode($configJson, true);
        //apply defaults
        if (is_array($slideList))
        {
            DWLibUtility::arrayApplyDefaults($slideList, self::$slideConfigDefaults);
            $contentList = &$slideList[self::DWPFSlideConfigKeyContent];
            $router = DWLibGlobals::getGlobal(DWLibGlobals::GlobalKeyDefaultRouter);
            foreach ($contentList as $key => $content)
            {
                DWLibUtility::arrayApplyDefaults($contentList[$key], self::$slideContentDefaults);
                foreach ($content as $k => $v)
                {
                    $ref = DWLibUtility::getContentBetweenMark(self::DWPFSlideContentValueMarkRef, ':', $v);
                    if ($ref)
                    {
                        $contentList[$key][$k] = DWLibUtility::getContentBetweenMark($ref, ':', $referString);
                    }
                    else
                    {
                        $contentList[$key][$k] = $router->urlFromPattern($v, $this->moduleGroup());
                    }
                }
            }
        }

        return $slideList;
    }
}
