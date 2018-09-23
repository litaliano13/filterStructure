<?
    if ( ! defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
        die();
    }

    use Bitrix\Main;
    use Bitrix\Main\Localization\Loc as Loc;

    Loc::loadMessages(__FILE__);

    $arComponentParameters = array(
        'GROUPS'     => array(),
        'PARAMETERS' => array(
            'IBLOCK_ID'   => array(
                'PARENT' => 'BASE',
                'NAME'   => Loc::getMessage('PARAMETERS_IBLOCK_ID'),
                'TYPE'   => 'STRING'
            ),
            'IBLOCK_CODE' => array(
                'PARENT' => 'BASE',
                'NAME'   => Loc::getMessage('PARAMETERS_IBLOCK_CODE'),
                'TYPE'   => 'STRING'
            ),
            'CACHE_TIME'  => array(
                'DEFAULT' => 3600
            )
        )
    );
