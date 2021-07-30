<?php declare(strict_types=1);

use SimpleSAML\Module;
use SimpleSAML\Module\perun\ListOfSps;

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
                        <?php
                        foreach ($attributesToShow as $attr) {
                            if (! empty($samlServices)) {
                                echo "<th class='" . ListOfSps::getClass(
                                    array_values($samlServices)[0]['facilityAttributes'][$attr]['type']
                                ) . "'>" . array_values($samlServices)[0]['facilityAttributes'][$attr]['displayName']
                                . '</th>';
                            }
                        }
                        ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($allServices as $service) {
                        if (empty($service['showOnServiceList']['value']) ||
                            ! ($service['showOnServiceList']['value'])
                        ) {
                            continue;
                        }
                        echo '<tr>';
                        echo '<td>'
                            . ListOfSps::printServiceName(
                                ListOfSps::getPreferredTranslation($service['name']['value'], $this->getLanguage()),
                                $service['loginURL']['value'] ?? null
                            )
                            . '</td>';
                        if (array_key_exists($service['facility']->getID(), $samlServices)) {
                            echo '<td>' . $this->t('{perun:listOfSps:saml}') . '</td>';
                        } else {
                            echo '<td>' . $this->t('{perun:listOfSps:oidc}') . '</td>';
                        }
                        foreach ($attributesToShow as $attr) {
                            $type = $service['facilityAttributes'][$attr]['type'];
                            $value = $service['facilityAttributes'][$attr]['value'];
                            if ($value !== null && in_array($attr, $this->data['multilingualAttributes'], true)) {
                                $type = 'java.lang.String';
                                $value = ListOfSps::getPreferredTranslation($value, $this->getLanguage());
                            }
                            echo ListOfSps::printAttributeValue($type, $value);
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

?>

<script src="<?php echo htmlspecialchars(Module::getModuleURL('chartjs/Chart.bundle.min.js')); ?>"></script>

<script src="<?php echo htmlspecialchars(Module::getModuleURL('perun/listOfSps.js')); ?>"></script>
