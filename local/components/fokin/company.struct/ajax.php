<?php
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
    $APPLICATION->IncludeComponent(
        "fokin:company.struct",
        "",
        array(
            "IBLOCK_ID"   => 14,
            "HL_BLOCK_ID" => 1,
            "IBLOCK_CODE" => 'Company',
            "CACHE_TIME"  => 3600,
            "REQUEST"     => $_REQUEST
        )
    );
