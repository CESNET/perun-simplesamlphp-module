<?php
/**
 * This is a simple example of template with table of SPs
 *
 * @author Dominik Baránek <0Baranek.dominik0@gmail.com>
 */

$this->data['header'] = '';
$this->data['head'] = '<link rel="stylesheet"  media="screen" type="text/css" href="' . SimpleSAML\Module::getModuleUrl('perun/res/css/listOfSps.css')  . '" />';

$this->includeAtTemplateBase('includes/header.php');

$attrNames = $this->data['attrNames'];
$facilitiesWithAttributes = $this->data['facilitiesWithAttributes'];

$columns = '[';
$columns .= '{label: "Id", type: "number"},';
$columns .= '{label: "Name", type: "string"},';
$columns .= '{label: "Description", type: "string"},';
if (!empty($attrNames)) {
	$facilityAttributes = array_values($facilitiesWithAttributes)[0]['facilityAttributes'];
	foreach ($attrNames as $attrName) {
		if (typeIsSupported($facilityAttributes[$attrName]['type'])) {
			$columns .= '{label: "' . $facilityAttributes[$attrName]['displayName'] . '", type: "';
			if (strpos($facilityAttributes[$attrName]['type'], 'Integer')) {
				$columns .= 'number';
			} else if (strpos($facilityAttributes[$attrName]['type'], 'Boolean')) {
				$columns .= 'boolean';
			} else if (strpos($facilityAttributes[$attrName]['type'], 'String') || strpos($facilityAttributes[$attrName]['type'], 'Array')) {
				$columns .= 'string';
			}
			$columns .= '"},';
		}
	}
	$columns = substr($columns, 0, -1) . ']';
} else {
	$columns .= ']';
}

$rows = '[';
foreach ($facilitiesWithAttributes as $facilityWithAttributes) {
	$rows .= '{c:[';
	$rows .= '{v: "' . $facilityWithAttributes['facility']->getId() . '"}, ';
	$rows .= '{v: "' . $facilityWithAttributes['facility']->getName() . '"}, ';
	$rows .= '{v: "' . $facilityWithAttributes['facility']->getDescription() . '"}, ';
	foreach ($attrNames as $attrName) {
		if (typeIsSupported($facilityWithAttributes['facilityAttributes'][$attrName]['type'])) {
			if ((strpos($facilityWithAttributes['facilityAttributes'][$attrName]['type'], 'Array'))) {
				$rows .= '{v: "';
				foreach ($facilityWithAttributes['facilityAttributes'][$attrName]['value'] as $value) {
					$rows .= $value . '; ';
				}
				if (!empty($facilityWithAttributes['facilityAttributes'][$attrName]['value'])) {
					$rows = substr($rows, 0, -2) . '"}, ';
				} else {
					$rows .= '"}, ';
				}
			} else {
				$rows .= '{v: "' . $facilityWithAttributes['facilityAttributes'][$attrName]['value'] . '"}, ';
			}
		}
	}
	$rows = substr($rows, 0, -2) . ']},';
}
if (!empty($facilitiesWithAttributes)) {
	$rows = substr($rows, 0, -1) . ']';
} else {
	$rows .= ']';
}

?>
	<html>
	<head>
		<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
		<script type="text/javascript">
			google.charts.load('current', {'packages':['corechart', 'controls']});
			google.charts.setOnLoadCallback(drawDashboard);

			function drawDashboard() {

				var dashboard = new google.visualization.Dashboard(
					document.getElementById('dashboard_div'));

				var control = new google.visualization.ControlWrapper({
					'controlType': 'StringFilter',
					'containerId': 'stringFilter',
					'options': {
					    'matchType': 'any',
						'filterColumnLabel': 'Name',
					}
				});

				var chart  = new google.visualization.ChartWrapper({
					'chartType': 'Table',
					'containerId': 'table',
				});

				var data = new google.visualization.DataTable({
					cols: <?php echo $columns ?>,
					rows: <?php echo $rows ?>
				});

				dashboard.bind(control, chart);
				dashboard.draw(data);
			}
		</script>
	</head>
	<body>
		<h2><?php echo $this->t('{perun:perun:listOfSps_header}'); ?></h2>
		<div id="listOfSps">
			<div id="stringFilter"></div>
			<div id="table"></div>
		</div>
	</body>
	</html>

<?php
$this->includeAtTemplateBase('includes/footer.php');

function typeIsSupported($type) {
	return strpos($type, 'Integer') ||
		strpos($type, 'Boolean') ||
		strpos($type, 'String') ||
		strpos($type, 'Array');
}
