<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\CreateNotificationParams;
use Elabftw\Enums\Action;

class NotificationsTest extends \PHPUnit\Framework\TestCase
{
    private Notifications $Notifications;

    protected function setUp(): void
    {
        $this->Notifications = new Notifications(new Users(1, 1));
    }

    public function testCreate(): void
    {
        $body = array(
            'experiment_id' => 1,
            'commenter_userid' => 2,
        );
        $this->assertIsInt($this->Notifications->create(
            new CreateNotificationParams(Notifications::COMMENT_CREATED, $body)
        ));
    }

    public function testRead(): void
    {
        $this->assertIsArray($this->Notifications->readAll());
    }

    public function testUpdate(): void
    {
        $Notifications = new Notifications(new Users(1, 1), 1);
        $this->assertIsArray($Notifications->patch(Action::Update, array()));
    }

    public function testDestroy(): void
    {
        $this->assertTrue($this->Notifications->destroy());
    }
}
