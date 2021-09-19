<?php

namespace R1KO\QueryBuilder\Expressions;

use R1KO\Database\Contracts\IConnection;
use R1KO\QueryBuilder\Contracts\IExpression;

/**
 * @codeCoverageIgnore
 */
class Table implements IExpression
{
    private string $table;
    private ?string $alias = null;
    private IConnection $db;

    public function __construct(string $expression, IConnection $db)
    {
        $this->db = $db;

        $expression = trim($expression);

        if (mb_strpos($expression, ' ') === false) {
            $this->table = $expression;
            return;
        }

        [$table, $alias] = explode(' ', $expression, 2);

        $this->table = trim($table);
        $alias = trim($alias);

        if (strlen($alias) !== 0 && mb_strpos($alias, ' ') !== false) {
            $this->alias = trim(str_ireplace('as ', '', $alias));
        }
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function getTableName(): string
    {
        $alias = $this->getAlias();
        if ($alias) {
            return $this->quote($alias);
        }

        return $this->quote($this->getTable());
    }

    public function get(): string
    {
        $alias = $this->getAlias();
        $table = $this->quote($this->getTable());
        return $alias ? sprintf('%s AS %s ', $table, $this->quote($alias)) : $table;
    }

    public function __toString(): string
    {
        return $this->get();
    }

    private function quote(string $name): string
    {
        return $this->db->getDriver()->quoteTableName($name);
    }
}
