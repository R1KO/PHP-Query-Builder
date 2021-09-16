<?php

namespace Tests;

use R1KO\Database\Contracts\IConnection;
use R1KO\QueryBuilder\Contracts\IQueryBuilder;
use R1KO\QueryBuilder\QueryBuilder;
use R1KO\QueryBuilder\Expressions\Raw;

class RawExpressionTest extends TestCase
{
    public function testCreateByConnection(): void
    {
        $raw = $this->db->raw('raw');
        $this->assertInstanceOf(Raw::class, $raw);

        $raw = QueryBuilder::asRaw('raw');
        $this->assertInstanceOf(Raw::class, $raw);
    }

    public function testCreateByBuilder(): void
    {
        $raw = $this->db->builder()->raw('raw');
        $this->assertInstanceOf(Raw::class, $raw);

        $raw = QueryBuilder::asRaw('raw');
        $this->assertInstanceOf(Raw::class, $raw);
    }

    public function testGetValue(): void
    {
        $expression = 'raw';
        $raw = $this->db->raw($expression);
        $this->assertInstanceOf(Raw::class, $raw);
        $this->assertEquals($raw->get(), $expression);
        $this->assertEquals((string) $raw, $expression);
    }
}
