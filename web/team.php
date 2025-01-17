<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Enums\FilterableColumn;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Scheduler;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Teams;
use Elabftw\Models\Templates;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * The TEAM page
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Team');
// default response is error page with general error message
/** @psalm-suppress UncaughtThrowInGlobalScope */
$Response = new Response();
$Response->prepare($App->Request);

try {
    $Teams = new Teams($App->Users);
    $TeamGroups = new TeamGroups($App->Users);
    $Items = new Items($App->Users);
    $Scheduler = new Scheduler($Items);
    $Templates = new Templates($App->Users);
    $ItemsTypes = new ItemsTypes($App->Users);

    $DisplayParams = new DisplayParams($App->Users, $App->Request);
    // we only want the bookable type of items
    $DisplayParams->appendFilterSql(FilterableColumn::Bookable, 1);
    // make limit very big because we want to see ALL the bookable items here
    $DisplayParams->limit = 900000;
    $itemData = null;

    $allItems = true;
    $selectedItem = null;
    if ($App->Request->query->get('item')) {
        if ($App->Request->query->get('item') === 'all'
            || !$App->Request->query->has('item')) {
        } else {
            $Scheduler->Items->setId($App->Request->query->getInt('item'));
            $selectedItem = $App->Request->query->get('item');
            $allItems = false;
            // itemData is to display the name/category of the selected item
            $itemData = $Scheduler->Items->readOne();
        }
    }

    $entityData = array();
    if ($App->Request->query->has('templateid')) {
        $Templates->setId($App->Request->query->getInt('templateid'));
        $entityData = $Templates->readOne();
    }

    // only the bookable categories
    $bookableCategoryArr = array_filter($ItemsTypes->readAll(), function ($c) {
        return $c['bookable'] === 1;
    });

    $template = 'team.html';
    $renderArr = array(
        'Entity' => $Templates,
        'Scheduler' => $Scheduler,
        'allItems' => $allItems,
        'bookableCategoryArr' => $bookableCategoryArr,
        'itemsArr' => $Items->readShow($DisplayParams),
        'itemData' => $itemData,
        'selectedItem' => $selectedItem,
        'teamArr' => $Teams->readOne(),
        'teamGroupsArr' => $TeamGroups->readAll(),
        'teamsStats' => $Teams->getStats((int) $App->Users->userData['team']),
        'entityData' => $entityData,
        'templatesArr' => $Templates->readAll(),
    );

    $Response->setContent($App->render($template, $renderArr));
} catch (ImproperActionException $e) {
    // show message to user
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
} catch (IllegalActionException $e) {
    // log notice and show message
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $template = 'error.html';
    $renderArr = array('error' => Tools::error(true));
    $Response->setContent($App->render($template, $renderArr));
} catch (DatabaseErrorException | FilesystemErrorException $e) {
    // log error and show message
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
} catch (Exception $e) {
    // log error and show general error message
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $template = 'error.html';
    $renderArr = array('error' => Tools::error());
    $Response->setContent($App->render($template, $renderArr));
} finally {
    $Response->send();
}
