<?php

/**
 * This is simple example of template where user can choose where they want to register to access the requested service
 *
 * Allow type hinting in IDE
 * @var SimpleSAML_XHTML_Template $this
 * @var sspmod_perun_model_Group[] $groups;
 * @var sspmod_perun_model_Vo $vo
 */
$vo = $this->data['vo'];
$groups = $this->data['groups'];

$this->data['header'] = 'Select group which fits you most';

$this->includeAtTemplateBase('includes/header.php');

echo 'It will give you access to the requested service.';

echo '<div class="list-group">';
foreach ($groups as $group) {
	$url = getRegisterUrl($this->data['registerUrl'], $this->data['callbackParamName'], $this->data['callbackUrl'], $vo->getShortName(), $group->getName());
	echo "<a href='$url' class='list-group-item'><b>{$group->getName()}</b> - {$group->getDescription()}</a>";
}
echo '</div>';



$this->includeAtTemplateBase('includes/footer.php');



/**
 * @param $registerUrl
 * @param $callbackParamName
 * @param $callbackUrl
 * @param $voShortName
 * @param $groupName
 * @return string url where user should continue to register to group
 */
function getRegisterUrl($registerUrl, $callbackParamName, $callbackUrl, $voShortName, $groupName) {
	return \SimpleSAML\Utils\HTTP::addURLParameters($registerUrl, array(
		'vo' => $voShortName,
		'group' => $groupName,
		$callbackParamName => $callbackUrl,
	));
}