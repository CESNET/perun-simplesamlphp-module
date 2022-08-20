<?php declare(strict_types=1);

use SimpleSAML\Module;
use SimpleSAML\Module\perun\Auth\Process\SpAuthorization;
use SimpleSAML\Module\perun\PerunConstants;
use SimpleSAML\Utils\HTTP;

$stateId = $this->data[SpAuthorization::PARAM_STATE_ID];
$registrationUrl = $this->data[SpAuthorization::PARAM_REGISTRATION_URL];
$spMetadata = $this->data[SpAuthorization::PARAM_SP_METADATA];
$registrationData = $this->data[SpAuthorization::PARAM_REGISTRATION_DATA];

if (!empty($_POST)) {
    $callback = Module::getModuleURL(SpAuthorization::CALLBACK, [
        PerunConstants::STATE_ID => $stateId,
    ]);

    $params = [];
    $vo = explode(':', $_POST['selectedGroup'], 2)[0];
    $group = explode(':', $_POST['selectedGroup'], 2)[1];

    $params[PerunConstants::VO] = $vo;
    if ($group !== PerunConstants::GROUP_MEMBERS) {
        $params[PerunConstants::GROUP] = $group;
    }

    $params[PerunConstants::TARGET_NEW] = $callback;
    $params[PerunConstants::TARGET_EXISTING] = $callback;
    $params[PerunConstants::TARGET_EXTENDED] = $callback;
    HTTP::redirectTrustedURL($registrationUrl, $params);
}
$this->data['head'] = '<link rel="stylesheet" media="screen" type="text/css" href="' .
    Module::getModuleUrl('perun/res/css/perun_identity_choose_vo_and_group.css') . '" />';

$this->data['header'] = '';

$this->includeAtTemplateBase('includes/header.php');

$header = $this->t('{perun:perun:choose-vo-and-group-tpl_header-part1}');
if (!empty($serviceName) && !empty($informationURL)) {
    $header .= '<a href="' . $informationURL . '">' . $serviceName . '</a>';
} elseif (!empty($serviceName)) {
    $header .= $serviceName;
}
$header .= $this->t('{perun:perun:choose-vo-and-group-tpl_header-part2}');

echo '<div id="head">';
echo '<h1>' . $header . '</h1>';
echo '</div>';

echo '<div class="msg">' . $this->t('{perun:perun:choose-vo-and-group-tpl_message}') . '</div>';
?>
    <div class="list-group mt-4">
        <form action="" method="post">
            <label for="selectedGroup">
                <?php echo $this->t('{perun:perun:choose-vo-and-group-tpl_select-group}'); ?>
            </label>
            <select class="selectGroup form-control" id="selectedGroup" name="selectedGroup" class="form-control mt-4" required>
                <?php
                foreach ($registrationData as $group) {
                    echo '<option class="groupOption" value="' . $group->getUniqueName() . '" >'
                        . $group->getDescription()
                        . '</option>';
                }
?>
            </select>
            <input type="submit" value="<?php echo $this->t('{perun:perun:choose-vo-and-group-tpl_continue}'); ?>"
                   class="btn btn-lg btn-primary btn-block mt-4">
        </form>
    </div>

<?php
$this->includeAtTemplateBase('includes/footer.php');
