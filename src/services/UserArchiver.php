<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users;
use PDO;

/**
 * Archive/Unarchive a user
 */
class UserArchiver
{
    protected Db $Db;

    public function __construct(private Users $target)
    {
        $this->Db = Db::getConnection();
    }

    public function toggleArchive(): array
    {
        $this->target->userData['archived'] === 0 ? $this->archive() : $this->unarchive();
        $this->toggleArchiveSql();
        return $this->target->readOne();
    }

    private function archive(): bool
    {
        if ($this->target->userData['validated'] === 0) {
            throw new ImproperActionException('You are trying to archive an unvalidated user. Maybe you want to delete the account?');
        }
        // if we are archiving a user, also lock all experiments
        return $this->lockExperiments();
    }

    private function unarchive(): bool
    {
        if ($this->getUnarchivedCount() > 0) {
            throw new ImproperActionException('Cannot unarchive this user because they have another active account with the same email!');
        }
        return true;
    }

    // if the user is already archived, make sure there is no other account with the same email
    private function getUnarchivedCount(): int
    {
        $sql = 'SELECT COUNT(email) FROM users WHERE email = :email AND archived = 0';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':email', $this->target->userData['email']);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    private function toggleArchiveSql(): bool
    {
        $sql = 'UPDATE users SET archived = IF(archived = 1, 0, 1), token = null WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->target->userData['userid'], PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Lock all the experiments owned by user
     */
    private function lockExperiments(): bool
    {
        $sql = 'UPDATE experiments
            SET locked = :locked, lockedby = :userid, lockedwhen = CURRENT_TIMESTAMP WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':locked', 1);
        $req->bindParam(':userid', $this->target->userData['userid'], PDO::PARAM_INT);
        return $this->Db->execute($req);
    }
}