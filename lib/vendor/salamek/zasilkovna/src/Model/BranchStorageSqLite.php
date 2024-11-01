<?php
/**
 * Created by PhpStorm.
 * User: sadam
 * Date: 8.9.17
 * Time: 2:12
 */

namespace Salamek\Zasilkovna\Model;

/**
 * Class BranchStorageSqLite
 * @package Salamek\Zasilkovna\Model
 */
class BranchStorageSqLite implements IBranchStorage
{
    private $database;

    private $expiry;
    
    private $tableName = 'branch';

    /**
     * BranchStorageSqLite constructor.
     * @param null $databasePath
     */
    public function __construct($databasePath = null, $expiry = '-24 hours')
    {
        $this->expiry = $expiry;
        if (is_null($databasePath))
        {
            $databasePath = sys_get_temp_dir().'/'.md5(__CLASS__).'.sqlite';
        }

        $this->database = new \PDO('sqlite:/'.$databasePath);
        $this->database->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        //Create table
        $this->createTable();
    }

    private function createTable()
    {
        $query = 'CREATE TABLE IF NOT EXISTS '.$this->tableName.' (`id` INTEGER, `data` TEXT, `created` DATETIME)';
        $statement = $this->database->prepare($query);
        $statement->execute();
    }

    /**
     * @return mixed
     */
    public function getBranchList()
    {
        $query = 'SELECT `data` FROM '.$this->tableName;
        foreach ($this->database->query($query) as $row)
        {
            yield json_decode($row['data'], true);
        }
    }

    /**
     * @param $id
     * @return mixed
     */
    public function find($id)
    {
        $query = 'SELECT `data` FROM '.$this->tableName.' WHERE id = ?';
        $statement = $this->database->prepare($query);

        $statement->execute([$id]);
        $found = $statement->fetch();

        if ($found)
        {
            return json_decode($found['data'], true);
        }

        return null;
    }

    /**
     * @param $branchList
     */
    public function setBranchList($branchList)
    {
        // "Truncate" table
        $query = 'DROP TABLE '.$this->tableName;
        $statement = $this->database->prepare($query);
        $statement->execute([]);

        $this->createTable();

        $query = 'INSERT INTO '.$this->tableName.' (`id`, `data`, `created`) VALUES (?, ?, ?)';
        $statement = $this->database->prepare($query);
        foreach($branchList AS $item)
        {
            $statement->execute([$item['id'], json_encode($item), date('Y-m-d H:i:s')]);
        }
    }

    /**
     * @return bool
     */
    public function isStorageValid()
    {
        $query = 'SELECT `created` FROM '.$this->tableName.' ORDER BY date(`created`) DESC LIMIT 1';
        $statement = $this->database->prepare($query);
        $statement->execute();
        $found = $statement->fetch();

        $limit = new \DateTime();
        $limit->modify($this->expiry);

        if ($found && (new \DateTime($found['created'])) > $limit)
        {
            return true;
        }

        return false;
    }
}