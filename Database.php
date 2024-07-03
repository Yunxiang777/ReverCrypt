<?php
require('./constants.php');

class Database
{
    private static $instance = null;
    private $connection;

    /**
     * 私有化構造方法，防止外部無限次實例化
     */
    private function __construct()
    {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8';
            $this->connection = new PDO($dsn, DB_USER, DB_PASS);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception('Database connection error: ' . $e->getMessage());
        }
    }

    /**
     * 實例化
     */
    public static function getInstance()
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 執行帶有可選參數的 SQL 查詢並返回結果作為關聯數組。
     *
     * @param string $sql 要執行的 SQL 查詢。
     * @param array $params 綁定到 SQL 查詢的可選參數。
     * @return array 查詢結果作為關聯數組返回。
     * @throws Exception 如果查詢出錯，拋出異常。
     */
    public function query(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception('Query error: ' . $e->getMessage());
        }
    }

    /**
     * 插入數據到數據庫
     *
     * @param string $table 表名
     * @param array $data 要插入的數據
     * @return int 插入的行的ID
     * @throws Exception 如果插入出錯，拋出異常。
     */
    public function insert(string $table, array $data): int
    {
        try {
            $fields = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            $sql = "INSERT INTO $table ($fields) VALUES ($placeholders)";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($data);
            return $this->connection->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception('Insert error: ' . $e->getMessage());
        }
    }

    /**
     * 更新數據庫中的數據
     *
     * @param string $table 表名
     * @param array $data 要更新的數據
     * @param array $where 更新條件
     * @return int 受影響的行數
     * @throws Exception 如果更新出錯，拋出異常。
     */
    public function update(string $table, array $data, array $where): int
    {
        try {
            $setClause = implode(', ', array_map(function ($key) {
                return "$key = :$key";
            }, array_keys($data)));
            $whereClause = implode(' AND ', array_map(function ($key) {
                return "$key = :where_$key";
            }, array_keys($where)));
            $sql = "UPDATE $table SET $setClause WHERE $whereClause";
            $stmt = $this->connection->prepare($sql);
            foreach ($where as $key => $value) {
                $stmt->bindValue(":where_$key", $value);
            }
            $stmt->execute(array_merge($data, $where));
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception('Update error: ' . $e->getMessage());
        }
    }

    /**
     * 刪除數據庫中的數據
     *
     * @param string $table 表名
     * @param array $where 刪除條件
     * @return int 受影響的行數
     * @throws Exception 如果刪除出錯，拋出異常。
     */
    public function delete(string $table, array $where): int
    {
        try {
            $whereClause = implode(' AND ', array_map(function ($key) {
                return "$key = :$key";
            }, array_keys($where)));
            $sql = "DELETE FROM $table WHERE $whereClause";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($where);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception('Delete error: ' . $e->getMessage());
        }
    }

    /**
     * 私有化 clone，防止外部clone
     */
    private function __clone()
    {
    }
}
