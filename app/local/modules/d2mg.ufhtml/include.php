<?
IncludeModuleLangFile(__FILE__);

global $DB;
$db_type = strtolower($DB->type);
CModule::AddAutoloadClasses(
    "d2mg.ufhtml", [
        "CCustomTypeHtml" => "classes/general/customtypehtml.php",
    ]
);

?>
