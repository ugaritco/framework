<?php

namespace Heritage\Database\Concerns;

use Heritage\Support\Collection;

trait ExplainsQueries
{
    /**
     * Explains the query.
     *
     * @return \Heritage\Support\Collection
     */
    public function explain()
    {
        $sql = $this->toSql();

        $bindings = $this->getBindings();

        $explanation = $this->getConnection()->select('EXPLAIN '.$sql, $bindings);

        return new Collection($explanation);
    }
}
