<?

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (class_exists("d2mg_ufhtml")) {
    return;
}

class d2mg_ufhtml extends CModule
{
    var $MODULE_ID = "d2mg.ufhtml";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
    var $errors;

    public function __construct()
    {
        if(file_exists(__DIR__."/version.php")){
            $arModuleVersion = [];

            include_once(__DIR__ . "/version.php");

            if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion)) {
                $this->MODULE_VERSION      = $arModuleVersion["VERSION"];
                $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
                $this->MODULE_NAME        = Loc::getMessage("inst_module_name");
                $this->MODULE_DESCRIPTION = Loc::getMessage("inst_module_desc");
                $this->PARTNER_NAME       = Loc::getMessage("inst_module_partner");
                $this->PARTNER_URI        = Loc::getMessage("inst_module_partner_uri");
            }
        }
    }

    public function InstallDB(): bool
    {
        RegisterModule("d2mg.ufhtml");
        RegisterModuleDependences("main", "OnUserTypeBuildList", "d2mg.ufhtml", "CCustomTypeHtml", "GetUserTypeDescription");

        return true;
    }

    public function UnInstallDB(): bool
    {
        UnRegisterModuleDependences("main", "OnUserTypeBuildList", "d2mg.ufhtml", "CCustomTypeHtml", "GetUserTypeDescription");
        UnRegisterModule("d2mg.ufhtml");

        return true;
    }

    public function DoInstall()
    {
        global $APPLICATION;
        $this->InstallDB();
        $APPLICATION->IncludeAdminFile(Loc::getMessage("inst_inst_title"), $this->getPath(Application::getDocumentRoot() . "/local/modules/d2mg.ufhtml/install/step.php"));
    }

    public function DoUninstall()
    {
        global $APPLICATION;
        $this->UnInstallDB();
        $APPLICATION->IncludeAdminFile(Loc::getMessage("inst_uninst_title"), $this->getPath(Application::getDocumentRoot() . "/local/modules/d2mg.ufhtml/install/unstep.php"));
    }

    public function getPath(string $path)
    {
        if(!file_exists($path)) {
            return str_replace('local', 'bitrix', $path);
        }
        return $path;
    }

}

?>
