<?
    if ( ! defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
        die();
    }

    use Bitrix\Main\Localization\Loc as Loc;

    Loc::loadMessages(__FILE__);

    $this->setFrameMode(true);

    function getManagerFullName($id, &$arResult)
    {
        empty($id) ?: $managerFio = implode(
            " ",
            [
                $arResult["MANAGERS"][$id]["LAST_NAME"],
                $arResult["MANAGERS"][$id]["NAME"],
                $arResult["MANAGERS"][$id]["SECOND_NAME"],
            ]
        );
        empty($managerFio) ?: $fullname = ", руководитель: <b>$managerFio</b>";

        return $fullname;
    }

    function renderUserCard($department, &$arResult)
    {
        $html = "";
        $html .= $department["NAME"] . getManagerFullName($department["UF_MANAGER_ID"], $arResult);
        $html .= "<table>";
        foreach ($arResult["USERS"][$department["ID"]] as $departmentUsers) {
            $html .= "<tr><td>";
            $html .= $departmentUsers["LAST_NAME"] . " " .
                     $departmentUsers["NAME"] . ", " .
                     $departmentUsers["WORK_POSITION"] . "<br>";
            $html .= Loc::getMessage("USER_PROPERTY_SKILLS") . ":" . "<ul>";
            foreach ($departmentUsers["UF_SKILLS"] as $skill) {
                $html .= "<li>" . $arResult["SKILLS"][$skill] . "</li>";
            }
            $html .= "</ul></td></tr>";
        }
        $html .= "</table>";

        return $html;
    }

    if ($arParams["AJAX"] !== "Y"){ ?>
<div id="structure-container">
    <form id="filter" class="structure-form">
        <input type="text" name="AJAX" value="Y" hidden>
        <input type="text" name="fio" placeholder="ФИО"/><br>
        <? foreach ($arResult["SKILLS"] as $skill) { ?>
            <input type="checkbox" name="SKILL_<?=$skill?>" value="<?=$skill?>"><?=$skill?>
        <? } ?>
        <br>
        <input type="text" placeholder="Руководитель" name="manager"/><br>
        <input id="submit-btn" type="submit" value="Фильтр">
    </form>

    <div id="structure-result-block" class="structure-block">
        <? } ?>
        <ul>
            <? foreach ($arResult["I_SECTIONS"]["CHILDS"] as $sectionDL1) { ?>
                <li>
                    <?=renderUserCard($sectionDL1, $arResult)?>
                    <? foreach ($sectionDL1["CHILDS"] as $sectionDL2) { ?>
                        <ul>
                            <li>
                                <?=renderUserCard($sectionDL2, $arResult)?>
                                <ul>
                                    <? foreach ($sectionDL2["CHILDS"] as $sectionDL3) { ?>
                                        <li>
                                            <?=renderUserCard($sectionDL3, $arResult)?>
                                        </li>
                                    <? } ?>
                                </ul>
                            </li>
                        </ul>
                    <? } ?>
                </li>
            <? } ?>
        </ul>
        <? if ($arParams["AJAX"] !== "Y"){ ?>
    </div>
</div>
<? } ?>
