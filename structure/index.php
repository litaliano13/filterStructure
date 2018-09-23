<?
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
    $APPLICATION->SetTitle("Структура компании");

    $APPLICATION->IncludeComponent(
        "fokin:company.struct",
        "",
        array(
            "IBLOCK_ID"   => 14,
            "HL_BLOCK_ID" => 1,
            "IBLOCK_CODE" => 'Company',
            "CACHE_TIME"  => 3600
        )
    );
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");