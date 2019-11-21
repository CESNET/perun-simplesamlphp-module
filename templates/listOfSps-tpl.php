<?php

use SimpleSAML\Module;

/**
 * This is a simple example of template with table of SPs
 *
 * @author Dominik Baránek <0Baranek.dominik0@gmail.com>
 */
$this->data['header'] = '';
$this->data['head'] = '<link rel="stylesheet"  media="screen" type="text/css" href="' .
    Module::getModuleUrl('perun/res/css/listOfSps.css') . '" />';

$this->data['head'] .= '<meta name="translations" id="translations" content="' . htmlspecialchars(json_encode([
    'saml_production' => $this->t('{perun:listOfSps:saml_production}'),
    'saml_test' => $this->t('{perun:listOfSps:saml_test}'),
    'oidc_production' => $this->t('{perun:listOfSps:oidc_production}'),
    'oidc_test' => $this->t('{perun:listOfSps:oidc_test}'),
])) . '">';

$statistics = $this->data['statistics'];
$attributesToShow = $this->data['attributesToShow'];
$samlServices = $this->data['samlServices'];
$oidcServices = $this->data['oidcServices'];
$allServices = $this->data['allServices'];


$productionServicesCount = $statistics['samlServicesCount'] - $statistics['samlTestServicesCount'] +
    $statistics['oidcServicesCount'] - $statistics['oidcTestServicesCount'];
$testServicesCount = $statistics['samlTestServicesCount'] + $statistics['oidcTestServicesCount'];
$samlProductionCount = $statistics['samlServicesCount'] - $statistics['samlTestServicesCount'];
$oidcProductionCount = $statistics['oidcServicesCount'] - $statistics['oidcTestServicesCount'];

$this->data['head'] .= '<meta name="data" id="data" content="' . htmlspecialchars(json_encode([
    'samlProductionCount' => $samlProductionCount,
    'samlTestServicesCount' => $statistics['samlTestServicesCount'],
    'oidcProductionCount' => $oidcProductionCount,
    'oidcTestServicesCount' => $statistics['oidcTestServicesCount'],
])) . '">';

$this->includeAtTemplateBase('includes/header.php');
?>

<div class="row">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-12">
                <h3><?php echo $this->t('{perun:listOfSps:statistics}') ?></h3>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <table class="table table-stats">
                    <tr>
                        <th></th>
                        <th><?php echo $this->t('{perun:listOfSps:production_services}') ?></th>
                        <th><?php echo $this->t('{perun:listOfSps:test_services}') ?></th>
                    </tr>
                    <tr>
                        <th><?php echo $this->t('{perun:listOfSps:all}') ?></th>
                        <th><?php echo $productionServicesCount ?></th>
                        <th><?php echo $testServicesCount ?></th>
                    </tr>
                    <tr>
                        <th><?php echo $this->t('{perun:listOfSps:saml}') ?></th>
                        <th><?php echo $samlProductionCount ?></th>
                        <th><?php echo $statistics['samlTestServicesCount'] ?></th>
                    </tr>
                    <tr>
                        <th><?php echo $this->t('{perun:listOfSps:oidc}') ?></th>
                        <th><?php echo $oidcProductionCount ?></th>
                        <th><?php echo $statistics['oidcTestServicesCount'] ?></th>
                    </tr>
                </table>
            </div>
            <div class="col-md-6 center">
                <canvas id="myChart"></canvas>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-12">
                <h3><?php echo $this->t('{perun:listOfSps:services}') ?></h3>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 table-responsive">
                <table class="table table-striped" id="table1">
                    <thead>
                    <tr>
                        <th><?php echo $this->t('{perun:listOfSps:name}') ?></th>
                        <th><?php echo $this->t('{perun:listOfSps:authenticate_protocol}') ?></th>
                        <th><?php echo $this->t('{perun:listOfSps:description}') ?></th>
                        <?php
                        foreach ($attributesToShow as $attr) {
                            if (!empty($samlServices)) {
                                echo "<th class='" .
                                    getClass(array_values($samlServices)[0]['facilityAttributes'][$attr]) .
                                    "'>" . array_values($samlServices)[0]['facilityAttributes'][$attr]['displayName']
                                    . "</th>";
                            }
                        }
                        ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($allServices as $service) {
                        if (empty($service['showOnServiceList']['value']) ||
                            !($service['showOnServiceList']['value'])
                        ) {
                            continue;
                        }
                        echo '<tr>';
                        echo '<td>' . printServiceName($service) . '</td>';
                        if (array_key_exists($service['facility']->getID(), $samlServices)) {
                            echo 'td>' . $this->t('{perun:listOfSps:saml}') . '</td>';
                        } else {
                            echo '<td>' . $this->t('{perun:listOfSps:oidc}') . '</td>';
                        }
                        echo '<td>' . $service['facility']->getDescription() . '</td>';
                        foreach ($attributesToShow as $attr) {
                            $value = printAttributeValue($service['facilityAttributes'][$attr], $service, $attr);
                            echo $value;
                        }
                    }
                    echo '</tr>';
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$this->includeAtTemplateBase('includes/footer.php');

function printServiceName($service)
{
    if (empty($service['loginURL']['value'])
    ) {
        return $service['facility']->getName();
    } else {
        return "<a class='customLink' href='" . $service['loginURL']['value'] . "'>" .
            $service['facility']->getName() . "</a>";
    }
}

function printAttributeValue($attribute, $service, $attr)
{
    $value = $attribute['value'];
    if (empty($value) && $attribute['type'] !== 'java.lang.Boolean') {
        return "<td class='center'>&horbar;</td>";
    }
    $string = '';
    if ($attribute['type'] === 'java.lang.String' || $attribute['type'] === 'java.lang.LargeString') {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $string = '<a class="customLink" href="' . $value . '">' . $value . '</a>';
        } else {
            $string = $value;
        }
    } elseif ($attribute['type'] === 'java.lang.Integer') {
        $string = $value;
    } elseif ($attribute['type'] === 'java.lang.Boolean') {
        if ($value !== null && $value) {
            $string = '&#x2714;';
        } else {
            $string = '&#x2715;';
        }
    } elseif ($attribute['type'] === 'java.util.ArrayList' || $attribute['type'] === 'java.lang.LargeArrayList') {
        $string = '<ul>';
        foreach ($value as $v) {
            $string .= '<li>' . $v . '</li>';
        }
        $string .= '</ul>';
    } elseif ($attribute['type'] === 'java.util.LinkedHashMap') {
        $string = '<ul>';
        foreach ($value as $k => $v) {
            $string .= '<li>' . $k . ' &rarr; ' . $v . '</li>';
        }
        $string .= '</ul>';
    }
    if (!empty($string)) {
        return '<td class="' . getClass($service['facilityAttributes'][$attr]) . '">' . $string . '</td>';
    } else {
        return '<td/>';
    }
}

function getClass($attribute)
{
    if ($attribute['type'] === 'java.lang.String') {
        return 'string';
    } elseif ($attribute['type'] === 'java.lang.Integer') {
        return 'integer';
    } elseif ($attribute['type'] === 'java.lang.Boolean') {
        return 'boolean';
    } elseif ($attribute['type'] === 'java.util.ArrayList' || $attribute['type'] === 'java.util.LargeArrayList') {
        return 'array';
    } elseif ($attribute['type'] === 'java.util.LinkedHashMap') {
        return 'map';
    } else {
        return '';
    }
}

?>

<script src="<?php echo htmlspecialchars(\SimpleSAML\Module::getModuleURL('chartjs/Chart.bundle.min.js'));?>"></script>

<script src="<?php echo htmlspecialchars(\SimpleSAML\Module::getModuleURL('perun/listOfSps.js'));?>"></script>
