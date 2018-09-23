<?
    if ( ! defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
        die();
    }

    use Bitrix\Main;
    use Bitrix\Main\Localization\Loc as Loc;
    use Bitrix\Highloadblock\HighloadBlockTable as HLT;

    class CCompanyStructure extends CBitrixComponent
    {
        const UTF8_ENCODING = "UTF-8";
        const SITE_ENCODING = "windows-1251";

        public function onIncludeComponentLang()
        {
            $this->includeComponentLang(basename(__FILE__));
            Loc::loadMessages(__FILE__);
        }

        /**
         * @param array $arParams
         *
         * @return array
         */
        public function onPrepareComponentParams($params)
        {
            $result = array(
                'IBLOCK_ID'   => (int)$params['IBLOCK_ID'],
                'HL_BLOCK_ID' => (int)$params['HL_BLOCK_ID'],
                'IBLOCK_CODE' => trim($params['IBLOCK_CODE']),
                'AJAX'        => $_REQUEST['AJAX'] == 'Y' ? 'Y' : 'N',
                'CACHE_TIME'  => ! empty ($params['CACHE_TIME']) ? (int)$params['CACHE_TIME'] : 3200,
                'REQUEST'     => $params['REQUEST']
            );

            return $result;
        }

        public function executeComponent()
        {
            global $USER;
            try {
                $this->checkModules();

                if ($this->StartResultCache($this->arParams["CACHE_TIME"], $USER->GetGroups())) {
                    $this->getResult();
                    $this->includeComponentTemplate();
                }
            } catch (Exception $e) {
                $this->AbortResultCache();
                ShowError($e->getMessage());
            }
        }

        protected function checkModules()
        {
            if ( ! Main\Loader::includeModule('iblock')) {
                throw new Main\LoaderException(Loc::getMessage('IBLOCK_MODULE_NOT_INSTALLED'));
            }
            if ( ! Main\Loader::includeModule('highloadblock')) {
                throw new Main\LoaderException(Loc::getMessage('HLBLOCK_MODULE_NOT_INSTALLED'));
            }
        }

        protected function getResult()
        {
            list($this->arResult["I_SECTIONS"], $this->arResult["MANAGERS"]) = $this->getSectionList();
            $this->arResult["SKILLS"] = $this->getSkillsData();
            $generalFilter            = $this->combineGeneralFilter();
            $this->arResult["USERS"]  = $this->getUsersSortedByDepartment($generalFilter);
        }

        protected function getSectionList()
        {
            $dbSection = CIBlockSection::GetList(
                Array('DEPTH_LEVEL' => 'ASC', "SORT" => "ASC"),
                Array(
                    'ACTIVE'        => 'Y',
                    'GLOBAL_ACTIVE' => 'Y',
                    'IBLOCK_CODE'   => $this->arParams["IBLOCK_CODE"],
                    'IBLOCK_ID'     => $this->arParams["IBLOCK_ID"],
                ),
                false,
                $arSelect = Array(
                    "ID",
                    "NAME",
                    "IBLOCK_SECTION_ID",
                    "IBLOCK_ID",
                    "DEPTH_LEVEL",
                    "UF_MANAGER_ID"
                )
            );

            $managers = array();
            while ($arSection = $dbSection->GetNext()) {
                empty($arSection["UF_MANAGER_ID"]) ?: $managers[$arSection["UF_MANAGER_ID"]]["SECTION_MANAGEMENT"][] = $arSection["ID"];

                $sectionId       = $arSection['ID'];
                $parentSectionId = (int)$arSection['IBLOCK_SECTION_ID'];

                $arDepartments[$parentSectionId]['CHILDS'][$sectionId] = $arSection;
                $arDepartments[$sectionId]                             = &$arDepartments[$parentSectionId]['CHILDS'][$sectionId];
            }

            if ( ! empty($managers)) {
                $order    = array('sort' => 'asc');
                $tmp      = array('sort');
                $arFilter = ["ID" => implode("|", array_keys($managers))];
                $dbUsers  = CUser::GetList($order, $tmp, $arFilter);

                while ($arUsers = $dbUsers->GetNext()) {
                    $managers[$arUsers["ID"]] = array_merge($managers[$arUsers["ID"]], $arUsers);
                }
            }

            return array(array_shift($arDepartments), $managers);
        }

        protected function getSkillsData()
        {
            $hlblock           = HLT::getById($this->arParams["HL_BLOCK_ID"])->fetch();
            $entity            = HLT::compileEntity($hlblock);
            $entity_data_class = $entity->getDataClass();

            $rsData = $entity_data_class::getList(array(
                "select" => array('*'),
                "filter" => array(),
                "order"  => array("ID" => "ASC")
            ));

            $skills = array();
            while ($arRes = $rsData->Fetch()) {
                $skills[$arRes["ID"]] = $arRes["UF_SKILL"];
            }

            return $skills;
        }

        protected function combineGeneralFilter()
        {
            $ufSkillsFilter      = $this->prepareSkillsFilter();
            $ufFioFilter         = $this->prepareFioFilter();
            $ufDepartmentsFilter = $this->prepareManagerFilter();

            $genFilter = array();

            empty($ufSkillsFilter) ?: $genFilter["UF_SKILLS"] = $ufSkillsFilter;
            empty($ufFioFilter) ?: $genFilter = array_merge($genFilter, $ufFioFilter);
            empty($ufDepartmentsFilter) ?: $genFilter["UF_DEPARTMENTS"] = $ufDepartmentsFilter;

            return $genFilter;
        }

        protected function prepareSkillsFilter()
        {
            //Выбираем из $this->arParams["REQUEST"] все поля, ключ которых начинается с SKILL_
            $keys = array_keys($this->arParams["REQUEST"]);

            function filterSkillsFields($key)
            {
                return substr($key, 0, 6) === "SKILL_";
            }

            $getFilterSkills = function ($key) {
                return ! empty($this->arParams["REQUEST"][$key]) ?
                    iconv(self::UTF8_ENCODING, self::SITE_ENCODING, $this->arParams["REQUEST"][$key]) : "";
            };

            $filter = array_map($getFilterSkills, array_filter($keys, "filterSkillsFields"));

            foreach ($filter as $key => $val) {
                $filter[$key] = array_search($val, $this->arResult["SKILLS"]);
            };

            return $filter;
        }

        protected function prepareFioFilter()
        {
            $filter = [];

            if (empty($this->arParams["REQUEST"]["fio"])) {
                return $filter;
            }
            $arRequestFio = explode(" ",
                iconv(self::UTF8_ENCODING, self::SITE_ENCODING, $this->arParams["REQUEST"]["fio"]));

            $filter = array(
                "LAST_NAME"   => $arRequestFio[0],
                "NAME"        => $arRequestFio[1],
                "SECOND_NAME" => $arRequestFio[2],
            );

            return $filter;
        }

        protected function prepareManagerFilter()
        {
            $departments = [];

            if (empty($this->arParams["REQUEST"]["manager"])) {
                return $departments;
            }

            $arRequestManager = explode(" ",
                iconv(self::UTF8_ENCODING, self::SITE_ENCODING, $this->arParams["REQUEST"]["manager"]));

            foreach ($this->arResult["MANAGERS"] as $curManager) {
                $curManager["LAST_NAME"] === $arRequestManager[0] &&
                $curManager["NAME"] === $arRequestManager[1] &&
                $curManager["SECOND_NAME"] === $arRequestManager[2] ?
                    $departments = $curManager["SECTION_MANAGEMENT"] : false;
            }

            return $departments;
        }

        protected function getUsersSortedByDepartment($arFilter = array())
        {
            $order              = array('sort' => 'asc');
            $tmp                = array('sort');
            $arParams["SELECT"] = array("UF_DEPARTMENTS", "UF_SKILLS");
            $dbUsers            = CUser::GetList($order, $tmp, $arFilter, $arParams);

            $sortedUsers = array();

            while ($arUsers = $dbUsers->GetNext()) {
                foreach ($arUsers["UF_DEPARTMENTS"] as $DEPARTMENT) {
                    $sortedUsers[$DEPARTMENT][] = $arUsers;
                }
            }

            return $sortedUsers;
        }
    }