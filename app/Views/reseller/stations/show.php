<?php
/** @var array $station; @var array $owner; @var array|null $latest; @var string $adminPass */
echo \App\Core\View::renderPartial('partials/station_detail', [
    'station'       => $station,
    'owner'         => $owner,
    'latest'        => $latest,
    'adminPass'     => $adminPass,
    'base'          => 'reseller',
    'showSensitive' => true,
]);
